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
    private $id;

    /** @var bool $isPdb if true, $id is handled as a PDB ID */
    private $isPdb;

    /** @var string $divId the generated <div> will have this id */
    private $divId;

    /** @var string $dataStyle style of the molecule */
    private $dataStyle;

    /** @var string $backgroundColor background color of canvas */
    private $backgroundColor;

    /** @var string $filePath path to data file */
    private $filePath;

    /**
     * Simple Molecule Viewer
     * Give me some data and I will do a nice 3D representation
     *
     * @param str $id The id of an attached structure file or PDB code
     * @param str $filePath Path to data file
     * @param bool $isPdb True if $id is a PDB ID. Defaults to False
     * @param str $dataStyle Representation of molecule. Defaults to "cartoon:color=spectrum"
     * @param str $backgroundColor Background color in hex notation
     */
    public function __construct(
        $id,
        $filePath = '',
        $isPdb = false,
        $dataStyle = 'cartoon:color=spectrum',
        $backgroundColor = '0xffffff'
    ) {
        // Check for proper use:
        // We always want either is_pdb to be true or a valid filepath!
        if ($filePath === "" && !$isPdb) {
            throw new Exception('If $id is not a PDB ID ($isPdb=false) then a valid file path must be passed!');
        }
        $this->id = (int) $id;
        $this->isPdb = $isPdb;
        $this->divId = '3Dmol_' . $this->id;
        $this->dataStyle = $dataStyle;
        $this->backgroundColor = $backgroundColor;
        $this->filePath = $filePath;
    }

    /**
     * Return a data string that can be digested by 3DMol.js according to
     * the given type of data: Either uploaded file or PDB code.
     *
     * @return str Representation of the data for 3Dmol.js
     */
    private function getDataString()
    {
        // If we deal with a PDB code, just pass data=$this->id to 3Dmol.js.
        // It will handle it just fine.
        if ($this->isPdb) {
            $dataString = "data={$this->id}";
        // Otherwise we need to pass the filepath with data-href
        } elseif ($this->filePath != "") {
            $dataString = "data-href='{$this->filePath}'";
        // This is triggered if the function is not properly used:
        // We always want either is_pdb to be true or a valid filepath!
        } else {
            throw new Exception('If $id is not a PDB ID ($isPdb=False) then a valid file path must be passed!');
        }

        // assemble and return the final expression
        $dataString .= " data-style='{$this->dataStyle}' data-backgroundcolor='{$this->backgroundColor}' ";

        return $dataString;
    }

    /**
     * Builds a basic control panel for the viewer
     *
     * @return str HTML code of the control panel
     */
    private function buildControls()
    {
        // Array holding list of styles for the dropdown list.
        // Each item consists of its label als translatable string and a corresponding javascript function
        // that is executed if the item is clicked.
        $styles = array(
            'cartoon' => array(_('Cartoon'), 'show_cartoon(\'' . $this->divId . '\');'),
            'stick' => array(_('Stick'), 'show_stick(\'' . $this->divId . '\');'),
            'surface_solid' => array(_('Solid Surface'), 'show_surface(\'' . $this->divId . '\');'),
            'surface_transparent' => array(_('Transparent Surface'), 'show_surface(\'' . $this->divId . '\', .7, \'0xffffff\');')
        );

        // Label of dropdown list and clean button
        $styleText = _('Style');
        $removeSurfacesText = _('Remove Surfaces');

        $controls = "<div style=\"padding-bottom: 5px\" class=\"btn-group\">\n";
        $controls .= "<button type=\"button\" class=\"btn btn-default btn-xs dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">{$styleText}<span class=\"caret\"></span></button>\n";
        $controls .= "<ul class=\"dropdown-menu\">\n";

        // Build dropdown menu
        foreach ($styles as $style => $props) {
            $controls .= "<li><a href=\"#{$this->divId}\" onClick=\"{$props[1]}\">{$props[0]}</a></li>\n";
        }
        $controls .= "</ul>\n";
        $controls .= "<button class='btn btn-default btn-xs align_left' data-toggle='tooltip' data-placement='bottom' title='{$removeSurfacesText}' onClick=\"remove_surfaces('{$this->divId}');\"><span class='glyphicon glyphicon-erase'></span></button></div>\n";

        return $controls;
    }

    /**
     * Generated HTML code of the viewer
     *
     * @return str HTML code of the viewer div
     */
    public function getViewerDiv()
    {
        $output = "{$this->buildControls()}<div style='margin-left: 25px;' class='center'><div style='height: 250px; width: 100%; position: relative;' class='row viewer_3Dmoljs' {$this->getDataString()} id={$this->divId}></div></div>";
        return $output;
    }
}
