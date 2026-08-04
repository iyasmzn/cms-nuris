<?php

namespace App\Http\Controllers;

use App\Models\AlumniStat;
use App\Models\AlumniUniversity;
use App\Models\ContentSection;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Greeting;
use App\Models\Media;
use App\Models\Post;
use App\Models\Program;
use App\Models\Slide;
use App\Models\Stat;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // 1 featured (first) + up to 6 in the grid
        $posts = Post::published()
            ->latest('published_at')
            ->limit(7)
            ->get();

        $stats = Stat::ordered()->get();
        $alumniStats = AlumniStat::ordered()->get();
        $alumniUniversities = AlumniUniversity::active()->get();
        $slides = Slide::active()->get();
        $greetings = Greeting::published()->get();
        $testimonials = Testimonial::published()->get();
        $faqs = Faq::published()->get();
        $contentSections = ContentSection::published()->get();
        $contactItems = contact_items();

        $upcomingEvents = Event::published()
            ->upcoming()
            ->ordered()
            ->limit(3)
            ->get();

        $programs = Program::published()
            ->ordered()
            ->limit(6)
            ->get();

        $galleryMedia = Media::inGallery()->limit(6)->get();

        return view('welcome', compact(
            'posts', 'stats', 'slides', 'greetings', 'testimonials', 'faqs', 'contactItems',
            'contentSections',
            'alumniStats', 'alumniUniversities',
            'upcomingEvents', 'programs',
            'galleryMedia',
        ));
    }
}
