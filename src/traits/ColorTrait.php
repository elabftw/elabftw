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

use Elabftw\Exceptions\ImproperActionException;

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
        $color = filter_var(substr($color, 1, 7), FILTER_SANITIZE_STRING);
        if ($color === false || \mb_strlen($color) !== 6) {
            throw new ImproperActionException('Bad color');
        }
        return $color;
    }
}
