@php
    $quickLinks = \App\Support\LandingBlocks::quickLinks();
@endphp

@if($quickLinks->isNotEmpty())
<x-section-background :config="section_background('section_quick_links')"
                     background="transparent"
                     id="tautan-cepat"
                     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('sections.bodies.quick-links', ['quickLinks' => $quickLinks])
</x-section-background>
@endif
