<?php
/**
 * \Elabftw\Elabftw\MolViewer
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;

/**
 * Simple viewer for molecular structures based on 3Dmol.js
 */
class MolViewer
{
    /** the id of the molecule's file and the resulting viewer */
    private $id;

    /** if true, $id is handled as a PDB ID */
    private $is_pdb_id;

    /** the generated <div> will have this id */
    private $div_id;

    /** Style of the molecule */
    private $data_style;

    /** Background color of canvas */
    private $backgroundcolor;

    /**
     * Simple Molecule Viewer
     * Give me some data and I will do a nice 3D representation
     *
     * @param str $id The id of an attached structure file or PDB code
     * @param str $filepath Path to data file
     * @param bool $is_pdb True if $id is a PDB ID. Defaults to False
     * @param str $data_style Representation of molecule. Defaults to "cartoon:color=spectrum"
     * @param $str $backgroundcolor Background color in hex notation
     * @return str $output
     */
    public function __construct($id, $filepath="", $is_pdb=False, $data_surface="", $data_style="cartoon:color=spectrum", $backgroundcolor="0xffffff")
    {
        // Check for proper use:
        // We always want either is_pdb to be true or a valid filepath!
        if ($filepath === "" && !$is_pdb)
        {
            throw new Exception('If $id is not a PDB ID ($is_pdb=False) then a valid file path must be passed!');
        }
        $this->id = $id;
        $this->is_pdb = $is_pdb;
        $this->div_id = '3Dmol_' . $this->id;
        $this->data_style = $data_style;
        $this->backgroundcolor = $backgroundcolor;
        $this->filepath = $filepath;
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
        if ($this->is_pdb)
        {
            $data_string = "data={$this->id}";
        // Otherwise we need to pass the filepath with data-href
        } elseif ($this->filepath != "") {
            $data_string = "data-href='{$this->filepath}'";
        // This is triggered if the function is not properly used:
        // We always want either is_pdb to be true or a valid filepath!
        } else {
            throw new Exception('If $id is not a PDB ID ($is_pdb=False) then a valid file path must be passed!');
        }

        // assemble and return the final expression
        $data_string .= " data-style='{$this->data_style}' data-backgroundcolor='{$this->backgroundcolor}' ";

        return $data_string;
    }

    /**
     * Builds a basic control panel for the viewer
     *
     * @return str HTML code of the control panel
     */
    private function buildControls()
    {
        // Tooltips of the buttons
        $cartoon_text = _('Cartoon');
        $stick_text = _('Stick');
        $solid_surface_text = _('Solid Surface');
        $transparent_surface_text = _('Transparent Surface');
        $remove_surfaces_text = _('Remove all surfaces');

        $controls = "<div class='row' style='padding-bottom: 5px;' id='{$this->div_id}_controls'>";

        $controls .= "<button class='btn btn-default btn-xs align_left' data-toggle='tooltip' data-placement='bottom' title='{$cartoon_text}' onClick=\"show_cartoon('{$this->div_id}');\">C</button>\n";
        $controls .= "<button class='btn btn-default btn-xs data-toggle='tooltip' data-placement='bottom' title='{$stick_text}' align_left' onClick=\"show_stick('{$this->div_id}');\">S</button>\n";
        $controls .= "<button class='btn btn-default btn-xs align_left' data-toggle='tooltip' data-placement='bottom' title='{$solid_surface_text}' onClick=\"show_surface('{$this->div_id}');\">SS</button>\n";
        $controls .= "<button class='btn btn-default btn-xs align_left' data-toggle='tooltip' data-placement='bottom' title='{$transparent_surface_text}' onClick=\"show_surface('{$this->div_id}', .7, '0xffffff');\">TS</button>\n";
        $controls .= "<button class='btn btn-default btn-xs align_left' data-toggle='tooltip' data-placement='bottom' title='{$remove_surfaces_text}' onClick=\"remove_surfaces('{$this->div_id}');\"><span class='glyphicon glyphicon-erase'></span></button>\n";

        $controls .= "</div>";

        return $controls;
    }

    /**
     * Generated HTML code of the viewer
     *
     * @return str HTML code of the viewer div
     */
    public function getViewerDiv()
    {
        $output = "<div class='center' style='margin-left: 15px; '>{$this->buildControls()}<div style='height: 250px; width: 100%; position: relative;' class='row viewer_3Dmoljs' {$this->getDataString()} id={$this->div_id}></div></div>";
        return $output;
    }



}
