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
}
