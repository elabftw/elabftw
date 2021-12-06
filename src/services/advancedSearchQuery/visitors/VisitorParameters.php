<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

class VisitorParameters
{
    public function __construct(private array $parameters)
    {
    }

    public function getColumn(): string
    {
        return $this->parameters['column'];
    }

    public function getEntityType(): string
    {
        return $this->parameters['entityType'];
    }

    public function getVisArr(): array
    {
        return $this->parameters['visArr'];
    }
}
