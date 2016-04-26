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

    /** instance of ItemsTypes */
    public $itemsTypes;
    /**
     * Constructor
     *
     * @param ItemsTypes $itemsTypes
     */
    public function __construct(ItemsTypes  $itemsTypes)
    {
        $this->itemsTypes = $itemsTypes;
        $this->pdo = Db::getConnection();
    }

    /**
     * Output html for create new item type
     *
     * @return string $html
     */
    public function showCreate()
    {
        $html = "<h3>" . _('Add a new type of item') . "</h3>";
        $html .= "<ul class='list-group'><li class='list-group-item'>";
        $html .= "<ul class='list-inline'>";
        $html .= "<li>" . _('Name') . " <input type='text' id='itemsTypesName' /></li>";
        $html .= "<li>" . _('Color') . " <input class='colorpicker' type='text' id='itemsTypesColor' value='29AEB9' /></li></ul>";
        $html .= "<textarea class='mceditable' id='itemsTypesTemplate' /></textarea>";
        $html .= "<div class='submitButtonDiv'><button onClick='itemsTypesCreate()' class='button'>" . _('Save') . "</button></div>";
        $html .= "</li></ul>";

        return $html;

    }

    /**
     * List the items types
     *
     * @return string $html
     */
    public function show()
    {
        $itemsTypesArr = $this->itemsTypes->readAll();

        $html = "<h3>" . _('Database items types') . "</h3>";
        $html .= "<ul class='draggable sortable_itemstypes list-group'>";

        foreach ($itemsTypesArr as $itemType) {
            // count the items with this type
            // don't allow deletion if items with this type exist
            // but instead display a message to explain
            $count_db_sql = "SELECT COUNT(*) FROM items WHERE type = :type";
            $count_db_req = $this->pdo->prepare($count_db_sql);
            $count_db_req->bindParam(':type', $itemType['id'], PDO::PARAM_INT);
            $count_db_req->execute();
            $count = $count_db_req->fetchColumn();

            $html .= "<li id='itemstypes_" . $itemType['id'] . "' class='list-group-item center'>";


            $html .= "<ul class='list-inline'>";

            $html .= "<li>" . _('Name') . " <input type='text' id='itemsTypesName_" . $itemType['id'] . "' value='" . $itemType['name'] . "' /></li>";
            $html .= "<li style='color:#" . $itemType['bgcolor'] . "'>" . _('Color') . " <input class='colorpicker' type='text' style='display:inline' id='itemsTypesColor_" . $itemType['id'] . "' value='" . $itemType['bgcolor'] . "' /></li>";
            $html .= "<li><button onClick='itemsTypesShowEditor(" . $itemType['id'] . ")' class='button'>" . _('Edit the template') . "</button></li>";
            $html .= "<li><button onClick='itemsTypesUpdate(" . $itemType['id'] . ")' class='button'>" . _('Save') . "</button></li>";
            $html .= "<li><button class='button' ";
            if ($count == 0) {
                $html .= "onClick=\"itemsTypesDestroy(" . $itemType['id'] . ")\"";
            } else {
                $html .= "onClick=\"alert('" . _('Remove all database items with this type before deleting this type.') . "')\"";
            }
            $html .= ">" . _('Delete') . "</button></li>";

            $html .= "</li>";
            $html .= "<li class='itemsTypesEditor' id='itemsTypesEditor_" . $itemType['id'] . "'><textarea class='mceditable' style='height:50px' id='itemsTypesTemplate_" . $itemType['id'] . "' />" . $itemType['template'] . "</textarea></li>";
            $html .= "</ul>";
        }
        $html .= "</ul>";

        return $html;
    }
}
