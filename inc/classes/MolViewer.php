<?php
/**
 * \Elabftw\Elabftw\MolViewer
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @author David Müller
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;

/**
 * Timestamp an experiment with RFC 3161
 * Based on:
 * http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161
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

    private $data_surface;

    private $backgroundcolor;

    /**
     * Give me an experiment id and a db and I make good pdf for you
     *
     * @param str $id The id of an attached structure file or PDB code
     * @param bool $is_pdb True if $id is a PDB ID. Defaults to False
     * @param str $mol_style Representation of molecule. Defaults to "cartoon:color=spectrum"
     * @return str $output
     */
    public function __construct($id, $filepath="", $is_pdb=False, $data_surface="opacity:0.7;color:white", $data_style="cartoon:color=spectrum", $backgroundcolor="0xffffff")
    {
        if ($filepath === "" && !$is_pdb)
        {
            throw new Exception('If $id is not a PDB ID ($is_pdb=False) then a valid file path must be passed!');
        }
        $this->id = $id;
        $this->is_pdb = $is_pdb;
        $this->div_id = '3Dmol_' . $this->id;
        $this->data_style = $data_style;
        $this->data_surface = $data_surface;
        $this->backgroundcolor = $backgroundcolor;
        $this->filepath = $filepath;
    }

    private function getDataString()
    {
        if ($this->is_pdb)
        {
            $data_string = "data={$this->id}";
        } elseif ($this->filepath != "") {
            $data_string = "data-href='{$this->filepath}'";
        } else {
            throw new Exception('If $id is not a PDB ID ($is_pdb=False) then a valid file path must be passed!');
        }

        $data_string .= " data-style='{$this->data_style}' data-surface='{$this->data_surface}' data-backgroundcolor='{$this->backgroundcolor}'";

        return $data_string;
    }

    private function buildControls()
    {
        $nav = "<div class='col-xs-6 col-md-4' id='{$this->div_id}_controls'>";

        $nav .= "<button class='btn btn-default btn-xs align_right' style='width: 100%;' onClick=\"$3Dmol.viewers['{$this->div_id}'].setStyle({hetflag:false},{cartoon:{color: 'spectrum'}}); $3Dmol.viewers['{$this->div_id}'].render();\">C</button>\n";
        $nav .= "<button class='btn btn-default btn-xs align_right' style='width: 100%;' onClick=\"$3Dmol.viewers['{$this->div_id}'].setStyle({},{stick:{}}); $3Dmol.viewers['{$this->div_id}'].render();\">S</button>\n";
        $nav .= "<button class='btn btn-default btn-xs align_right' style='width: 100%;' onClick=\"$3Dmol.viewers['{$this->div_id}'].addSurface($3Dmol.SurfaceType.MS, {opacity:1,color:0xffffff}, {hetflag:false}, {hetflag:false}); $3Dmol.viewers['{$this->div_id}'].render();\">SS</button>\n";
        $nav .= "<button class='btn btn-default btn-xs align_right' style='width: 100%;' onClick=\"$3Dmol.viewers['{$this->div_id}'].addSurface($3Dmol.SurfaceType.MS, {opacity:.7,color:0xffffff}, {hetflag:false}, {hetflag:false}); $3Dmol.viewers['{$this->div_id}'].render();\">TS</button>\n";
        $nav .= "<button class='btn btn-default btn-xs align_right' style='width: 100%;' onClick=\"$3Dmol.viewers['{$this->div_id}'].removeAllSurfaces(); $3Dmol.viewers['{$this->div_id}'].render();\">RS</button>\n";

        $nav .= "</div>";

        return $nav;
    }

    public function getViewerDiv()
    {
        $output = "<div class='row'><div style='height:100px; position:relative;' class='col-xs-12 col-md-8 viewer_3Dmoljs' {$this->getDataString()} id={$this->div_id}></div>{$this->buildControls()}</div>";

        return $output;
    }



}
