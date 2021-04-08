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
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UpdateParams
{
    protected const MIN_CONTENT_SIZE = 2;

    protected string $content;

    protected string $target = '';

    // an update action always has content at least required
    public function __construct(string $content)
    {
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

    public function getTarget(): string
    {
        return $this->target;
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

    public function getTitle(): string
    {
        return 'no';
    }

    public function getDate(): string
    {
        return 'no';
    }

    public function getBody(): string
    {
        return 'no';
    }

    public function getCanread(): string
    {
        return 'team';
    }

    public function getCanwriteS(): string
    {
        return 'team';
    }

    public function getIsBookable(): int
    {
        return 0;
    }

    public function getTeam(): int
    {
        return 0;
    }

    public function getFile(): UploadedFile
    {
        return new UploadedFile('a', 'b');
    }
}
