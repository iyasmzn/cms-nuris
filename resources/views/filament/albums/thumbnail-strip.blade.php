{{--
    Compact peek at an album's contents: the newest few items, with a counter
    for whatever else is inside.
--}}
@php
    $record = $getRecord();
    $items = $record->media;
    $remaining = max(0, ($record->media_count ?? $items->count()) - $items->count());
@endphp

@if ($items->isEmpty())
    <span class="text-xs" style="color:var(--gray-400)">Album kosong</span>
@else
    <div style="display:flex;align-items:center;gap:.25rem;">
        @foreach ($items as $item)
            @if ($item->is_video && blank($item->embed_thumbnail_path))
                <video src="{{ $item->url }}#t=0.5"
                       preload="metadata"
                       muted
                       playsinline
                       title="{{ $item->name }}"
                       style="width:44px;height:44px;object-fit:cover;border-radius:.375rem;display:block;background:#f5f5f4;"></video>
            @else
                <img src="{{ $item->thumbnail_url }}"
                     alt="{{ $item->alt ?? $item->name }}"
                     title="{{ $item->name }}"
                     loading="lazy"
                     style="width:44px;height:44px;object-fit:cover;border-radius:.375rem;display:block;background:#f5f5f4;">
            @endif
        @endforeach

        @if ($remaining > 0)
            <span style="width:44px;height:44px;border-radius:.375rem;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600;background:var(--gray-100);color:var(--gray-600);">
                +{{ $remaining }}
            </span>
        @endif
    </div>
@endif
