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
use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Services\Check;
use function mb_strlen;

final class UpdateStatus implements UpdateParamsInterface
{
    private const MIN_CONTENT_SIZE = 2;

    public string $action;

    private string $content;

    private int $id;

    private string $color;

    private bool $isTimestampable;

    private bool $isDefault;

    public function __construct(int $id, string $content, string $color, bool $isTimestampable = false, bool $isDefault = false)
    {
        $this->id = $id;
        $this->content = $content;
        $this->action = 'update';
        $this->color = $color;
        $this->isTimestampable = $isTimestampable;
        $this->isDefault = $isDefault;
    }

    public function getContent(): string
    {
        // check for length
        if (mb_strlen($this->content) < self::MIN_CONTENT_SIZE) {
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

    public function getColor(): string
    {
        return Check::color($this->color);
    }

    public function getIsTimestampable(): int
    {
        return (int) $this->isTimestampable;
    }

    public function getIsDefault(): int
    {
        return (int) $this->isDefault;
    }
}
