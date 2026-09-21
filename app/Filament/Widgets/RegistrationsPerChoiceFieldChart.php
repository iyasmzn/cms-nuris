<?php

namespace App\Filament\Widgets;

use App\Models\PpdbField;
use App\Models\SpmbRegistration;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Collection;

/**
 * Grafik jumlah pendaftar per pilihan pada sebuah field formulir — misalnya
 * Pilihan Kelas (SD: Reguler/Tahfizh, KB & TK: KB/TK).
 *
 * Isinya mengikuti formulir tiap jenjang: field mana pun bertipe dropdown atau
 * radio yang ditandai "Tampilkan Grafiknya di Dasbor" akan muncul di pemilih
 * di atas grafik. Jenjang yang tidak punya field seperti itu tidak menambah
 * apa pun, dan bila tak satu pun jenjang punya, widget ini tidak tampil.
 */
class RegistrationsPerChoiceFieldChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 8;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public ?string $filter = null;

    /**
     * Palette cycled across the options of one field.
     *
     * @var array<int, string>
     */
    private const PALETTE = ['#d97706', '#3b82f6', '#16a34a', '#8b5cf6', '#06b6d4', '#ef4444', '#f59e0b', '#6b7280'];

    public function mount(): void
    {
        $this->filter ??= (string) (self::chartableFields()->first()?->getKey() ?? '');

        parent::mount();
    }

    public static function canView(): bool
    {
        return self::chartableFields()->isNotEmpty() && parent::canView();
    }

    public function getHeading(): ?string
    {
        $field = $this->selectedField();

        if ($field === null) {
            return 'Pendaftar per Pilihan';
        }

        $jenjang = $field->institution?->short_name ?: $field->institution?->name;

        return trim("Pendaftar per {$field->label}".($jenjang ? " — {$jenjang}" : ''));
    }

    protected function getFilters(): ?array
    {
        return self::chartableFields()
            ->mapWithKeys(fn (PpdbField $field): array => [
                (string) $field->getKey() => trim(
                    ($field->institution?->short_name ?: $field->institution?->name ?: '').' — '.$field->label,
                    ' —',
                ),
            ])
            ->all();
    }

    protected function getData(): array
    {
        $field = $this->selectedField();

        if ($field === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $counts = $this->countsPerOption($field);

        $labels = $field->optionValues();

        // Jawaban yang tidak lagi ada di daftar pilihan (opsi yang dihapus atau
        // diganti namanya) tetap dihitung, supaya totalnya tidak diam-diam susut.
        foreach (array_keys($counts) as $answer) {
            if ($answer !== '' && ! in_array($answer, $labels, true)) {
                $labels[] = $answer;
            }
        }

        $data = array_map(fn (string $label): int => $counts[$label] ?? 0, $labels);

        if (($counts[''] ?? 0) > 0) {
            $labels[] = 'Belum diisi';
            $data[] = $counts[''];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pendaftar',
                    'data' => $data,
                    'backgroundColor' => array_map(
                        fn (int $index): string => self::PALETTE[$index % count(self::PALETTE)],
                        array_keys($labels),
                    ),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Jumlah pendaftar jenjang ini per jawaban. Jawaban sebuah field dinamis
     * tersimpan di kolom `data` (JSON), kecuali key-nya kebetulan sama dengan
     * salah satu kolom pendaftar.
     *
     * @return array<string, int>
     */
    private function countsPerOption(PpdbField $field): array
    {
        // Key-nya ikut disusun ke dalam SQL, jadi dibersihkan dulu sampai hanya
        // tersisa huruf, angka dan garis bawah — bentuk yang memang dipakai
        // sebagai key field.
        $key = preg_replace('/[^A-Za-z0-9_]/', '', (string) $field->key) ?? '';

        if ($key === '') {
            return [];
        }

        $answer = in_array($key, SpmbRegistration::dynamicColumnKeys(), true)
            ? "COALESCE(`{$key}`, '')"
            : "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.\"{$key}\"')), '')";

        return SpmbRegistration::query()
            ->visibleTo(auth()->user())
            ->where('institution_id', $field->institution_id)
            ->selectRaw("{$answer} as answer, COUNT(*) as total")
            ->groupBy('answer')
            ->pluck('total', 'answer')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    private function selectedField(): ?PpdbField
    {
        $fields = self::chartableFields();

        return $fields->first(fn (PpdbField $field): bool => (string) $field->getKey() === (string) $this->filter)
            ?? $fields->first();
    }

    /**
     * Field pilihan yang ditandai untuk dasbor, hanya dari jenjang yang boleh
     * dilihat user ini.
     *
     * @return Collection<int, PpdbField>
     */
    private static function chartableFields(): Collection
    {
        $user = auth()->user();
        $visible = $user === null ? [] : $user->visibleInstitutionIds();

        return PpdbField::query()
            ->chartedOnDashboard()
            ->when($visible !== null, fn ($query) => $query->whereIn('institution_id', $visible ?? []))
            ->whereHas('institution', fn ($query) => $query->where('is_active', true))
            ->with('institution')
            ->orderBy('institution_id')
            ->ordered()
            ->get();
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
