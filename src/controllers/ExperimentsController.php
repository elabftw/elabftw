<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Models\Experiments;
use Elabftw\Models\Status;

/**
 * For experiments.php
 */
class ExperimentsController extends AbstractEntityController
{
    /** @var Experiments $Entity instance of Experiments */
    protected $Entity;

    /**
     * Constructor
     *
     * @param App $app
     * @param Experiments $entity
     */
    public function __construct(App $app, Experiments $entity)
    {
        parent::__construct($app, $entity);

        $Category = new Status($this->App->Users);
        $this->categoryArr = $Category->read();
    }

    /**
     * Get the results from main sql query with items to display
     */
    protected function getItemsArr(): array
    {
        // filter by user if we don't want to show the rest of the team
        if (!$this->Entity->Users->userData['show_team']) {
            $this->Entity->addFilter('entity.userid', $this->App->Users->userData['userid']);
        }

        $DisplayParams = new DisplayParams();
        $DisplayParams->adjust($this->App);
        return $this->Entity->readShow($DisplayParams);
    }
}
