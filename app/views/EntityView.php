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
}
