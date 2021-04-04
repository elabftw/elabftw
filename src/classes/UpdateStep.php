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

    protected int $id;

    // TODO deprecated because we use this->Entity->id
    private int $entityId;

    // TODO this could be a generic processor, or maybe a ProcessedParams class
    // because here we don't care where the params are coming from!
    public function __construct(JsonProcessor $payload)
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
