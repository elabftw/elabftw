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

use \PDO;

/**
 * HTML for the status
 */
class StatusView
{
    /** The PDO object */
    private $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    public function show($statusArr, $team)
    {
        $html ="<ul class='draggable sortable_status list-group'>";

        foreach ($statusArr as $status) {
            $html .= "<li id='_" . $status['id'] . "' class='list-group-item'>";
            $html .= "<a class='trigger_" . $status['id'] . "'>" . _('Edit') . ' ' . $status['name'] . "</a>";
            $html .= "<div class='toggle_container_" . $status['id'] . "'>";
            $html .= "<img class='align_right' src='img/small-trash.png' title='delete' alt='delete' ";

            // count the experiments with this status
            // don't allow deletion if experiments with this status exist
            // but instead display a message to explain
            $count_exp_sql = "SELECT COUNT(*) FROM experiments WHERE status = :status AND team = :team";
            $count_exp_req = $this->pdo->prepare($count_exp_sql);
            $count_exp_req->bindParam(':status', $status['id'], PDO::PARAM_INT);
            $count_exp_req->bindParam(':team', $team, PDO::PARAM_INT);
            $count_exp_req->execute();
            $count = $count_exp_req->fetchColumn();

            if ($count === 0) {
                $html .= "onClick=\"deleteThis('" . $status['id'] . "','status', 'admin.php')\"";
            } else {
                $html .= "onClick=\"alert('" . _('Remove all experiments with this status before deleting this status.') . "')\"";
            }
            $html .= " />";

            $html .= "<label>" .  _('Edit name') . "</label>";
            $html .= "<input required type='text' id='statusName_" . $status['id'] . "' value='" . $status['name'] . "' />";
            $html .= "<label for='default_checkbox'>" . _('Default status') . "</label>";
            $html .= "<input type='checkbox' id='statusDefault_" . $status['id'] . "'";
            // check the box if the status is already default
            if ($status['is_default'] == 1) {
                $html .= " checked";
            }
            $html .= ">";
            $html .= "<div id='colorwheel_div_" . $status['id'] . "'>";
            $html .= "<label>" . _('Edit color') . "</label>";
            $html .= "<input class='colorpicker' type='text' style='display:inline' id='statusColor_" . $status['id'] . "' value='" . $status['color'] . "' />";
            $html .= "</div>";
            $html .= "<div class='submitButtonDiv'>
                    <button type='submit' onClick='statusUpdate(" . $status['id'] . ")' class='button'>" . _('Edit') . ' ' . $status['name'] . "</button>
                </div>";

            $html .= "<script>$(document).ready(function() {
            $('.toggle_container_" . $status['id'] . "').hide();
            $('a.trigger_" . $status['id'] . "').click(function(){
            $('div.toggle_container_" . $status['id'] . "').slideToggle(100);";
            // disable sortable behavior
            $html .= "$('.sortable_status').sortable('disable');";
            $html .= "});});</script></div></li>";
        }
        $html .= "</ul>";
        return $html;
    }
}
