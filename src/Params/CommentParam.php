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

final class CommentParam implements ParamInterface
{
    public function __construct(private string $content) {}

    public function getContent(): string
    {
        if ($this->content === '') {
            throw new ImproperActionException('Invalid comment.');
        }
        return $this->content;
    }
}
