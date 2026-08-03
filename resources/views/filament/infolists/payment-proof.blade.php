@php
    use App\Models\RegistrationPayment;

    // Rendered from two places: the payment preview (record is the payment) and
    // the registration preview (record is the pendaftar, payment hangs off it).
    $record = $getRecord();
    $payment = $record instanceof RegistrationPayment ? $record : $record?->payment;

    $path = $payment?->proof_path;
    $extension = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : null;
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
    $isPdf = $extension === 'pdf';

    $previewUrl = $payment && $path ? route('ppdb.payment.preview', $payment) : null;
    $downloadUrl = $payment && $path ? route('ppdb.payment.download', $payment) : null;
@endphp

<div class="space-y-3">
    @if (! $previewUrl)
        <p class="fi-color-gray text-sm text-gray-500 dark:text-gray-400">
            Belum ada bukti transfer diunggah.
        </p>
    @else
        @if ($isImage)
            {{-- Click to open the full-size original in a new tab. --}}
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
               class="block w-fit overflow-hidden rounded-lg border border-gray-200 transition hover:opacity-90 dark:border-gray-700">
                <img src="{{ $previewUrl }}"
                     alt="Bukti transfer {{ $payment->invoice_number }}"
                     loading="lazy"
                     class="max-h-80 w-auto max-w-full object-contain bg-gray-50 dark:bg-gray-900">
            </a>
        @elseif ($isPdf)
            <object data="{{ $previewUrl }}" type="application/pdf"
                    class="h-96 w-full rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="p-4 text-sm text-gray-500 dark:text-gray-400">
                    Pratinjau PDF tidak dapat ditampilkan di peramban ini. Gunakan tombol di bawah.
                </p>
            </object>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Format berkas tidak dapat dipratinjau.
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
               class="fi-btn fi-btn-size-sm inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4" />
                Buka di Tab Baru
            </a>

            <a href="{{ $downloadUrl }}"
               class="fi-btn fi-btn-size-sm inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                Unduh Berkas
            </a>
        </div>
    @endif
</div>
