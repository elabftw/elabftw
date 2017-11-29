<?php
/**
 * \Elabftw\Elabftw\MolViewer
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Simple viewer for molecular structures based on 3Dmol.js
 */
class MolViewer
{
    /** @var int $id the id of the molecule's file and the resulting viewer */
    //private $id;

    /** @var bool $isPdb if true, $id is handled as a PDB ID */
    //private $isPdb;

    /** @var string $divId the generated <div> will have this id */
    private $divId;

    /** @var string $dataStyle style of the molecule */
    //private $dataStyle;

    /** @var string $backgroundColor background color of canvas */
    //private $backgroundColor;

    /** @var string $filePath path to data file */
    private $filePath;

    /**
     * Simple Molecule Viewer
     * Give me some data and I will do a nice 3D representation
     *
     * @param string $id The id of an attached structure file or PDB code
     * @param string $filePath Path to data file
     */
    public function __construct($id, $filePath = '') {
        //$isPdb = false,
        //$dataStyle = 'stick',
        //$backgroundColor = '0xffffff'
        /*
    ) {
         */
        // Check for proper use:
        // We always want either is_pdb to be true or a valid filepath!
        /*
        if ($filePath === "" && !$isPdb) {
            throw new Exception('If $id is not a PDB ID ($isPdb=false) then a valid file path must be passed!');
        }
         */
        //$this->id = (int) $id;
        //$this->isPdb = $isPdb;
        $this->divId = '3Dmol_' . $id;
        //$this->dataStyle = $dataStyle;
        //$this->backgroundColor = $backgroundColor;
        $this->filePath = $filePath;
    }

    /**
     * Return a data string that can be digested by 3DMol.js according to
     * the given type of data: Either uploaded file or PDB code.
     *
     * @return string HTML with the div for 3dmol.js
     */
    private function getDiv()
    {
        /*
        // If we deal with a PDB code, just pass data=$this->id to 3Dmol.js.
        // It will handle it just fine.
        if ($this->isPdb) {
            $dataString = "data={$this->id}";
        // Otherwise we need to pass the filepath with data-href
        } elseif ($this->filePath != "") {
*/
        //$dataString = "data-href='{$this->filePath}'";
        /*
        // This is triggered if the function is not properly used:
        // We always want either is_pdb to be true or a valid filepath!
        } else {
            throw new Exception('If $id is not a PDB ID ($isPdb=False) then a valid file path must be passed!');
        }
         */

        // assemble and return the final expression
        //$dataString .= " data-style='stick' data-backgroundcolor='0xffffff' ";

        return "<div class='row viewer_3Dmoljs' data-href='" . $this->filePath .
            "' data-style='stick' data-backgroundcolor='0xffffff' id='" . $this->divId . "'></div>";
    }

    /**
     * Builds a basic control panel for the viewer
     *
     * @return string HTML code of the control panel
     */
    private function getControls()
    {
        /*
        // Array holding list of styles for the dropdown list.
        // Each item consists of its label als translatable string and a corresponding javascript function
        // that is executed if the item is clicked.
        $styles = array(
            'cartoon' => array(_('Cartoon'), 'show_cartoon(\'' . $this->divId . '\');'),
            'stick' => array(_('Stick'), 'show_stick(\'' . $this->divId . '\');'),
            'surface_solid' => array(_('Solid Surface'), 'show_surface(\'' . $this->divId . '\');'),
            'surface_transparent' => array(_('Transparent Surface'), 'show_surface(\'' . $this->divId . '\', .7, \'0xffffff\');')
        );
         */

        // Label of dropdown list and clean button
        $styleText = _('Style');
        //$removeSurfacesText = _('Remove Surfaces');

        $controls = "<div style='padding-bottom: 5px' class='btn-group'>";
        $controls .= "<button type='button' class='btn btn-default btn-xs dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>" . $styleText . " <span class='caret'></span></button>";
        $controls .= "<ul class='dropdown-menu clickable'>";

        // Build dropdown menu
        //$controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-cartoon'>" . _('Cartoon') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-cross'>" . _('Cross') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-line'>" . _('Line') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-sphere'>" . _('Sphere') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-stick'>" . _('Stick') . "</a></li>";
        //$controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-solid'>" . _('Solid Surface') . "</a></li>";
        //$controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-trans'>" . _('Transparent Surface') . "</a></li>";

        $controls .= "</ul>";
        //$controls .= "<button class='btn btn-default btn-xs align_left rmSurface' data-toggle='tooltip' data-placement='bottom' title='{$removeSurfacesText}' data-divid='{$this->divId}'><span class='glyphicon glyphicon-erase'></span></button>";
        $controls .= "</div>";

        return $controls;
    }

    /**
     * Generated HTML code of the viewer
     *
     * @return string HTML code of the viewer div
     */
    public function getViewerDiv()
    {
        return $this->getControls() . $this->getDiv();
    }
}
