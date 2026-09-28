<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Berapa pendaftar yang sudah membayar biaya pendaftaran, berapa yang belum,
 * dan berapa yang buktinya menunggu konfirmasi panitia. Dihitung per pendaftar
 * (bukan per tagihan), jadi pendaftar yang belum punya tagihan sama sekali
 * tetap terlihat.
 */
class RegistrationsPerPaymentStatusChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 7;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public ?string $filter = 'all';

    /**
     * Label dan warna tiap kelompok, berurutan dari yang paling ditunggu
     * panitia. `null` sebagai status berarti pendaftar tanpa tagihan.
     *
     * @var array<int, array{status: ?string, label: string, color: string}>
     */
    private const BUCKETS = [
        ['status' => RegistrationPayment::STATUS_PAID, 'label' => 'Sudah Membayar', 'color' => '#16a34a'],
        ['status' => RegistrationPayment::STATUS_WAITING, 'label' => 'Butuh Konfirmasi', 'color' => '#f59e0b'],
        ['status' => RegistrationPayment::STATUS_UNPAID, 'label' => 'Belum Membayar', 'color' => '#6b7280'],
        ['status' => RegistrationPayment::STATUS_REJECTED, 'label' => 'Bukti Ditolak', 'color' => '#ef4444'],
        ['status' => RegistrationPayment::STATUS_EXPIRED, 'label' => 'Kedaluwarsa', 'color' => '#a855f7'],
        ['status' => null, 'label' => 'Tanpa Tagihan', 'color' => '#94a3b8'],
    ];

    /**
     * Tanpa fitur pembayaran yang menyala — global maupun di salah satu
     * jenjang — tidak ada satu pun tagihan terbit, jadi grafik ini hanya akan
     * menampilkan nol.
     */
    public static function canView(): bool
    {
        return Institution::paymentEnabledAnywhere() && parent::canView();
    }

    public function getHeading(): ?string
    {
        return 'Pendaftar per Status Pembayaran';
    }

    protected function getFilters(): ?array
    {
        $filters = ['all' => 'Semua Tahun Ajaran'];

        foreach (AcademicYear::query()->orderByDesc('year_start')->get() as $year) {
            $filters[(string) $year->id] = "T.A. {$year->label}";
        }

        return $filters;
    }

    protected function getData(): array
    {
        $counts = $this->countsPerStatus();

        // Kelompok kosong dibuang supaya grafik tidak penuh irisan bernilai 0.
        $buckets = array_values(array_filter(
            self::BUCKETS,
            fn (array $bucket): bool => ($counts[$bucket['status'] ?? ''] ?? 0) > 0,
        ));

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pendaftar',
                    'data' => array_map(fn (array $bucket): int => $counts[$bucket['status'] ?? ''] ?? 0, $buckets),
                    'backgroundColor' => array_column($buckets, 'color'),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_column($buckets, 'label'),
        ];
    }

    /**
     * Jumlah pendaftar per status tagihan. Pendaftar tanpa tagihan dihitung
     * di bawah kunci string kosong, sejalan dengan `BUCKETS`.
     *
     * @return array<string, int>
     */
    private function countsPerStatus(): array
    {
        $registrations = SpmbRegistration::query()
            ->visibleTo(auth()->user())
            ->when(
                $this->filter !== null && $this->filter !== 'all',
                fn (Builder $query): Builder => $query->where('academic_year_id', $this->filter),
            );

        $counts = (clone $registrations)
            ->join('registration_payments', 'registration_payments.spmb_registration_id', '=', 'spmb_registrations.id')
            ->selectRaw('registration_payments.status as payment_status, COUNT(*) as total')
            ->groupBy('registration_payments.status')
            ->pluck('total', 'payment_status')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $counts[''] = (clone $registrations)->whereDoesntHave('payment')->count();

        return $counts;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
