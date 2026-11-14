<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Models\TeamGroups;
use Override;

use function _;
use function array_merge;

final class CompoundsController extends AbstractHtmlController
{
    #[Override]
    protected function getTemplate(): string
    {
        return 'compounds.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Compounds');
    }

    // what the permissions modal of the compounds offers, like the one of the entries
    #[Override]
    protected function getData(): array
    {
        return array_merge(
            parent::getData(),
            array(
                'allTeamgroupsArr' => new TeamGroups($this->app->Users)->readAllEverything(),
                'teamsArr' => $this->app->Teams->readAllVisible(),
                'visibilityArr' => new PermissionsHelper()->getAssociativeArray(),
            ),
        );
    }
}
