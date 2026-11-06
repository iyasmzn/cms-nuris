<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Support\LandingBlocks;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::published()->ordered()->get();

        $appName = config('app.name');

        $seo = [
            'title' => "Program Unggulan | {$appName}",
            'description' => "Berbagai program unggulan di {$appName} yang membentuk santri berprestasi dan berakhlak mulia.",
            'canonical' => route('programs.index'),
        ];

        return view('programs.index', compact('programs', 'seo'));
    }

    public function show(Program $program): View
    {
        abort_unless($program->is_published, 404);

        $related = Program::published()
            ->where('id', '!=', $program->id)
            ->ordered()
            ->limit(3)
            ->get();

        // Blok data dibekali isinya; blok Program tidak ikut mendaftar program ini sendiri
        $blocks = LandingBlocks::prepare($program->blocks, currentProgram: $program);

        $seo = [
            'title' => "{$program->title} | ".config('app.name'),
            'description' => $program->seoDescription($blocks),
            'canonical' => route('programs.show', $program),
            'og_image' => $program->thumbnail_url,
        ];

        return view('programs.show', compact('program', 'blocks', 'related', 'seo'));
    }
}
