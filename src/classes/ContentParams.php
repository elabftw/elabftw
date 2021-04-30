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

class ContentParams implements ContentParamsInterface
{
    protected const MIN_CONTENT_SIZE = 1;

    protected string $content;

    protected string $target;

    public function __construct(string $content = '', string $target = '')
    {
        $this->content = $content;
        $this->target = $target;
    }

    public function getTarget(): string
    {
        return $this->target;
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

    public function getBody(): string
    {
        return Filter::body($this->content);
    }
}
