<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ParamInterface;

final class TagParam implements ParamInterface
{
    public function __construct(private string $content)
    {
    }

    /**
     * Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
     * also remove | because we use this as separator for tags in SQL
     */
    public function getContent(): string
    {
        $tag = filter_var($this->content, FILTER_SANITIZE_STRING);
        if ($tag === false) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 1));
        }
        $tag = trim(str_replace(array('\\', '|'), array('', ' '), $tag));
        // empty tags are disallowed
        if ($tag === '') {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 1));
        }
        return $tag;
    }
}
