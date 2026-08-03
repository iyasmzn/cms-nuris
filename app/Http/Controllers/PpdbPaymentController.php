<?php

namespace App\Http\Controllers;

use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PpdbPaymentController extends Controller
{
    /**
     * The lookup form a pendaftar uses to get back to their status page after
     * losing the link they were redirected to on submission.
     */
    public function lookup(): View
    {
        $siteName = setting('site_name', config('app.name'));

        $seo = [
            'title' => "Status Pendaftaran & Pembayaran | {$siteName}",
            'description' => 'Cek status pendaftaran PPDB dan unggah bukti pembayaran biaya pendaftaran.',
            'canonical' => route('ppdb.status'),
        ];

        return view('ppdb.status', compact('seo'));
    }

    /**
     * Resolve a registration from an identity (nomor pendaftaran or NIK) paired
     * with a phone number, then hand back the signed status URL. Accepting NIK
     * as an alternative means a pendaftar who lost their nomor pendaftaran can
     * still get in, while still requiring two matching fields so the page —
     * which carries personal data — cannot be opened by guessing one of them.
     *
     * Phone numbers are compared digits-only so `0812-3456` and `081234...`
     * both match what the formulir stored, and the parent's number counts too
     * because it is often the one on the registration.
     *
     * Deliberately vague on failure so the form cannot be used to probe which
     * nomor pendaftaran or NIK exist.
     */
    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:25'],
        ], [
            'identity.required' => 'Nomor pendaftaran atau NIK wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
        ]);

        $identity = trim($validated['identity']);
        $phone = preg_replace('/\D/', '', $validated['phone']) ?? '';

        $registration = SpmbRegistration::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('registration_number', $identity)
                ->orWhere('nik', $identity))
            ->limit(10)
            ->get()
            ->first(fn (SpmbRegistration $candidate): bool => in_array($phone, [
                preg_replace('/\D/', '', (string) $candidate->phone),
                preg_replace('/\D/', '', (string) $candidate->parent_phone),
            ], true));

        if ($registration === null) {
            return back()
                ->withInput()
                ->with('error', 'Data pendaftaran tidak ditemukan. Periksa kembali nomor pendaftaran / NIK dan nomor HP yang Anda isi saat mendaftar.');
        }

        return redirect()->to(self::statusUrl($registration));
    }

    /**
     * Status pendaftaran plus, when a tagihan exists, the payment instructions
     * and bukti transfer upload form. Reachable only through a signed URL.
     */
    public function show(SpmbRegistration $registration): View
    {
        $registration->load(['institution', 'academicYear', 'registrationWave', 'admissionPath', 'payment']);

        $payment = $registration->payment;
        $bankAccounts = spmb_bank_accounts();
        $instructions = (string) setting('spmb_payment_instructions', '');
        $siteName = setting('site_name', config('app.name'));

        $seo = [
            'title' => "Status Pendaftaran {$registration->registration_number} | {$siteName}",
            'description' => 'Status pendaftaran PPDB dan pembayaran biaya pendaftaran.',
            'robots' => 'noindex, nofollow',
        ];

        $proofUrl = $payment?->acceptsProof()
            ? URL::signedRoute('ppdb.payment.proof', $registration)
            : null;

        // Handed to the view rather than read from the current request, so the
        // copy button always yields the canonical signed URL even if the
        // pendaftar arrived with extra query parameters attached.
        $statusUrl = self::statusUrl($registration);

        return view('ppdb.payment', compact('registration', 'payment', 'bankAccounts', 'instructions', 'proofUrl', 'statusUrl', 'seo'));
    }

    /**
     * Accept a bukti transfer from the pendaftar and queue it for the panitia.
     */
    public function uploadProof(Request $request, SpmbRegistration $registration): RedirectResponse
    {
        $payment = $registration->payment;

        if ($payment === null || ! $payment->acceptsProof()) {
            return redirect()->to(self::statusUrl($registration))
                ->with('error', 'Bukti pembayaran tidak dapat dikirim untuk tagihan ini.');
        }

        $accounts = spmb_bank_accounts();

        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'bank_account' => [$accounts === [] ? 'nullable' : 'required', 'integer', 'min:0', 'max:'.max(count($accounts) - 1, 0)],
            'transferred_on' => ['required', 'date', 'before_or_equal:today'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'sender_name.required' => 'Nama pengirim wajib diisi.',
            'bank_account.required' => 'Pilih rekening tujuan transfer.',
            'transferred_on.required' => 'Tanggal transfer wajib diisi.',
            'transferred_on.before_or_equal' => 'Tanggal transfer tidak boleh melewati hari ini.',
            'proof.required' => 'Bukti transfer wajib diunggah.',
            'proof.mimes' => 'Bukti transfer harus berupa gambar (JPG/PNG) atau PDF.',
        ]);

        $previousProof = $payment->proof_path;

        $account = $accounts[$validated['bank_account'] ?? null] ?? null;

        $payment->submitProof([
            'bank_account' => $account === null
                ? null
                : "{$account['bank']} {$account['number']} a.n. {$account['holder']}",
            'sender_name' => $validated['sender_name'],
            'transferred_on' => $validated['transferred_on'],
            'proof_path' => $request->file('proof')->store("ppdb-bukti/{$registration->institution_id}", 'local'),
        ]);

        // A resubmitted bukti replaces the rejected one; keep the disk tidy.
        if (filled($previousProof)) {
            Storage::disk('local')->delete($previousProof);
        }

        return redirect()->to(self::statusUrl($registration))
            ->with('success', 'Bukti pembayaran berhasil dikirim. Panitia akan memverifikasi dalam 1×24 jam kerja.');
    }

    /**
     * Content types a bukti may be served as. Anything uploaded is only ever
     * rendered as one of these, regardless of what the file claims to be.
     *
     * @var array<string, string>
     */
    private const PROOF_CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
    ];

    /**
     * Stream a bukti transfer to authorised panel users only. Bukti live on the
     * private disk, so this route is the only way to fetch one.
     */
    public function downloadProof(Request $request, RegistrationPayment $payment): StreamedResponse
    {
        $this->authoriseProofAccess($request, $payment);

        return Storage::disk('local')->download($payment->proof_path);
    }

    /**
     * The same bukti served for display in the browser rather than as a
     * download, so panitia can eyeball a struk without saving it first.
     *
     * The content type is pinned to a whitelist derived from the extension and
     * sniffing is switched off: rendering a user upload inline would otherwise
     * let a file that merely claims to be an image execute as HTML.
     */
    public function previewProof(Request $request, RegistrationPayment $payment): StreamedResponse
    {
        $this->authoriseProofAccess($request, $payment);

        $extension = strtolower(pathinfo($payment->proof_path, PATHINFO_EXTENSION));
        $contentType = self::PROOF_CONTENT_TYPES[$extension] ?? null;

        abort_if($contentType === null, 404);

        return Storage::disk('local')->response($payment->proof_path, headers: [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.basename($payment->proof_path).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; object-src 'self'",
        ]);
    }

    /**
     * Guard shared by both bukti routes: panel permission plus a file that
     * actually exists on the private disk.
     */
    private function authoriseProofAccess(Request $request, RegistrationPayment $payment): void
    {
        abort_unless($request->user()?->can('View:RegistrationPayment'), 403);

        abort_if(
            blank($payment->proof_path) || ! Storage::disk('local')->exists($payment->proof_path),
            404,
        );
    }

    /**
     * The signed status URL for a registration. Shared with SpmbController so a
     * freshly submitted pendaftar lands on the same page a lookup leads to.
     */
    public static function statusUrl(SpmbRegistration $registration): string
    {
        return URL::signedRoute('ppdb.payment', $registration);
    }
}
