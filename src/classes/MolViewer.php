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
declare(strict_types=1);

namespace Elabftw\Elabftw;

/**
 * Simple viewer for molecular structures based on 3Dmol.js
 */
class MolViewer
{
    /** @var string $divId the generated <div> will have this id */
    private $divId;

    /** @var string $filePath path to data file */
    private $filePath;

    /** @var string $defaultStyle for 3d view; e.g. "stick" or "cartoon" */
    private $defaultStyle = "stick";

    /**
     * Simple Molecule Viewer
     * Give me some data and I will do a nice 3D representation
     *
     * @param string $id The id of an attached structure file or PDB code
     * @param string $filePath Path to data file
     * @param bool $isProtein Whether this file contains a protein structure. If true, default style will be 'cartoon'
     */
    public function __construct($id, $filePath = '', $isProtein = false)
    {
        $this->divId = '3Dmol_' . $id;
        $this->filePath = $filePath;
        if ($isProtein) {
            $this->defaultStyle = "cartoon:color=spectrum";
        }
    }

    /**
     * Return a data string that can be digested by 3DMol.js according to
     * the given type of data.
     *
     * @return string HTML with the div for 3dmol.js
     */
    private function getDiv(): string
    {
        return "<div class='row viewer_3Dmoljs' data-href='app/download.php?f=" . $this->filePath .
            "' data-style='" . $this->defaultStyle . "' data-backgroundcolor='0xffffff' id='" . $this->divId . "'></div>";
    }

    /**
     * Builds a basic control panel for the viewer
     *
     * @return string HTML code of the control panel
     */
    private function getControls(): string
    {

        // Label of dropdown list and clean button
        $styleText = _('Style');

        $controls = "<div style='padding-bottom: 5px' class='btn-group'>";
        $controls .= "<button type='button' class='btn btn-default btn-xs dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>" . $styleText . " <span class='caret'></span></button>";
        $controls .= "<ul class='dropdown-menu clickable'>";

        // Build dropdown menu
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-cartoon'>" . _('Cartoon (proteins only)') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-cross'>" . _('Cross') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-line'>" . _('Line') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-sphere'>" . _('Sphere') . "</a></li>";
        $controls .= "<li><a data-divid='" . $this->divId . "' class='3dmol-stick'>" . _('Stick') . "</a></li>";

        $controls .= "</ul>";
        $controls .= "</div>";

        return $controls;
    }

    /**
     * Generated HTML code of the viewer
     *
     * @return string HTML code of the viewer div
     */
    public function getViewerDiv(): string
    {
        return $this->getControls() . $this->getDiv();
    }
}
