<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

use Elabftw\Elabftw\PermissionsHelper;

use function array_intersect_key;
use function array_keys;
use function array_unique;
use function array_values;
use function implode;
use function preg_grep;
use function preg_quote;
use function str_replace;

final class VisibilityFieldHelper
{
    public string $possibleInput = '';

    public function __construct(private string $userInput) {}

    public function getArr(): array
    {
        $visArr = (new PermissionsHelper())->getExtendedSearchAssociativeArray();
        // ToDo: add back team groups, add teams and users
        $this->possibleInput = "'" . implode("', '", array_keys($visArr)) . "'";

        // Emulate SQL LIKE search functionality so the user can use the same placeholders
        $pattern = '/^' . str_replace(array('%', '_'), array('.*', '.'), preg_quote($this->userInput, '/')) . '$/i';
        // Filter visibility entries based on user input
        $filteredArr = preg_grep($pattern, array_keys($visArr)) ?: array();

        // Return a unique list of visibility entries that can be used in the SQL where clause
        return array_unique(array_intersect_key(array_values($visArr), $filteredArr));
    }
}
