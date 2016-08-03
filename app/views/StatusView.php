<?php
/**
 * \Elabftw\Elabftw\StatusView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * HTML for the status
 */
class StatusView
{
    /** and instance of Status */
    private $Status;

    /**
     * Constructor
     *
     * @param Status $status
     */
    public function __construct(Status $status)
    {
        $this->Status = $status;
    }

    /**
     * Output HTML to display the create new status block
     *
     * @return string $html
     */
    public function showCreate()
    {
        $html = "<h3>" . _('Add a new status') . "</h3>";
        $html .= "<ul class='list-group'><li class='list-group-item center'>";
        $html .= "<ul class='list-inline'>";
        $html .= "<li>" . _('Name') . " <input type='text' id='statusName' /></li>";
        $html .= "<li>" . _('Color') . " <input class='colorpicker' type='text' id='statusColor' value='000000' /></li>";
        $html .= "<li><button onClick='statusCreate()' class='button'>" . _('Save') . "</button></li>";
        $html .= "</ul></li></ul>";

        return $html;
    }

    /**
     * Output HTML with all the status
     *
     * @return string $html
     */
    public function show()
    {
        $statusArr = $this->Status->readAll();

        $html = "<h3>" . _('Edit an existing status') . "</h3>";
        $html .= "<ul class='draggable sortable_status list-group'>";

        foreach ($statusArr as $status) {

            $html .= "<li id='status_" . $status['id'] . "' class='list-group-item center'>";


            $html .= "<ul class='list-inline'>";

            $html .= "<li>" . _('Name') . " <input required type='text' id='statusName_" . $status['id'] . "' value='" . $status['name'] . "' /></li>";
            $html .= "<li style='color:#" . $status['color'] . "'>" . _('Color') . " <input class='colorpicker' type='text' maxlength='6' id='statusColor_" . $status['id'] . "' value='" . $status['color'] . "' />";
            $html .= "</li>";
            $html .= "<li>" . _('Default status') . " <input type='radio' name='defaultRadio' id='statusDefault_" . $status['id'] . "'";
            // check the box if the status is already default
            if ($status['is_default'] == 1) {
                $html .= " checked";
            }
            $html .= "></li>";


            $html .= "<li><button id='statusUpdate_" . $status['id'] . "' onClick='statusUpdate(" . $status['id'] . ")' class='button'>" . _('Save') . "</button></li>";

            $html .= "<li><button class='button-delete' onClick=\"statusDestroy(" . $status['id'] . ")\">";
            $html .= _('Delete') . "</button></li>";

            $html .= "</ul></li>";
        }
        $html .= "</ul>";

        return $html;
    }
}
