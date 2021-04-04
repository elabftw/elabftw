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
    protected string $target;

    private int $id;

    private int $entityId;

    public function __construct(PayloadProcessor $payload)
    {
        $this->id = $payload->id;
        $this->entityId = $payload->Entity->id;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }
}
