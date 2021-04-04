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
use Elabftw\Interfaces\UpdateCommentParamsInterface;
use function mb_strlen;

final class UpdateComment implements UpdateCommentParamsInterface
{
    private const MIN_COMMENT_SIZE = 2;

    public string $action;

    private string $content;

    private int $id;

    public function __construct(string $content, int $id)
    {
        $this->id = $id;
        $this->content = $content;
        $this->action = 'update';
    }

    public function getContent(): string
    {
        // check for length
        if (mb_strlen($this->content) < self::MIN_COMMENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        return $this->content;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTarget(): string
    {
        return 'nope';
    }
}
