{{--
    Media grid thumbnail. Uploaded videos have no thumbnail image of their own,
    so the browser renders their first frame from the file itself (`#t=0.5`)
    unless a poster was uploaded manually.
--}}
@php
    $record = $getRecord();
    $height = $height ?? 160;
    $radius = $radius ?? '0';
    $box = "width:{$width};height:".(is_numeric($height) ? "{$height}px" : $height).";object-fit:cover;display:block;border-radius:{$radius};background:#f5f5f4;";

    $glyph = 'data:image/svg+xml,'.rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">'
        .'<rect width="80" height="80" rx="8" fill="#fffbeb"/>'
        .'<text x="40" y="50" font-size="32" text-anchor="middle">📄</text>'
        .'</svg>'
    );
@endphp

@if ($record->is_video && blank($record->embed_thumbnail_path))
    <video src="{{ $record->url }}#t=0.5"
           preload="metadata"
           muted
           playsinline
           style="{{ $box }}"></video>
@elseif ($record->is_image || $record->is_video || $record->is_embed)
    <img src="{{ $record->thumbnail_url }}"
         alt="{{ $record->alt ?? $record->name }}"
         loading="lazy"
         style="{{ $box }}">
@else
    <img src="{{ $glyph }}" alt="" style="{{ $box }}">
@endif
