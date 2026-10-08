{{-- Seksi "Pilihan Jenjang": kartu tiap jenjang aktif beserta status pendaftarannya. --}}
@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Institution> $institutions */
    $institutions = $block['data'];
    $fmtDate = fn ($date) => $date ? $date->locale('id')->translatedFormat('d M Y') : null;
@endphp

@if($institutions->isEmpty())
    <div class="fi-card p-10 text-center max-w-lg mx-auto" data-aos="fade-up">
        <div class="text-5xl mb-4">🏫</div>
        <h3 class="font-bold text-lg mb-2" style="color:var(--text)">Belum Ada Jenjang Tersedia</h3>
        <p class="text-sm" style="color:var(--muted)">Informasi PPDB akan segera tersedia. Silakan cek kembali beberapa saat lagi.</p>
    </div>
@else
    <div class="{{ \App\Support\PpdbLanding::gridClass($block, 'institutions_columns') }}">
        @foreach($institutions as $institution)
            @php
                $open = $institution->registrationOpen();
                $wave = $institution->usesInternalForm() ? \App\Models\RegistrationWave::relevant($institution) : null;
            @endphp
            <div class="pl-card-hover fi-card flex flex-col border overflow-hidden" style="border-color:var(--border)"
                 data-aos="fade-up" data-aos-delay="{{ min($loop->index, 5) * 80 }}">
                <a href="{{ route('ppdb.show', $institution) }}" class="p-6 flex flex-col flex-1">
                    <div class="flex items-center justify-between mb-4">
                        <div class="pl-icon-tile">
                            @if($url = icon_url($institution->icon_image))
                                <img src="{{ $url }}" alt="{{ $institution->name }}" loading="lazy" class="w-8 h-8 object-contain">
                            @else
                                <span class="text-3xl">{{ $institution->icon ?: '🏫' }}</span>
                            @endif
                        </div>
                        @if($open)
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Dibuka
                            </span>
                        @elseif($institution->closedByQuota())
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-200">Kuota Penuh</span>
                        @else
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 border border-gray-200">Segera</span>
                        @endif
                    </div>

                    @if($institution->short_name)
                        <span class="text-xs font-bold uppercase tracking-widest text-amber-600 mb-1">{{ $institution->short_name }}</span>
                    @endif
                    <h3 class="font-bold text-lg mb-2" style="color:var(--text)">{{ $institution->name }}</h3>
                    @if($institution->description)
                        <p class="text-sm leading-relaxed mb-4" style="color:var(--muted)">{{ \Illuminate\Support\Str::limit($institution->description, 130) }}</p>
                    @endif

                    @if($open && $wave && $fmtDate($wave->end_date))
                        <div class="flex items-center gap-1.5 text-xs font-semibold mb-4" style="color:var(--muted)">
                            🗓️ Batas daftar: <span class="text-amber-600">{{ $fmtDate($wave->end_date) }}</span>
                        </div>
                    @elseif($institution->usesExternalLink() || $institution->usesEmbed())
                        <div class="flex items-center gap-1.5 text-xs font-semibold mb-4" style="color:var(--muted)">
                            🔗 Pendaftaran online
                        </div>
                    @endif

                    <span class="mt-auto inline-flex items-center gap-2 text-sm font-bold text-amber-600">
                        Lihat PPDB {{ $institution->short_name ?? $institution->name }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </span>
                </a>
                @if($institution->detail_url)
                    @php $detailIsExternal = \Illuminate\Support\Str::startsWith($institution->detail_url, ['http://', 'https://']); @endphp
                    <a href="{{ $institution->detail_url }}" @if($detailIsExternal) target="_blank" rel="noopener" @endif
                       class="flex items-center justify-center gap-1.5 text-xs font-bold px-4 py-3.5 border-t transition-colors hover:text-amber-600"
                       style="color:var(--muted); border-color:var(--border)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Lihat Detail Jenjang
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
