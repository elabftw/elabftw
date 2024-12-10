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
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Filter;

use function mb_strlen;

class ContentParams implements ContentParamsInterface
{
    protected const int MIN_CONTENT_SIZE = 1;

    public function __construct(protected string $target, protected string $content) {}

    public function getUnfilteredContent(): string
    {
        return $this->content;
    }

    // maybe rename to something else, so we have getContent to get filtered content and this would be get nonemptystring
    public function getContent(): mixed
    {
        // check for length
        if (mb_strlen($this->content) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), self::MIN_CONTENT_SIZE));
        }
        return $this->content;
    }

    public function getColumn(): string
    {
        return $this->getTarget();
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    protected function getBody(): string
    {
        return Filter::body($this->content);
    }

    protected function getBinary(): int
    {
        return Filter::toBinary($this->content);
    }

    protected function getInt(): int
    {
        return (int) $this->content;
    }

    protected function getIntOrNull(): ?int
    {
        return $this->getInt() === 0 ? null : $this->getInt();
    }

    protected function getUrl(): string
    {
        if (filter_var($this->content, FILTER_VALIDATE_URL) === false) {
            throw new ImproperActionException('Invalid URL format.');
        }
        return $this->content;
    }
}
