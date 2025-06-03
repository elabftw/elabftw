<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use DateTimeImmutable;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Orderby;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\UserRequestActions;
use Elabftw\Params\DisplayParams;
use Override;
use Symfony\Component\HttpFoundation\InputBag;

use function array_merge;

/**
 * For dashboard.php
 */
final class DashboardController extends AbstractHtmlController
{
    private const int SHOWN_NUMBER = 5;

    #[Override]
    protected function getTemplate(): string
    {
        return 'dashboard.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Dashboard');
    }

    #[Override]
    protected function getData(): array
    {
        $DisplayParamsExp = new DisplayParams(
            $this->app->Users,
            EntityType::Experiments,
            limit: self::SHOWN_NUMBER,
            orderby: Orderby::Lastchange,
        );
        $Experiments = new Experiments($this->app->Users);
        $Items = new Items($this->app->Users);
        $Templates = new Templates($this->app->Users);
        $ItemsTypes = new ItemsTypes($this->app->Users);
        $now = new DateTimeImmutable();
        $Scheduler = new Scheduler($Items, start: $now->format(DateTimeImmutable::ATOM));
        // for items we need to create a new DisplayParams object, otherwise the scope setting will also apply here
        $DisplayParamsItems = new DisplayParams(
            $this->app->Users,
            EntityType::Items,
            limit: self::SHOWN_NUMBER,
            orderby: Orderby::Lastchange,
        );
        $PermissionsHelper = new PermissionsHelper();
        $ExperimentsCategory = new ExperimentsCategories(new Teams($this->app->Users));
        $ExperimentsStatus = new ExperimentsStatus(new Teams($this->app->Users));
        $ItemsStatus = new ItemsStatus(new Teams($this->app->Users));
        $UserRequestActions = new UserRequestActions($this->app->Users);

        return array_merge(
            parent::getData(),
            array(
                'bookingsArr' => $Scheduler->readAll(),
                'itemsCategoryArr' => $ItemsTypes->readAll(),
                'itemsStatusArr' => $ItemsStatus->readAll(),
                'experimentsArr' => $Experiments->readShow($DisplayParamsExp),
                'experimentsCategoryArr' => $ExperimentsCategory->readAll($ExperimentsCategory->getQueryParams(new InputBag(array('limit' => 9999)))),
                'experimentsStatusArr' => $ExperimentsStatus->readAll($ExperimentsStatus->getQueryParams(new InputBag(array('limit' => 9999)))),
                'itemsArr' => $Items->readShow($DisplayParamsItems),
                'requestActionsArr' => $UserRequestActions->readAllFull(),
                'templatesArr' => $Templates->Pins->readAll(),
                'usersArr' => $this->app->Users->readAllActiveFromTeam(),
                'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
            ),
        );
    }
}
