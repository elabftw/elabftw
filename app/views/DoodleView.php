<?php
/**
 * \Elabftw\Elabftw\DoodleView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Experiments View
 */
class DoodleView
{
    /** instance of Entity */
    private $Entity;

    /**
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->Entity = $entity;
    }

    /**
     * Generate html for the doodle form
     *
     * @return string
     */
    public function buildDoodle()
    {
        $html = "<section class='box'>";
        $html .= "<img src='app/img/pencil.png' /> ";
        $html .= "<h3 style='display:inline' class='clickable' onclick=\"$('.canvasDiv').toggle();\">" . _('Draw something') . "</h3><br><br>";
        $html .= "<div class='canvasDiv' style='display:none'>";
        $html .= "<canvas id='doodleCanvas' width='700px' height='600px'></canvas>";
        $html .= "<button class='button button-delete' onClick='clearCanvas()'>" . _('Clear') . "</button>";
        $html .= "<button class='button' onClick='saveCanvas(" . $this->Entity->id . ")'>" . _('Save') . "</button>";
        $html .= "</div></section>";
        $html .= "<script src='app/js/doodle.js'></script>";

        return $html;
    }
}
