<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\EntityType;

final class EntitySlug
{
    public function __construct(public readonly EntityType $type, public readonly int $id) {}

    public static function fromString(string $stringSlug): self
    {
        [$type, $id] = explode(':', $stringSlug);
        return new self(EntityType::from($type), (int) $id);
    }
}
