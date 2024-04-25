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
use ValueError;

/**
 * Parameters passed for ordering extra fields
 */
class ExtraFieldsOrderingParams extends OrderingParams
{
    public readonly int $id;

    public readonly EntityType $type;

    public function __construct(protected array $reqBody)
    {
        parent::__construct($reqBody);
        $this->id = (int) $this->reqBody['entity']['id'];
        $this->type = EntityType::tryFrom($this->reqBody['entity']['type'] ?? '') ?? throw new ValueError('Incorrect type value');
    }

    /**
     * Nothing to clean up here
     */
    protected function cleanup(array $ordering): array
    {
        return $ordering;
    }
}
