<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use function mt_rand;
use function sprintf;
use function array_map;
use function hexdec;
use function ltrim;
use function str_split;

trait RandomColorTrait
{
    protected const string DEFAULT_BLUE = '29aeb9';

    protected const string DEFAULT_GREEN = '54aa08';

    protected const string DEFAULT_GRAY = 'c0c0c0';

    protected const string DEFAULT_RED = 'c24f3d';

    // WCAG 2 AAA minimum contrast ratio for normal text
    protected const float AAA_CONTRAST_RATIO = 7.0;

    /**
     * Get a color that is dark but not too dark
     */
    protected function getRandomDarkColor(): string
    {
        return sprintf(
            '#%06X',
            (mt_rand(0, 0xFFFFFF) & 0x9F9F9F) | 0x202020
        );
    }

    /**
     * Get a random dark color that reaches WCAG AAA contrast (7:1) with white text
     * The hue is kept: the color is only darkened until it is readable
     */
    protected function getRandomAaaColor(): string
    {
        $rgb = array_map(
            static fn(string $channel): int => (int) hexdec($channel),
            str_split(ltrim($this->getRandomDarkColor(), '#'), 2),
        );
        // terminates because black has a 21:1 contrast ratio with white
        while (self::getContrastRatioWithWhite($rgb) < self::AAA_CONTRAST_RATIO) {
            $rgb = array_map(static fn(int $channel): int => (int) ((float) $channel * 0.9), $rgb);
        }
        return sprintf('#%02X%02X%02X', ...$rgb);
    }

    /**
     * WCAG 2 contrast ratio between an sRGB color and white
     *
     * @param array<int> $rgb red, green and blue channels (0-255)
     */
    protected static function getContrastRatioWithWhite(array $rgb): float
    {
        $linear = array_map(static function (int $channel): float {
            $c = (float) $channel / 255.0;
            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $rgb);
        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
        return 1.05 / ($luminance + 0.05);
    }
}
