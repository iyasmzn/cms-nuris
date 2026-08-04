<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type'); // null | 'foto' | 'video'
        $album = $request->query('album');

        $items = Media::inGallery()
            ->when($type === 'foto', fn (Builder $q) => $q->whereNull('embed_provider')
                ->where('mime_type', 'like', 'image/%'))
            ->when($type === 'video', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->whereNotNull('embed_provider')
                ->orWhere('mime_type', 'like', 'video/%')))
            ->when(filled($album), fn (Builder $q) => $q->whereRelation('album', 'name', $album))
            ->with('album')
            ->paginate(24)
            ->withQueryString();

        $albums = Album::query()
            ->whereHas('media', fn (Builder $q) => $q->where('show_in_gallery', true))
            ->orderBy('name')
            ->pluck('name');

        $appName = setting('site_name', config('app.name'));

        $seo = [
            'title' => "Galeri Foto & Video | {$appName}",
            'description' => "Galeri foto dan dokumentasi kegiatan {$appName}. Fasilitas, wisuda, dan berbagai momen berharga pesantren.",
            'canonical' => route('gallery.index', array_filter(['type' => $type, 'album' => $album])),
        ];

        return view('gallery.index', compact('items', 'albums', 'album', 'type', 'seo'));
    }
}
