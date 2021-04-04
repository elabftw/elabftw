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

class UpdateStep
{
    public string $action;

    protected string $target;

    protected int $id;

    // TODO this could be a generic processor, or maybe a ProcessedParams class
    // because here we don't care where the params are coming from!
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->action = 'update';
    }

    public function getTarget(): string
    {
        return $this->target;
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
}
