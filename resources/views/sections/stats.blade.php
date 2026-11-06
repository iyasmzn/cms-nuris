<x-section-background :config="section_background('section_stats')"
                      background="transparent"
                      id="profil" class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($stats->isNotEmpty())
            @include('sections.bodies.stats', ['stats' => $stats])
        @endif
    </div>
</x-section-background>
