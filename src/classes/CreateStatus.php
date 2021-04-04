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

use Elabftw\Interfaces\CreateStatusParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

final class CreateStatus implements CreateStatusParamsInterface
{
    public string $action;

    private string $content;

    private string $color;

    private bool $isTimestampable;

    private bool $isDefault;

    public function __construct(string $content, string $color, bool $isTimestampable = false, bool $isDefault = false)
    {
        $this->action = 'create';
        $this->content = $content;
        $this->color = $color;
        $this->isTimestampable = $isTimestampable;
        $this->isDefault = $isDefault;
    }

    public function getContent(): string
    {
        // TODO check for length after sanitize
        return Filter::sanitize($this->content);
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
