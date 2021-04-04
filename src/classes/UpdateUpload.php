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

use Elabftw\Interfaces\UpdateUploadParamsInterface;

abstract class UpdateUpload implements UpdateUploadParamsInterface
{
    public string $action;

    protected string $target;

    private int $id;

    public function __construct(JsonProcessor $payload)
    {
        $this->id = $payload->id;
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
