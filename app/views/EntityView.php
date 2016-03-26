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
 * Entity View
 */
class EntityView
{

    public $experiments;
    public $database;
    public $limit;

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
            $html = "<div class='col-md-2 pull-right'>";
            $html .= "<a name='anchor'></a>";
            $html .= "<p class='inline'>" . _('Export this result:') . " </p>";
            $html .= "<a href='make.php?what=zip&id=" . Tools::buildStringFromArray($idArr) . "&type=" . $type . "'>";
            $html .= " <img src='img/zip.png' title='make a zip archive' alt='zip' /></a>";
            $html .= "<a href='make.php?what=csv&id=" . Tools::buildStringFromArray($idArr) . "&type=" . $type . "'>";
            $html .= " <img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export CSV' /></a></div>";

            return $html;
    }

    public function buildShowMenu($type)
    {
        $templates = '';
        $createItem = '';

        if ($type === 'experiments') {

            $Status = new Status();
            $categoryArr = $Status->read($_SESSION['team_id']);
            $createItem .= "<li class='dropdown-item'><a href='app/controllers/ExperimentsController.php?experimentsCreate=true'>";
            $createItem .= ngettext('Experiment', 'Experiments', 1) . "</a></li>";
            $Templates = new Templates($_SESSION['team_id']);
            $templatesArr = $Templates->readFromUserid($_SESSION['userid']);
            if (count($templatesArr) > 0) {
                foreach ($templatesArr as $tpl) {
                    $templates .= "<li class='dropdown-item'><a href='app/controllers/ExperimentsController.php?experimentsCreate=true&tpl="
                        . $tpl['id'] . "'>"
                        . $tpl['name'] . "</a></li>";
                }
            }
            $tag = "<input type='hidden' name='tag' value='" . $this->experiments->tag . "' />";
            $query = "<input type='hidden' name='q' value='" . $this->experiments->query . "' />";

        } else {

            $itemsTypes = new ItemsTypes($this->database->team);
            $categoryArr = $itemsTypes->read();
            foreach ($categoryArr as $category) {
                $templates .= "<li class='dropdown-item'><a style='color:#" . $category['bgcolor'] . "' href='app/controllers/DatabaseController.php?databaseCreateId=" . $category['id'] . "'>"
                    . $category['name'] . "</a></li>";
            }
            $tag = "<input type='hidden' name='tag' value='" . $this->database->tag . "' />";
            $query = "<input type='hidden' name='q' value='" . $this->database->query . "' />";

        }

        $html = "<menu class='border row'>";
        $html .= "<div class='row'><div class='col-md-12'>";

        // LEFT MENU - CREATE NEW
        $html .= "<div class='btn-group col-md-2 select-filter-status'>";
        $html .= "<button type='button' class='btn btn-elab-white'>" . _('Create new') . "</button>";
        $html .= "<button type='button' class='btn btn-elab dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>";
        $html .= "<b class='caret'></b>";
        $html .= "</button>";
        $html .= "<ul class='dropdown-menu'>";
        $html .= $createItem;
        $html .= $templates;
        $html .= "</ul></div>";

        // RIGHT MENU - FILTERS
        $html .= "<div class='col-md-10'>";
        $html .= "<form class='form-inline pull-right'>";
        $html .= "<div class='form-group'>";
        $html .= "<input type='hidden' name='mode' value='show' />";
        $html .= $tag . $query;

        // CATEGORY
        $html .= "<select name='filter' class='form-control select-filter-status'>";
        $html .= "<option value=''>" . _('Filter status') . "</option>";
        foreach ($categoryArr as $category) {
            $html .= "<option value='" . $category['id'] . "'" . checkSelectFilter($category['id']) . ">" . $category['name'] . "</option>";
        }
        $html .= "</select>";
        $html .= "<input type='hidden' name='mode' value='show' />";
        $html .= "<button class='btn btn-elab submit-filter'>" . _('Filter') . "</button>";

        // ORDER
        $html .= "<select name='order' class='form-control select-order'>";
        $html .= "<option value=''>" . _('Order by') . "</option>";
        $html .= "<option value='cat'" . checkSelectOrder('cat') . ">" . _('Category') . "</option>";
        $html .= "<option value='date'" . checkSelectOrder('date') . ">" . _('Date') . "</option>";
        $html .= "<option value='rating'" . checkSelectOrder('rating') . ">" . _('Rating') . "</option>";
        $html .= "<option value='title'" . checkSelectOrder('title') . ">" . _('Title') . "</option>";
        $html .= "</select>";

        // SORT
        $html .= "<select name='sort' class='form-control select-sort'>";
        $html .= "<option value=''>" . _('Sort') . "</option>";
        $html .= "<option value='desc'" . checkSelectSort('desc') . ">" . _('DESC') . "</option>";
        $html .= "<option value='asc'" . checkSelectSort('asc') . ">" . _('ASC') . "</option>";
        $html .= "</select>";
        $html .= "<button class='btn btn-elab submit-order'>" . _('Order') . "</button>";
        $html .= "<button type='reset' class='btn btn-danger submit-reset' onClick=\"javascript:location.href='" . $type . ".php?mode=show'\">";
        $html .= _('Reset') . "</button></div></form></div>";

        $html .= "</div></div>";
        $html .= "</menu>";

        return $html;
    }

    protected function buildShowJs($type)
    {
        if ($type === 'experiments') {
            $shortcut = "
            // KEYBOARD SHORTCUTS
            key('" . $_SESSION['prefs']['shortcuts']['create'] . "', function(){
                location.href = 'app/controllers/ExperimentsController.php?experimentsCreate=true'
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
}
