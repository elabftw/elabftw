<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Collectors;

class InvalidFieldCollector
{
    public function __construct(private ?array $fieldErrors = null)
    {
    }

    public function getfieldErrors(): array
    {
        return $this->fieldErrors ?? array();
    }
}
