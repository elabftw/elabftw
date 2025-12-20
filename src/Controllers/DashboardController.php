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
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
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
    private const int SHOWN_NUMBER = 6;

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
            orderby: Orderby::Lastchange,
            limit: self::SHOWN_NUMBER,
        );
        $Experiments = new Experiments($this->app->Users);
        $Items = new Items($this->app->Users);
        $ItemsTypes = new ItemsTypes($this->app->Users);
        $Templates = new Templates($this->app->Users);
        $now = new DateTimeImmutable();
        $Scheduler = new Scheduler($Items, start: $now->format(DateTimeImmutable::ATOM));
        // for items we need to create a new DisplayParams object, otherwise the scope setting will also apply here
        $DisplayParamsItems = new DisplayParams(
            $this->app->Users,
            EntityType::Items,
            orderby: Orderby::Lastchange,
            limit: self::SHOWN_NUMBER,
        );
        $PermissionsHelper = new PermissionsHelper();
        $ExperimentsStatus = new ExperimentsStatus($this->app->Teams);
        $ItemsStatus = new ItemsStatus($this->app->Teams);
        $UserRequestActions = new UserRequestActions($this->app->Users);

        $DisplayParamsTemplates = new DisplayParams($this->app->Users, EntityType::Templates);
        $DisplayParamsItemsTypes = new DisplayParams($this->app->Users, EntityType::ItemsTypes);

        return array_merge(
            parent::getData(),
            array(
                'bookingsArr' => $Scheduler->readAll(),
                'itemsStatusArr' => $ItemsStatus->readAll(),
                'experimentsArr' => $Experiments->readShow($DisplayParamsExp),
                'experimentsStatusArr' => $ExperimentsStatus->readAll($ExperimentsStatus->getQueryParams(new InputBag(array('limit' => 9999)))),
                'itemsArr' => $Items->readShow($DisplayParamsItems),
                'itemsTemplatesArr' => $ItemsTypes->readAllSimple($DisplayParamsItemsTypes),
                'requestActionsArr' => $UserRequestActions->readAllFull(),
                'templatesArr' => $Templates->readAllSimple($DisplayParamsTemplates),
                'usersArr' => $this->app->Users->readAllActiveFromTeam(),
                'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
            ),
        );
    }
}
