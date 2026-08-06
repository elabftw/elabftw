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

trait RandomColorTrait
{
    protected const string DEFAULT_BLUE = '29aeb9';

    protected const string DEFAULT_GREEN = '54aa08';

    protected const string DEFAULT_GRAY = 'c0c0c0';

    protected const string DEFAULT_RED = 'c24f3d';

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
}
