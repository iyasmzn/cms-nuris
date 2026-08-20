<?php

namespace App\Filament\Resources\SpmbRegistrations\Pages;

use App\Filament\Resources\SpmbRegistrations\SpmbRegistrationResource;
use App\Models\Institution;
use App\Models\PpdbField;
use App\Models\RegistrationPayment;
use App\Models\SpmbRegistration;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSpmbRegistrations extends ListRecords
{
    protected static string $resource = SpmbRegistrationResource::class;

    /**
     * Header of the exported spreadsheet.
     *
     * @var array<int, string>
     */
    private const EXPORT_HEADINGS = [
        'No', 'Tahun Ajaran', 'Jenjang', 'Gelombang', 'Jalur', 'Nama Lengkap', 'NIK', 'Email', 'No. HP',
        'Tempat Lahir', 'Tanggal Lahir', 'Sekolah Asal', 'Kota Sekolah', 'Alamat',
        'Nama Orang Tua', 'No. HP Orang Tua', 'Status', 'Catatan', 'Tanggal Daftar',
        'No. Tagihan', 'Nominal Tagihan', 'Status Pembayaran',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportToExcel()),
        ];
    }

    private function exportToExcel(): StreamedResponse
    {
        /** @var Builder<SpmbRegistration> $query */
        $query = $this->getFilteredTableQuery()
            ->with(['academicYear', 'institution', 'registrationWave', 'admissionPath', 'payment']);

        $institution = $this->filteredInstitution();
        $extraFields = $this->extraExportFields($institution, $query);

        $filename = collect(['data-pendaftar-spmb', $institution?->short_name ?: $institution?->name, now()->format('Y-m-d-His')])
            ->filter()
            ->map(fn (string $part): string => Str::slug($part))
            ->implode('-').'.xlsx';

        return response()->streamDownload(function () use ($query, $extraFields): void {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(
                [...self::EXPORT_HEADINGS, ...$extraFields->pluck('label')->all()],
                (new Style)->setFontBold(),
            ));

            $number = 0;
            $statuses = SpmbRegistration::statusOptions();
            $paymentStatuses = RegistrationPayment::statusOptions();

            $query->orderBy('created_at')->chunk(200, function ($records) use ($writer, &$number, $statuses, $paymentStatuses, $extraFields): void {
                foreach ($records as $record) {
                    $writer->addRow(Row::fromValues([
                        ++$number,
                        $record->academicYear?->label ?? '',
                        $record->institution?->short_name ?: ($record->institution?->name ?? ''),
                        $record->registrationWave?->name ?? '',
                        $record->admissionPath?->name ?? '',
                        $record->full_name,
                        $record->nik ?? '',
                        $record->email ?? '',
                        $record->phone ?? '',
                        $record->birth_place ?? '',
                        $record->birth_date?->format('d/m/Y') ?? '',
                        $record->previous_school ?? '',
                        $record->previous_school_city ?? '',
                        $record->address ?? '',
                        $record->parent_name ?? '',
                        $record->parent_phone ?? '',
                        $statuses[$record->status] ?? $record->status,
                        $record->notes ?? '',
                        $record->created_at?->format('d/m/Y H:i') ?? '',
                        $record->payment?->invoice_number ?? '',
                        $record->payment?->total() ?? '',
                        $record->payment === null ? '' : ($paymentStatuses[$record->payment->status] ?? $record->payment->status),
                        ...$extraFields->map(fn (PpdbField $field): string => $this->dynamicValue($record, $field))->all(),
                    ]));
                }
            });

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * The jenjang the list is currently narrowed to, if any. Only then is there a
     * single known set of dynamic fields to widen the sheet with.
     */
    private function filteredInstitution(): ?Institution
    {
        return Institution::find($this->getTableFilterState('jenjang')['institution_id'] ?? null);
    }

    /**
     * The jenjang's dynamic form fields that have no column of their own in the
     * fixed headings — their answers live in the `data` bucket and would
     * otherwise be missing from the export entirely.
     *
     * A field the admin has since switched off still has answers on the pendaftar
     * who filled it in, so it keeps its column for as long as some exported row
     * actually carries one.
     *
     * @param  Builder<SpmbRegistration>  $query
     * @return Collection<int, PpdbField>
     */
    private function extraExportFields(?Institution $institution, Builder $query): Collection
    {
        if ($institution === null) {
            return new Collection;
        }

        return $institution->ppdbFields()
            ->ordered()
            ->get()
            ->reject(fn (PpdbField $field): bool => in_array($field->key, SpmbRegistration::dynamicColumnKeys(), true))
            ->filter(fn (PpdbField $field): bool => $field->is_active
                || (clone $query)->whereNotNull("data->{$field->key}")->exists())
            ->values();
    }

    /**
     * One dynamic answer as a spreadsheet cell. An uploaded berkas is a stored
     * path nobody can open from a sheet, so it travels as its download link.
     */
    private function dynamicValue(SpmbRegistration $record, PpdbField $field): string
    {
        $value = data_get($record->data, $field->key);

        if (blank($value)) {
            return '';
        }

        if ($field->type === 'file') {
            return route('ppdb.berkas', [$record, $field->key]);
        }

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
