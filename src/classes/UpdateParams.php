<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use function mb_strlen;

class UpdateParams
{
    protected const MIN_CONTENT_SIZE = 2;

    public string $action;

    protected int $id;

    protected string $content;

    // an update action always has an id and content at least required
    public function __construct(int $id, string $content)
    {
        $this->id = $id;
        $this->action = 'update';
        $this->content = $content;
    }

    public function getContent(): string
    {
        // check for length
        $c = Filter::sanitize($this->content);
        if (mb_strlen($c) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        return $c;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getColor(): string
    {
        return 'Nope';
    }

    public function getIsTimestampable(): int
    {
        return 0;
    }

    public function getIsDefault(): int
    {
        return 0;
    }

    public function getTarget(): string
    {
        return 'Nope';
    }
}
