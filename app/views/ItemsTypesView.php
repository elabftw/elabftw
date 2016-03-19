<?php
/**
 * \Elabftw\Elabftw\ItemsTypesView
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
 * The kind of items you can have in the database for a team
 */
class ItemsTypesView
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

    public function show($itemsTypesArr)
    {
        $html ="<ul class='draggable sortable_itemstypes list-group'>";

        foreach ($itemsTypesArr as $itemType) {
            $html .= "<li id='itemstypes_" . $itemType['id'] . "' class='list-group-item'>";
            $html .= "<a class='trigger_" . $itemType['id'] . "'>" . _('Edit') . ' ' . $itemType['name'] . "</a>";
            $html .= "<div class='toggle_container_" . $itemType['id'] . "'>";
            $html .= "<img class='align_right' src='img/small-trash.png' title='delete' alt='delete' ";

            // count the items with this type
            // don't allow deletion if items with this type exist
            // but instead display a message to explain
            $count_db_sql = "SELECT COUNT(*) FROM items WHERE type = :type";
            $count_db_req = $this->pdo->prepare($count_db_sql);
            $count_db_req->bindParam(':type', $itemType['id'], PDO::PARAM_INT);
            $count_db_req->execute();
            $count = $count_db_req->fetchColumn();

            if ($count === 0) {
                $html .= "onClick=\"deleteThis('" . $itemType['id'] . "','item_type', 'admin.php')\"";
            } else {
                $html .= "onClick=\"alert('" . _('Remove all database items with this type before deleting this type.') . "')\"";
            }
            $html .= " />";

            $html .= "<label>" .  _('Edit name') . "</label>";
            $html .= "<input required type='text' id='itemsTypesName_" . $itemType['id'] . "' value='" . $itemType['name'] . "' />";
            $html .= "<div id='colorwheel_div_" . $itemType['id'] . "'>";
            $html .= "<label>" . _('Edit color') . "</label>";
            $html .= "<input class='colorpicker' type='text' style='display:inline' id='itemsTypesColor_" . $itemType['id'] . "' value='" . $itemType['bgcolor'] . "' />";
            $html .= "</div>";
            $html .= "<textarea class='mceditable' id='itemsTypesTemplate_" . $itemType['id'] . "' />" . $itemType['template'] . "</textarea>";
            $html .= "<div class='submitButtonDiv'>
                    <button type='submit' onClick='itemsTypesUpdate(" . $itemType['id'] . ")' class='button'>" . _('Edit') . ' ' . $itemType['name'] . "</button>
                </div>";

            $html .= "<script>$(document).ready(function() {
            $('.toggle_container_" . $itemType['id'] . "').hide();
            $('a.trigger_" . $itemType['id'] . "').click(function(){
            $('div.toggle_container_" . $itemType['id'] . "').slideToggle(100);";
            // disable sortable behavior
            $html .= "$('.sortable_itemstypes').sortable('disable');";
            $html .= "});});</script></div></li>";
        }
        $html .= "</ul>";
        return $html;
    }
}
