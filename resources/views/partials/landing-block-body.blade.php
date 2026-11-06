{{--
    Isi blok data di halaman & program — seksi halaman depan yang dipasang
    sebagai blok. Judul, latar, dan jaraknya sudah dirender content-blocks;
    partial ini cukup meneruskan data blok (`$block['data']`, disiapkan
    App\Support\LandingBlocks::prepare()) ke isi seksi yang sama dengan halaman
    depan di sections/bodies/.

    Params (dari content-blocks): $block
--}}
@php
    $type = (string) ($block['type'] ?? '');
    $data = $block['data'] ?? null;
    $gridClass = \App\Support\LandingBlocks::gridClass($block);
    $moreLink = \App\Support\LandingBlocks::MORE_LINKS[$type] ?? null;
    $showMoreLink = $moreLink !== null && (bool) ($block['show_more_link'] ?? true);
@endphp

@if($data !== null)
<div class="ldb ldb-{{ \Illuminate\Support\Str::after($type, 'data_') }}">
    @switch($type)
        @case('data_stats')
            @include('sections.bodies.stats', ['stats' => $data])
            @break

        @case('data_greetings')
            @include('sections.bodies.greetings', ['greetings' => $data])
            @break

        @case('data_programs')
            @include('sections.bodies.programs', ['programs' => $data, 'gridClass' => $gridClass])
            @break

        @case('data_events')
            @include('sections.bodies.events', ['events' => $data, 'gridClass' => $gridClass])
            @break

        @case('data_gallery')
            @include('sections.bodies.gallery', ['galleryMedia' => $data, 'gridClass' => $gridClass.' ldb-grid-dense'])
            @break

        @case('data_posts')
            @include('sections.bodies.posts', [
                'posts' => $data,
                'featured' => (bool) ($block['posts_featured'] ?? true),
                'gridClass' => $gridClass,
            ])
            @break

        @case('data_alumni')
            @once
                @include('sections.bodies.alumni-styles')
            @endonce
            @include('sections.bodies.alumni-stats', ['alumniStats' => $data['stats']])
            @include('sections.bodies.alumni-logos', [
                'alumniUniversities' => $data['universities'],
                'alumniMarquee' => \App\Support\AlumniMarquee::fromSettings(),
            ])
            @break

        @case('data_testimonials')
            @include('sections.bodies.testimonials', ['testimonials' => $data, 'gridClass' => $gridClass])
            @break

        @case('data_faq')
            <div class="ldb-narrow">
                @include('sections.bodies.faq', [
                    'faqs' => $data,
                    'faqIdPrefix' => 'faq-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)),
                ])
            </div>
            @break

        @case('data_contact')
            @include('sections.bodies.contact', [
                'contactItems' => $data,
                'dark' => false,
                'showMap' => (bool) ($block['contact_map'] ?? true),
                'showButtons' => (bool) ($block['contact_buttons'] ?? true),
            ])
            @break

        @case('data_quick_links')
            @include('sections.bodies.quick-links', ['quickLinks' => $data])
            @break

        @case('data_spmb')
            @include('sections.bodies.spmb', ['institutions' => $data])
            @break

        @case('data_spmb_steps')
            @include('sections.bodies.spmb-steps', ['procedures' => $data])
            @break
    @endswitch

    @if($showMoreLink)
        <div class="mt-10 text-center">
            <a href="{{ route($moreLink[0]) }}" class="btn-outline">{{ $moreLink[1] }}</a>
        </div>
    @endif
</div>
@endif
