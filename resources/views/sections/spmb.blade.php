@php
    $institutions = \App\Models\Institution::query()->active()->ordered()->withQuotaUsage()->get();
    $openInstitutions = $institutions->filter(fn (\App\Models\Institution $i): bool => $i->registrationOpen());
@endphp

{{-- Section tampil saat minimal satu jenjang sedang membuka pendaftaran --}}
@if($openInstitutions->isNotEmpty())

<x-section-background :config="section_background('section_spmb')"
                      background="transparent"
                      id="spmb" class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('sections.bodies.spmb', ['institutions' => $institutions])
    </div>
</x-section-background>
@endif
