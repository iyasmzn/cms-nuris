<?php

namespace Tests\Unit;

use App\Support\NavItemStyle;
use PHPUnit\Framework\TestCase;

class NavItemStyleTest extends TestCase
{
    /**
     * Menu saved before this setting existed carry no style at all, and menus can
     * also arrive from an import — both have to land on a plain link.
     */
    public function test_an_unknown_or_missing_style_becomes_a_plain_link(): void
    {
        $this->assertSame('link', NavItemStyle::sanitize(null));
        $this->assertSame('link', NavItemStyle::sanitize('nav-item-button" onclick="alert(1)'));
        $this->assertSame('button', NavItemStyle::sanitize('button'));
    }

    public function test_the_style_maps_to_its_css_class(): void
    {
        $this->assertSame('', NavItemStyle::cssClass('link'));
        $this->assertSame('nav-item-button', NavItemStyle::cssClass('button'));
        $this->assertSame('nav-item-outline', NavItemStyle::cssClass('outline'));
    }

    /**
     * A chosen background needs a darker partner for hover: CSS cannot ask whether
     * a custom property is set, so the pair has to be written out here.
     */
    public function test_a_chosen_background_brings_its_own_hover_shade(): void
    {
        $vars = NavItemStyle::cssVars(['text_color' => '#FFFFFF', 'bg_color' => '#e11d48']);

        $this->assertStringContainsString('--item-text:#ffffff', $vars);
        $this->assertStringContainsString('--item-bg:#e11d48', $vars);
        $this->assertStringContainsString('--item-bg-hover:color-mix(in oklab, #e11d48 88%, black)', $vars);
    }

    /**
     * The declarations are written straight into a `style` attribute, so anything
     * that is not a HEX colour must produce nothing at all.
     */
    public function test_a_colour_that_is_not_hex_produces_no_declaration(): void
    {
        $this->assertSame('', NavItemStyle::cssVars(['text_color' => 'red; background: url(x)']));
        $this->assertSame('', NavItemStyle::cssVars([]));
    }

    public function test_it_cleans_the_colours_of_submenu_entries_too(): void
    {
        $items = NavItemStyle::sanitizeItems([
            [
                'label' => 'SPMB',
                'style' => 'button',
                'bg_color' => '#E11D48',
                'children' => [
                    ['label' => 'Jalur Prestasi', 'text_color' => 'javascript:alert(1)'],
                ],
            ],
        ]);

        $this->assertSame('#e11d48', $items[0]['bg_color']);
        $this->assertSame('SPMB', $items[0]['label']);
        $this->assertSame('', $items[0]['children'][0]['text_color']);
    }
}
