<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Interfaces;

use Elabftw\Services\AdvancedSearchQuery\Collectors\InvalidFieldCollector;
use Elabftw\Services\AdvancedSearchQuery\Collectors\WhereCollector;
use Elabftw\Services\AdvancedSearchQuery\Grammar\MetadataField;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

interface VisitMetadataField
{
    public function VisitMetadataField(MetadataField $metadataField, VisitorParameters $parameters): InvalidFieldCollector | WhereCollector | int;
}
