<?php
/**
 * \Elabftw\Elabftw\EntityView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Entity View to show/view/edit experiments or DB items
 */
class EntityView
{
    /** our Database instance */
    public $Database;

    /** number of items to display per page */
    public $limit = 15;

    /** can be compact */
    public $display = '';

    /** the tag searched */
    public $tag = '';

    /** the query entered */
    public $query = '';

    /**
     * Add chemdoodle JS
     *
     * @return string
     */
    public function injectChemEditor()
    {
        $html = '';
        if ($_SESSION['prefs']['chem_editor']) {
            $html .= "<div class='box chemdoodle'>";
            $html .= "<h3>" . _('Molecule drawer') . "</h3>";
            $html .= "<div class='center'>
                        <script>
                            var sketcher = new ChemDoodle.SketcherCanvas('sketcher', 550, 300, {oneMolecule:true});
                        </script>
                    </div>
            </div>";
        }

        return $html;
    }

    /**
     * Ask the user if he really wants to navigate out of the page
     *
     * @return string
     */
    public function injectCloseWarning()
    {
        $js = '';
        if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
            $js .= "window.onbeforeunload = function (e) {
                  e = e || window.event;
                  return '" . _('Do you want to navigate away from this page? Unsaved changes will be lost!') . "';};";
        }
        return $js;
    }

    /**
     * Generate html for zip/csv export buttons
     *
     * @param array $idArr
     * @param string $type items or experiments
     * @return string
     */
    public function buildExportMenu($idArr, $type)
    {
            $html = "<div class='align_right'>";
            $html .= "<a name='anchor'></a>";
            $html .= "<p class='inline'>" . _('Export this result:') . " </p>";
            $html .= "<a href='make.php?what=zip&id=" . Tools::buildStringFromArray($idArr) . "&type=" . $type . "'>";
            $html .= " <img src='img/zip.png' title='make a zip archive' alt='zip' /></a>";
            $html .= "<a href='make.php?what=csv&id=" . Tools::buildStringFromArray($idArr) . "&type=" . $type . "'>";
            $html .= " <img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export CSV' /></a></div>";

            return $html;
    }

    /**
     * The menu on top
     *
     * @param string $type experiments or items
     * @return string
     */
    public function buildShowMenu($type)
    {
        $templates = '';
        $createItem = '';

        if ($type === 'experiments') {

            $Status = new Status();
            $categoryArr = $Status->read($_SESSION['team_id']);
            $createItem .= "<li class='dropdown-item'><a href='app/controllers/ExperimentsController.php?create=true'>";
            $createItem .= ngettext('Experiment', 'Experiments', 1) . "</a></li>";
            $createItem .= "<li role='separator' class='divider'></li>";
            $Templates = new Templates($_SESSION['team_id']);
            $templatesArr = $Templates->readFromUserid($_SESSION['userid']);
            if (count($templatesArr) > 0) {
                foreach ($templatesArr as $tpl) {
                    $templates .= "<li class='dropdown-item'><a href='app/controllers/ExperimentsController.php?create=true&tpl="
                        . $tpl['id'] . "'>"
                        . $tpl['name'] . "</a></li>";
                }
            } else { //user has no templates
                $templates .= "<li class='dropdown-item disabled'><a href='#'>" . _('No templates found') . "</a></li>";
            }

            // FILTER BY
            $filterTitle = _('Filter status');

        } else {

            // filter by type list
            $itemsTypes = new ItemsTypes($this->Database->team);
            $categoryArr = $itemsTypes->readAll();
            foreach ($categoryArr as $category) {
                $templates .= "<li class='dropdown-item'><a style='color:#" . $category['bgcolor'] . "' href='app/controllers/DatabaseController.php?databaseCreateId=" . $category['id'] . "'>"
                    . $category['name'] . "</a></li>";
            }

            // FILTER BY
            $filterTitle = _('Filter by type');

        }

        // BEGIN
        $html = "<div class='row'>";

        // LEFT MENU - CREATE NEW
        $html .= "<div class='col-md-2'>";
        $html .= "<div class='dropdown'>";
        $html .= "<button class='btn btn-default dropdown-toggle' type='button' id='dropdownMenu1' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>";
        $html .= _('Create new');
        $html .= " <span class='caret'></span>";
        $html .= "</button>";
        $html .= "<ul class='dropdown-menu' aria-labelledby='dropdownMenu1'>";
        $html .= $createItem . $templates;
        $html .= "</ul>";
        $html .= "</div></div>";

        // RIGHT MENU
        // padding 0 is necessary to get the menu fully to the right
        $html .= "<div class='col-md-10' style='padding:0'>";

        // FILTERS
        $html .= "<div class='col-md-10 align_right'>";
        $html .= "<form class='form-inline align_right'>";
        $html .= "<div class='form-group'>";
        $html .= "<input type='hidden' name='tag' value='" . $this->tag . "' />";
        $html .= "<input type='hidden' name='q' value='" . $this->query . "' />";

        // CATEGORY
        $html .= "<select name='filter' style='-moz-appearance:none' class='form-control select-filter-status'>";
        $html .= "<option value=''>" . $filterTitle . "</option>";
        foreach ($categoryArr as $category) {
            $html .= "<option value='" . $category['id'] . "'" . checkSelectFilter($category['id']) . ">" . $category['name'] . "</option>";
        }
        $html .= "</select>";
        $html .= "<input type='hidden' name='mode' value='show' />";
        $html .= "<button class='btn btn-elab submit-filter'>" . _('Filter') . "</button>";

        // ORDER
        $html .= "<select name='order' style='-moz-appearance:none' class='form-control select-order'>";
        $html .= "<option value=''>" . _('Order by') . "</option>";
        $html .= "<option value='cat'" . checkSelectOrder('cat') . ">" . _('Category') . "</option>";
        $html .= "<option value='date'" . checkSelectOrder('date') . ">" . _('Date') . "</option>";
        if ($type === 'database') {
            $html .= "<option value='rating'" . checkSelectOrder('rating') . ">" . _('Rating') . "</option>";
        }
        $html .= "<option value='title'" . checkSelectOrder('title') . ">" . _('Title') . "</option>";
        $html .= "</select>";

        // SORT
        $html .= "<select name='sort' style='-moz-appearance:none' class='form-control select-sort'>";
        $html .= "<option value=''>" . _('Sort') . "</option>";
        $html .= "<option value='desc'" . checkSelectSort('desc') . ">" . _('DESC') . "</option>";
        $html .= "<option value='asc'" . checkSelectSort('asc') . ">" . _('ASC') . "</option>";
        $html .= "</select>";
        $html .= "<button class='btn btn-elab submit-order'>" . _('Order') . "</button>";
        $html .= "<button type='reset' class='btn btn-danger submit-reset' onClick=\"javascript:location.href='" . $type . ".php?mode=show'\">";
        $html .= _('Reset') . "</button></div></form></div>";

        $html .= "</div></div><hr>";

        return $html;
    }

    /**
     * JS for show
     *
     * @param string $type experiments or items
     * @return string
     */
    protected function buildShowJs($type)
    {
        if ($type === 'experiments') {
            $shortcut = "
            // KEYBOARD SHORTCUTS
            key('" . $_SESSION['prefs']['shortcuts']['create'] . "', function(){
                location.href = 'app/controllers/ExperimentsController.php?create=true'
                });";
        } else {
            $shortcut = '';
        }
        $html = "<script>
        $(document).ready(function(){

            // SHOW MORE BUTTON
            $('section.item').hide(); // hide everyone
            $('section.item').slice(0, " . $this->limit . ").show(); // show only the default at the beginning
            $('#loadButton').click(function(e){ // click to load more
                e.preventDefault();
                $('section.item:hidden').slice(0, " . $this->limit . ").show();
                if ($('section.item:hidden').length == 0) { // check if there are more exp to show
                    $('#loadButton').hide(); // hide load button when there is nothing more to show
                    $('#loadAllButton').hide(); // hide load button when there is nothing more to show
                }
            });
            $('#loadAllButton').click(function(e){ // click to load more
                e.preventDefault();
                $('section.item:hidden').show();
                $('#loadAllButton').hide(); // hide load button when there is nothing more to show
                $('#loadButton').hide(); // hide load button when there is nothing more to show
            });" . $shortcut . "});
        </script>";

        return $html;
    }

    /**
     * Display the tags
     *
     * @param string $type experiments or items
     * @param string $mode edit or view
     * @param int $itemId The ID of the item for which we want the tags
     * @return string Will show the HTML for tags
     */
    protected function showTags($type, $mode, $itemId)
    {
        $Tags = new Tags($type, $itemId);
        $tagList = $Tags->read();

        $html = '';

        if (count($tagList) === 0 && $mode != 'edit') {
            return $html;
        }

        if ($mode === 'view') {

            $html = "<span class='tags'><img src='img/tags.png' alt='tags' /> ";
            foreach ($tagList as $tag) {
                if ($type === 'experiments') {
                    $html .= "<a href='experiments.php?mode=show&tag=" . urlencode(stripslashes($tag['tag'])) . "'>" . stripslashes($tag['tag']) . "</a> ";
                } else { // type is items
                    $html .= "<a href='database.php?mode=show&tag=" . urlencode(stripslashes($tag['tag'])) . "'>" . stripslashes($tag['tag']) . "</a> ";
                }
            }
            $html .= "</span>";

            return $html;
        }


        $html = "<img src='img/tags.png' class='bot5px' alt='tags' /><label for='addtaginput'>" . _('Tags') . "</label>";
        $html .= "<div class='tags'><span id='tags_div'>";

        foreach ($tagList as $tag) {
            $html .= "<span class='tag'><a onclick='delete_tag(" . $tag['id'] . "," . $itemId . ")'>" . stripslashes($tag['tag']) . "</a></span>";
        }
        $html .= "</span><input type='text' id='createTagInput' placeholder='" . _('Add a tag') . "' /></div>";

        return $html;
    }
}
