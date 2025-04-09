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

trait RandomColorTrait
{
    private const string DEFAULT_BLUE = '29AEB9';

    private const string DEFAULT_GREEN = '54AA08';

    private const string DEFAULT_GRAY = 'C0C0C0';

    private const string DEFAULT_RED = 'C24F3D';

    /**
     * Get a color that is a good for background
     */
    protected function getSomeColor(): string
    {
        $colors = array(
            self::DEFAULT_BLUE,
            self::DEFAULT_GRAY,
            self::DEFAULT_GREEN,
            self::DEFAULT_RED,
            '0A0A0A',
            '0B3D91',
            '4A3F35',
            '3D0C02',
            '253529',
            '3B3C36',
            '483C32',
            '0F4C81',
            '4B0082',
            '2F4F4F',
            '321414',
            '3C1414',
        );
        $randomKey = array_rand($colors, 1);
        return $colors[$randomKey];
    }
}
