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

use Elabftw\Interfaces\CreateItemTypeParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

final class CreateItemType implements CreateItemTypeParamsInterface
{
    private string $body;

    private string $canread;

    private string $canwrite;

    private string $color;

    private string $content;

    private int $isBookable;

    private ?int $team;

    public function __construct(string $content, string $color, string $body = '', string $canread = 'team', string $canwrite = 'team', int $isBookable = 0, int $team = null)
    {
        $this->body = $body;
        $this->canread = $canread;
        $this->canwrite = $canwrite;
        $this->color = $color;
        $this->content = $content;
        $this->isBookable = $isBookable;
        $this->team = $team;
    }

    public function getBody(): string
    {
        return Filter::body($this->body);
    }

    public function getCanread(): string
    {
        return Check::visibility($this->canread);
    }

    public function getCanwriteS(): string
    {
        return Check::visibility($this->canwrite);
    }

    public function getColor(): string
    {
        return Check::color($this->color);
    }

    public function getContent(): string
    {
        return Filter::title($this->content);
    }

    public function getIsBookable(): int
    {
        return $this->isBookable;
    }

    public function getTeam(): int
    {
        return $this->team;
    }
}
