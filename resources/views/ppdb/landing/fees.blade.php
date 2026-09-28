{{-- Seksi "Rincian Biaya": tabel biaya dari Pengaturan PPDB atau jenjang terpilih. --}}
<div class="pl-narrow {{ ($block['heading_align'] ?? 'left') === 'center' ? 'mx-auto' : '' }}">
    <div class="fi-card overflow-hidden" data-aos="fade-up">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:var(--border); background:var(--bg)">
                    <th class="px-6 py-3.5 text-left font-semibold text-xs uppercase tracking-wider" style="color:var(--muted)">Komponen Biaya</th>
                    <th class="px-6 py-3.5 text-left font-semibold text-xs uppercase tracking-wider" style="color:var(--muted)">Jumlah</th>
                    <th class="px-6 py-3.5 text-left font-semibold text-xs uppercase tracking-wider hidden sm:table-cell" style="color:var(--muted)">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($block['data'] as $fee)
                    <tr class="pl-fee-row border-b last:border-b-0" style="border-color:var(--border)">
                        <td class="px-6 py-4 font-medium" style="color:var(--text)">
                            {{ $fee['category'] ?? '' }}
                            @if(filled($fee['note'] ?? null))
                                <div class="sm:hidden text-xs font-normal mt-0.5" style="color:var(--muted)">{{ $fee['note'] }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-amber-600 whitespace-nowrap">{{ $fee['amount'] ?? '' }}</td>
                        <td class="px-6 py-4 hidden sm:table-cell" style="color:var(--muted)">{{ $fee['note'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
