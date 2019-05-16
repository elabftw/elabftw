<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Traits;

/**
 * For cleaning up the color (status and items types)
 */
trait ColorTrait
{
    /**
     * Get only the relevant part of the color: remove the #
     *
     * @param string $color #121212
     * @return string
     */
    private function checkColor($color): string
    {
        return filter_var(substr($color, 1, 7), FILTER_SANITIZE_STRING);
    }
}
