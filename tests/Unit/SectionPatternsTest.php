<?php

namespace Tests\Unit;

use App\Models\ContentSection;
use App\Support\SectionPatterns;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Daftar pilihan pola di panel dan gambar ubinnya tinggal di dua berkas
 * terpisah. Kalau keduanya berbeda, admin bisa memilih pola yang tidak pernah
 * tergambar — dan tidak ada pesan galat apa pun yang memberitahunya.
 */
class SectionPatternsTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function patternProvider(): array
    {
        $patterns = array_diff(array_keys(ContentSection::BACKGROUND_PATTERNS), ['none']);

        return array_combine(
            $patterns,
            array_map(static fn (string $pattern): array => [$pattern], $patterns),
        );
    }

    #[DataProvider('patternProvider')]
    public function test_every_choice_offered_in_the_panel_has_a_tile(string $pattern): void
    {
        $this->assertTrue(
            SectionPatterns::exists($pattern),
            "Pola '{$pattern}' ada di daftar pilihan tapi tidak punya ubin SVG.",
        );

        $this->assertGreaterThan(0, SectionPatterns::width($pattern));
        $this->assertGreaterThan(0, SectionPatterns::height($pattern));
    }

    #[DataProvider('patternProvider')]
    public function test_every_tile_is_valid_svg_wrapped_in_a_css_url(string $pattern): void
    {
        $url = SectionPatterns::maskUrl($pattern);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('url(data:image/svg+xml,', $url);
        // Kutip ganda di sini akan menutup atribut style="..." lebih awal
        $this->assertStringNotContainsString('"', $url);

        $svg = rawurldecode(substr($url, strlen('url(data:image/svg+xml,'), -1));

        $previous = libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($svg);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($parsed, "Ubin '{$pattern}' bukan SVG yang sah.");
        $this->assertSame('svg', $parsed->getName());
    }

    #[DataProvider('patternProvider')]
    public function test_every_choice_carries_a_preview_of_its_own_tile(string $pattern): void
    {
        $options = SectionPatterns::selectOptions();

        $this->assertArrayHasKey($pattern, $options);
        $this->assertStringContainsString(SectionPatterns::maskUrl($pattern), $options[$pattern]);
        $this->assertStringContainsString(ContentSection::BACKGROUND_PATTERNS[$pattern], $options[$pattern]);
    }

    public function test_the_no_pattern_choice_keeps_an_empty_preview_box(): void
    {
        $none = SectionPatterns::selectOptions()['none'];

        $this->assertStringContainsString('Tanpa Pola', $none);
        $this->assertStringNotContainsString('mask-image', $none);
    }

    public function test_the_choices_match_the_list_shown_in_the_panel(): void
    {
        $this->assertSame(
            array_keys(ContentSection::BACKGROUND_PATTERNS),
            array_keys(SectionPatterns::selectOptions()),
        );
    }

    public function test_an_unknown_pattern_has_no_tile(): void
    {
        $this->assertFalse(SectionPatterns::exists('entah'));
        $this->assertNull(SectionPatterns::maskUrl('entah'));
        $this->assertSame(0, SectionPatterns::width('entah'));
    }
}
