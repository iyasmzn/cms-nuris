{{-- Seksi "Jadwal Gelombang": gelombang tahun ajaran aktif, dikelompokkan per jenjang. --}}
@php
    $fmtDate = fn ($date) => $date ? $date->locale('id')->translatedFormat('d M Y') : '—';
@endphp

<div class="pl-grid pl-grid-wide">
    @foreach($block['data'] as $group)
        @php $institution = $group['institution']; @endphp
        <div class="fi-card p-6" data-aos="fade-up" data-aos-delay="{{ min($loop->index, 5) * 70 }}">
            <div class="flex items-center gap-3 mb-5">
                <div class="pl-icon-tile pl-icon-tile-sm">
                    @if($url = icon_url($institution->icon_image))
                        <img src="{{ $url }}" alt="{{ $institution->name }}" loading="lazy" class="w-6 h-6 object-contain">
                    @else
                        <span class="text-2xl">{{ $institution->icon ?: '🏫' }}</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-bold text-sm leading-snug" style="color:var(--text)">{{ $institution->name }}</div>
                    <div class="text-xs" style="color:var(--muted)">Tahun Ajaran {{ spmb_year_label() }}</div>
                </div>
                <a href="{{ route('ppdb.show', $institution) }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 shrink-0">Detail →</a>
            </div>

            <div class="space-y-3">
                @foreach($group['waves'] as $wave)
                    @php
                        if ($wave->isOpen()) {
                            [$badgeText, $badgeClass, $dot] = ['Dibuka', 'bg-green-50 text-green-700 border-green-200', 'bg-green-500 animate-pulse'];
                        } elseif ($wave->start_date->isFuture()) {
                            [$badgeText, $badgeClass, $dot] = ['Akan Datang', 'bg-blue-50 text-blue-700 border-blue-200', 'bg-blue-500'];
                        } else {
                            [$badgeText, $badgeClass, $dot] = ['Selesai', 'bg-gray-100 text-gray-500 border-gray-200', 'bg-gray-400'];
                        }
                    @endphp
                    <div class="rounded-xl border p-4" style="border-color:var(--border); background:var(--bg)">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="font-bold text-sm" style="color:var(--text)">{{ $wave->name }}</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2 py-0.5 rounded-full border {{ $badgeClass }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>{{ $badgeText }}
                            </span>
                        </div>
                        <dl class="space-y-1.5 text-xs">
                            <div class="flex items-center gap-2">
                                <dt style="color:var(--muted)">📅 Pendaftaran</dt>
                                <dd class="font-semibold ml-auto text-right" style="color:var(--text)">{{ $fmtDate($wave->start_date) }} – {{ $fmtDate($wave->end_date) }}</dd>
                            </div>
                            @if($wave->selection_date)
                                <div class="flex items-center gap-2">
                                    <dt style="color:var(--muted)">🔍 Seleksi</dt>
                                    <dd class="font-semibold ml-auto" style="color:var(--text)">{{ $fmtDate($wave->selection_date) }}</dd>
                                </div>
                            @endif
                            @if($wave->announcement_date)
                                <div class="flex items-center gap-2">
                                    <dt style="color:var(--muted)">🎉 Pengumuman</dt>
                                    <dd class="font-semibold ml-auto" style="color:var(--text)">{{ $fmtDate($wave->announcement_date) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
