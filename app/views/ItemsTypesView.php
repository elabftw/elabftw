<?php
/**
 * \Elabftw\Elabftw\ItemsTypesView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypesView
{
    /** instance of ItemsTypes */
    public $itemsTypes;

    /**
     * Constructor
     *
     * @param ItemsTypes $itemsTypes
     */
    public function __construct(ItemsTypes $itemsTypes)
    {
        $this->itemsTypes = $itemsTypes;
    }

    /**
     * Output html for create new item type
     *
     * @return string $html
     */
    public function showCreate()
    {
        $html = "<div class='box'>";
        $html .= "<h3>" . _('Add a new type of item') . "</h3><hr>";
        $html .= "<ul class='list-inline'>";
        $html .= "<li>" . _('Name') . " <input type='text' id='itemsTypesName' /></li>";
        $html .= "<li>" . _('Color') . " <input class='colorpicker' type='text' id='itemsTypesColor' value='29AEB9' /></li>";
        $html .= "<li><input type='checkbox' id='itemsTypesBookable'> <label for='itemsTypesBookable'>" . _('Bookable') . " ";
        $html .= sprintf(_("in the %sscheduler%s"), "<a href='team.php'>", "</a>") . "</label>";
        $html .= "</ul>";

        $html .= "<textarea class='mceditable' id='itemsTypesTemplate' /></textarea>";
        $html .= "<div class='submitButtonDiv'><button onClick='itemsTypesCreate()' class='button'>" . _('Save') . "</button></div>";
        $html .= "</div>";

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

        $html = "<div class='box'>";
        $html .= "<h3>" . _('Database Items Types') . "</h3><hr>";
        $html .= "<ul class='draggable sortable_itemstypes list-group'>";

        foreach ($itemsTypesArr as $itemType) {

            $html .= "<li id='itemstypes_" . $itemType['category_id'] . "' class='list-group-item'>";

            $html .= "<ul class='list-inline'>";

            $html .= "<li>" . _('Name') . " <input type='text' id='itemsTypesName_" . $itemType['category_id'] . "' value='" . $itemType['category'] . "' /></li>";
            $html .= "<li style='color:#" . $itemType['color'] . "'>" . _('Color') . " <input class='colorpicker' type='text' style='display:inline' id='itemsTypesColor_" . $itemType['category_id'] . "' value='" . $itemType['color'] . "' /></li>";
            $html .= "<li><input id='itemsTypesBookable_" . $itemType['category_id'] . "' type='checkbox' ";
            if ($itemType['bookable']) {
                $html .= 'checked ';
            }
            $html .= "> <label for='itemsTypesBookable_" . $itemType['category_id'] . "'>" . _('Bookable') . "</label></li>";
            $html .= "<li><button onClick='itemsTypesShowEditor(" . $itemType['category_id'] . ")' class='button button-neutral'>" . _('Edit the template') . "</button></li>";
            $html .= "<li><button onClick='itemsTypesUpdate(" . $itemType['category_id'] . ")' class='button'>" . _('Save') . "</button></li>";
            $html .= "<li><button class='button button-delete' onClick=\"itemsTypesDestroy(" . $itemType['category_id'] . ")\">";
            $html .= _('Delete') . "</button></li>";

            $html .= "</li>";
            $html .= "<li class='itemsTypesEditor' id='itemsTypesEditor_" . $itemType['category_id'] . "'><textarea class='mceditable' style='height:50px' id='itemsTypesTemplate_" . $itemType['category_id'] . "' />" . $itemType['template'] . "</textarea></li>";
            $html .= "</ul>";
        }
        $html .= "</ul>";
        $html .= "</div>";

        return $html;
    }
}
