<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ParamInterface;

final class TagParam implements ParamInterface
{
    protected const int MIN_CONTENT_SIZE = 1;

    public function __construct(private string $content) {}

    /**
     * Sanitize tag, we remove '\' because it messes up the javascript if you have this in the tags
     * also remove | because we use this as separator for tags in SQL
     */
    public function getContent(): string
    {
        $tag = trim(str_replace(array('\\', '|'), array('', ' '), $this->content));
        // empty tags are disallowed
        if (mb_strlen($tag) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), self::MIN_CONTENT_SIZE));
        }
        return $tag;
    }
}
