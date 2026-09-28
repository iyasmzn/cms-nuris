{{-- Seksi "Kuota Penerimaan": kuota tiap jenjang, opsional dengan jumlah terisi & sisanya. --}}
@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Institution> $institutions */
    $institutions = $block['data'];
    $showRemaining = (bool) ($block['show_remaining'] ?? true);
@endphp

<div class="pl-grid">
    @foreach($institutions as $institution)
        @php
            $tracksUsage = $showRemaining && $institution->usesInternalForm();
            $used = $tracksUsage ? min($institution->quotaUsed(), $institution->quota) : 0;
            $full = $tracksUsage && $institution->isQuotaFull();
            $percent = $tracksUsage ? (int) round($used / $institution->quota * 100) : 0;
        @endphp
        <div class="fi-card p-6 flex flex-col" data-aos="fade-up" data-aos-delay="{{ min($loop->index, 5) * 80 }}">
            <div class="flex items-center gap-3 mb-5">
                <div class="pl-icon-tile pl-icon-tile-sm">
                    @if($url = icon_url($institution->icon_image))
                        <img src="{{ $url }}" alt="{{ $institution->name }}" loading="lazy" class="w-6 h-6 object-contain">
                    @else
                        <span class="text-2xl">{{ $institution->icon ?: '🏫' }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    @if($institution->short_name)
                        <div class="text-[11px] font-bold uppercase tracking-widest text-amber-600">{{ $institution->short_name }}</div>
                    @endif
                    <div class="font-bold text-sm leading-snug" style="color:var(--text)">{{ $institution->name }}</div>
                </div>
            </div>

            <div class="pl-quota-number">{{ number_format($institution->quota, 0, ',', '.') }}<small>kursi</small></div>

            @if($tracksUsage)
                <div class="mt-5 pl-progress {{ $full ? 'pl-progress-full' : '' }}"
                     role="progressbar" aria-valuemin="0" aria-valuemax="{{ $institution->quota }}" aria-valuenow="{{ $used }}"
                     aria-label="Kuota terisi {{ $institution->name }}">
                    <span style="width: {{ $percent }}%"></span>
                </div>
                <div class="mt-2 flex items-center justify-between gap-2 text-xs" style="color:var(--muted)">
                    <span>Terisi <strong style="color:var(--text)">{{ $used }}</strong></span>
                    @if($full)
                        <span class="font-bold text-red-600">Kuota Penuh</span>
                    @else
                        <span>Sisa <strong class="text-amber-600">{{ $institution->remainingQuota() }}</strong></span>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
