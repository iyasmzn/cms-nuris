<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FaqResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->panelUser('Faq');
        $this->actingAs($this->user);
    }

    // ── List ──────────────────────────────────────────────────────

    public function test_list_page_can_render(): void
    {
        $faqs = Faq::factory()->count(3)->create();

        Livewire::test(ListFaqs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($faqs);
    }

    public function test_list_page_can_search(): void
    {
        $visible = Faq::factory()->create(['question' => 'Kapan pendaftaran dibuka?']);
        $hidden = Faq::factory()->create(['question' => 'Apakah tersedia asrama?']);

        Livewire::test(ListFaqs::class)
            ->searchTable('pendaftaran')
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    // ── Create ────────────────────────────────────────────────────

    public function test_create_page_can_render(): void
    {
        Livewire::test(CreateFaq::class)
            ->assertSuccessful();
    }

    public function test_can_create_faq(): void
    {
        Livewire::test(CreateFaq::class)
            ->fillForm([
                'question' => 'Berapa biaya pendaftaran?',
                'answer' => '<p>Biaya pendaftaran berbeda untuk tiap jenjang.</p>',
                'category' => 'Biaya',
                'is_published' => true,
                'sort_order' => 2,
            ])
            ->call('create')
            ->assertNotified()
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Faq::class, [
            'question' => 'Berapa biaya pendaftaran?',
            'category' => 'Biaya',
            'is_published' => true,
            'sort_order' => 2,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        Livewire::test(CreateFaq::class)
            ->fillForm([
                'question' => null,
                'answer' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'question' => 'required',
                'answer',
            ])
            ->assertNotNotified();
    }

    // ── Edit ──────────────────────────────────────────────────────

    public function test_edit_page_can_render(): void
    {
        $faq = Faq::factory()->create();

        Livewire::test(EditFaq::class, ['record' => $faq->id])
            ->assertSuccessful()
            ->assertFormSet([
                'question' => $faq->question,
                'category' => $faq->category,
            ]);
    }

    public function test_can_edit_faq(): void
    {
        $faq = Faq::factory()->create();

        Livewire::test(EditFaq::class, ['record' => $faq->id])
            ->fillForm(['question' => 'Pertanyaan Diperbarui?', 'is_published' => false])
            ->call('save')
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Faq::class, [
            'id' => $faq->id,
            'question' => 'Pertanyaan Diperbarui?',
            'is_published' => false,
        ]);
    }

    // ── Model / Factory ───────────────────────────────────────────

    public function test_factory_creates_valid_records(): void
    {
        $faq = Faq::factory()->create();

        $this->assertDatabaseHas(Faq::class, ['id' => $faq->id]);
        $this->assertNotEmpty($faq->question);
        $this->assertNotEmpty($faq->answer);
    }

    public function test_published_scope_returns_only_published_in_sort_order(): void
    {
        $second = Faq::factory()->create(['is_published' => true, 'sort_order' => 2]);
        $first = Faq::factory()->create(['is_published' => true, 'sort_order' => 1]);
        Faq::factory()->create(['is_published' => false, 'sort_order' => 0]);

        $published = Faq::published()->get();

        $this->assertCount(2, $published);
        $this->assertSame([$first->id, $second->id], $published->pluck('id')->all());
    }
}
