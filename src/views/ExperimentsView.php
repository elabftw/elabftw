<?php
/**
 * \Elabftw\Elabftw\ExperimentsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use DateTime;

/**
 * Experiments View
 */
class ExperimentsView
{
    /** @var Experiments $Entity our Experiments instance */
    public $Entity;

    /** @var TeamGroups $TeamGroups instance of TeamGroups */
    public $TeamGroups;

    /**
     * Need an instance of Experiments
     *
     * @param Experiments $entity
     */
    public function __construct(Experiments $entity)
    {
        $this->Entity = $entity;
        $this->TeamGroups = new TeamGroups($this->Entity->Users);
    }

    /**
     * If int, get the name of the team group instead of a number
     *
     * @return string
     */
    public function getVisibility(): string
    {
        if (Tools::checkId((int) $this->Entity->entityData['visibility']) !== false) {
            return $this->TeamGroups->readName($this->Entity->entityData['visibility']);
        }
        return ucfirst($this->Entity->entityData['visibility']);
    }

    /**
     * Show info on timestamp
     *
     * @return string
     */
    public function showTimestamp(): string
    {
        $UploadsView = new UploadsView($this->Entity->Uploads);
        $timestamper = $this->Entity->Users->read($this->Entity->entityData['timestampedby']);

        $UploadsView->Uploads->Entity->type = 'exp-pdf-timestamp';
        $pdf = $UploadsView->Uploads->readAll();

        $UploadsView->Uploads->Entity->type = 'timestamp-token';
        $token = $UploadsView->Uploads->readAll();

        // set correct type back
        $UploadsView->Uploads->Entity->type = 'experiments';

        $date = new DateTime($this->Entity->entityData['timestampedwhen']);

        return Tools::displayMessage(
            _('Experiment was timestamped by') . ' ' . $timestamper['fullname'] . " " . _('on') .
            " " . $date->format('Y-m-d') . " " . _('at') . " " .
            $date->format('H:i:s') . " " .
            $date->getTimezone()->getName() . " <a class='elab-tooltip' href='app/download.php?f=" .
            $pdf[0]['long_name'] . "'><span>" . _('Download timestamped pdf') . "</span><i class='fas fa-file-pdf'></i></a> <a class='elab-tooltip' href='app/download.php?f=" . $token[0]['long_name'] .
            "'><span>" . _('Download token') . "</span><i class='fas fa-download'></i></a> <a href='#' class='elab-tooltip'><span>" .
            _('Decode token') . "</span><i class='fas fa-info-circle decode-asn1' data-token='" . $token[0]['long_name'] .
            "' data-id='" . $this->Entity->entityData['id'] . "'></i></a><div style='color:black;overflow:auto;display:hidden' id='decodedDiv'></div>",
            'ok',
            false
        );
    }
}
