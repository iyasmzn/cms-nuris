<?php

namespace Tests\Unit;

use App\Support\SectionBackground;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Warna pola berakhir sebagai nilai mentah di dalam atribut `style`, jadi
 * apa pun yang lolos dari penyaring hex bisa menyuntikkan CSS. Kolomnya di
 * basis data memang pendek, tapi nilai yang sama juga datang dari settings
 * seksi bawaan yang tidak punya batas panjang itu.
 */
class SectionBackgroundColorTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function rejectedProvider(): array
    {
        return [
            'kata biasa' => ['red'],
            'tanpa pagar' => ['ff8800'],
            'sisipan css' => ['#fff;background:url(https://jahat.test/x.png)'],
            'kurung fungsi' => ['url(javascript:alert(1))'],
            'panjang ganjil' => ['#ff88'.'0'],
            'kosong' => [''],
            'spasi saja' => ['   '],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function test_a_custom_colour_that_is_not_a_hex_falls_back_to_the_theme(string $value): void
    {
        $background = new SectionBackground(
            mode: 'base',
            pattern: 'dots',
            patternColor: 'custom',
            patternCustomColor: $value,
        );

        $this->assertSame('var(--primary)', $background->patternColorCss());
    }

    /** @return array<string, array{string}> */
    public static function acceptedProvider(): array
    {
        return [
            'tiga digit' => ['#fa0'],
            'enam digit' => ['#ff8800'],
            'delapan digit dengan alfa' => ['#ff8800cc'],
            'huruf besar' => ['#FF8800'],
        ];
    }

    #[DataProvider('acceptedProvider')]
    public function test_a_real_hex_is_used_as_written(string $value): void
    {
        $background = new SectionBackground(
            mode: 'base',
            pattern: 'dots',
            patternColor: 'custom',
            patternCustomColor: $value,
        );

        $this->assertSame($value, $background->patternColorCss());
    }
}
