<?php
/**
 * \Elabftw\Elabftw\StatusView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
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
        $html = "<div class='box'>";
        $html .= "<h3>" . _('Add a New Status') . "</h3><hr>";
        $html .= "<ul class='list-group'><li>";
        $html .= "<ul class='list-inline'>";
        $html .= "<li>" . _('Name') . " <input type='text' id='statusName' /></li>";
        $html .= "<li>" . _('Color') . " <input class='colorpicker' type='text' id='statusColor' value='000000' /></li>";
        $html .= "<li><button onClick='statusCreate()' class='button'>" . _('Save') . "</button></li>";
        $html .= "</ul></li></ul></div>";

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
        $html = "<div class='box'>";
        $html .= "<h3>" . _('Edit an Existing Status') . "</h3><hr>";
        $html .= "<ul class='draggable sortable_status list-group'>";

        foreach ($statusArr as $status) {

            $html .= "<li id='status_" . $status['category_id'] . "' class='list-group-item'>";


            $html .= "<ul class='list-inline'>";

            $html .= "<li>" . _('Name') . " <input required type='text' id='statusName_" . $status['category_id'] . "' value='" . $status['category'] . "' /></li>";
            $html .= "<li style='color:#" . $status['color'] . "'>" . _('Color') . " <input class='colorpicker' type='text' maxlength='6' id='statusColor_" . $status['category_id'] . "' value='" . $status['color'] . "' />";
            $html .= "</li>";
            $html .= "<li><input type='radio' name='defaultRadio' id='statusDefault_" . $status['category_id'] . "'";
            // check the box if the status is already default
            if ($status['is_default'] == 1) {
                $html .= " checked";
            }
            $html .= "> " . _('Default status') . " </li>";


            $html .= "<li><button id='statusUpdate_" . $status['category_id'] . "' onClick='statusUpdate(" . $status['category_id'] . ")' class='button'>" . _('Save') . "</button></li>";

            $html .= "<li><button class='button button-delete' onClick=\"statusDestroy(" . $status['category_id'] . ")\">";
            $html .= _('Delete') . "</button></li>";

            $html .= "</ul></li>";
        }
        $html .= "</ul></div>";

        return $html;
    }
}
