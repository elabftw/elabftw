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
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Orderby;
use Elabftw\Interfaces\ControllerInterface;
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
use Symfony\Component\HttpFoundation\Response;

/**
 * For dashboard.php
 */
class DashboardController implements ControllerInterface
{
    private const int SHOWN_NUMBER = 5;

    public function __construct(private App $App) {}

    public function getResponse(): Response
    {
        $template = 'dashboard.html';

        $DisplayParamsExp = new DisplayParams($this->App->Users, $this->App->Request, EntityType::Experiments);
        $DisplayParamsExp->limit = self::SHOWN_NUMBER;
        $DisplayParamsExp->orderby = Orderby::Lastchange;
        $Experiments = new Experiments($this->App->Users);
        $Items = new Items($this->App->Users);
        $Templates = new Templates($this->App->Users);
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $now = new DateTimeImmutable();
        $Scheduler = new Scheduler($Items, null, $now->format(DateTimeImmutable::ATOM));
        // for items we need to create a new DisplayParams object, otherwise the scope setting will also apply here
        $DisplayParamsItems = new DisplayParams($this->App->Users, $this->App->Request, EntityType::Items);
        $DisplayParamsItems->limit = self::SHOWN_NUMBER;
        $DisplayParamsItems->orderby = Orderby::Lastchange;
        $PermissionsHelper = new PermissionsHelper();
        $ExperimentsCategory = new ExperimentsCategories(new Teams($this->App->Users));
        $ExperimentsStatus = new ExperimentsStatus(new Teams($this->App->Users));
        $ItemsStatus = new ItemsStatus(new Teams($this->App->Users));
        $UserRequestActions = new UserRequestActions($this->App->Users);

        $renderArr = array(
            'bookingsArr' => $Scheduler->readAll(),
            'itemsCategoryArr' => $ItemsTypes->readAll(),
            'itemsStatusArr' => $ItemsStatus->readAll(),
            'experimentsArr' => $Experiments->readShow($DisplayParamsExp),
            'experimentsCategoryArr' => $ExperimentsCategory->readAll(),
            'experimentsStatusArr' => $ExperimentsStatus->readAll(),
            'itemsArr' => $Items->readShow($DisplayParamsItems),
            'requestActionsArr' => $UserRequestActions->readAllFull(),
            'templatesArr' => $Templates->Pins->readAllSimple(),
            'usersArr' => $this->App->Users->readAllActiveFromTeam(),
            'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }
}
