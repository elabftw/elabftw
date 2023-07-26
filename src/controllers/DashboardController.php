<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use DateTimeImmutable;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Orderby;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Templates;
use Symfony\Component\HttpFoundation\Response;

/**
 * For dashboard.php
 */
class DashboardController implements ControllerInterface
{
    public function __construct(private App $App)
    {
    }

    public function getResponse(): Response
    {
        $template = 'dashboard.html';

        $DisplayParams = new DisplayParams($this->App->Users, $this->App->Request, EntityType::Experiments);
        $DisplayParams->limit = 5;
        $DisplayParams->orderby = Orderby::Lastchange;
        $Experiments = new Experiments($this->App->Users);
        $Items = new Items($this->App->Users);
        $Templates = new Templates($this->App->Users);
        $ItemsTypes = new ItemsTypes($this->App->Users);
        $now = new DateTimeImmutable();
        $Scheduler = new Scheduler($Items, null, $now->format(DateTimeImmutable::ATOM));
        $renderArr = array(
            'bookingsArr' => $Scheduler->readAll(),
            'categoryArr' => $ItemsTypes->readAll(),
            'experimentsArr' => $Experiments->readShow($DisplayParams),
            'itemsArr' => $Items->readShow($DisplayParams),
            'templatesArr' => $Templates->Pins->readAllSimple(),
        );
        $Response = new Response();
        $Response->prepare($this->App->Request);
        $Response->setContent($this->App->render($template, $renderArr));

        return $Response;
    }
}
