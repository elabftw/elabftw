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

use Elabftw\Interfaces\StatusParamsInterface;
use Elabftw\Services\Check;

final class StatusParams extends ContentParams implements StatusParamsInterface
{
    public function __construct(string $content, private string $color, private bool $isDefault = false)
    {
        parent::__construct($content);
        $this->content = $content;
    }

    public function getColor(): string
    {
        return Check::color($this->color);
    }

    public function getIsDefault(): int
    {
        return (int) $this->isDefault;
    }
}
