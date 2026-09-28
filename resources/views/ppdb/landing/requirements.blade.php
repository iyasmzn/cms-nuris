{{-- Seksi "Persyaratan Dokumen": daftar berkas dari Pengaturan PPDB atau jenjang terpilih. --}}
<div class="pl-narrow {{ ($block['heading_align'] ?? 'left') === 'center' ? 'mx-auto' : '' }}">
    <div class="fi-card p-6 sm:p-8" data-aos="fade-up">
        <ul class="grid sm:grid-cols-2 gap-3">
            @foreach($block['data'] as $requirement)
                <li class="flex items-start gap-2.5">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-sm" style="color:var(--muted)">{{ $requirement }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
