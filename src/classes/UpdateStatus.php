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

use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Services\Check;

final class UpdateStatus extends UpdateParams implements UpdateParamsInterface
{
    private string $color;

    private bool $isTimestampable;

    private bool $isDefault;

    public function __construct(int $id, string $content, string $color, bool $isTimestampable = false, bool $isDefault = false)
    {
        parent::__construct($id, $content);
        $this->content = $content;
        $this->color = $color;
        $this->isTimestampable = $isTimestampable;
        $this->isDefault = $isDefault;
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
