<?php
/**
 * \Elabftw\Elabftw\UploadsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Experiments View
 * @deprecated should be a twig template
 */
class UploadsView
{
    /** @var Uploads the Uploads object */
    public $Uploads;

    /**
     * Constructor
     *
     * @param Uploads $uploads
     */
    public function __construct(Uploads $uploads)
    {
        $this->Uploads = $uploads;
    }

    /**
     * Generate HTML for displaying an uploaded file
     *
     * @param string $mode edit or view
     * @param array $upload the item to display
     * @return string html
     */
    public function displayUpload(string $mode, array $upload): string
    {
        $html = '';

        // list of extensions understood by 3Dmol.js
        // see http://3dmol.csb.pitt.edu/doc/types.html
        $molExtensions = array(
            'cdjson',
            'cif',
            'cube',
            'gro',
            'json',
            'mcif',
            'mmtf',
            'mol2',
            'pdb',
            'pqr',
            'prmtop',
            'sdf',
            'vasp',
            'xyz'
        );

        // get file extension
        $ext = Tools::getExt($upload['real_name']);
        $filepath = \dirname(__DIR__, 2) . '/uploads/' . $upload['long_name'];
        if (!\is_readable($filepath)) {
            return "ERROR: file not found! (" . \substr($filepath, 0, 42) . "…)";
        }
        $thumbpath = $filepath . '_th.jpg';

        // Make thumbnail only if it isn't done already
        if (!file_exists($thumbpath)) {
            $this->Uploads->makeThumb($filepath, $thumbpath, 100);
        }

        // only display the thumbnail if the file is here
        if (file_exists($thumbpath) && preg_match('/(jpg|jpeg|png|gif|tif|tiff|pdf|eps|svg)$/i', $ext)) {
            // if it's a picture, we display it with fancybox
            // see: http://fancyapps.com/fancybox/3/docs/
            $fancybox = ' ';
            if (preg_match('/(jpg|jpeg|png|gif)$/i', $ext)) {
                $fancybox = " data-fancybox='group' ";
            }

            $html .= "<a href='app/download.php?f=" . $upload['long_name'] . "'" . $fancybox;
            if ($upload['comment'] !== 'Click to add a comment') {
                $html .= "title='" . $upload['comment'] . "' data-caption='" . $upload['comment'] . "'";
            }
            $html .= "><img class='thumb img-thumbnail rounded mx-auto d-block' src='app/download.php?f=" . $upload['long_name'] . "_th.jpg' alt='thumbnail' /></a>";

            // not an image
            // special case for mol files, only in view mode
        } elseif ($mode === 'view' && $ext === 'mol' && $this->Uploads->Entity->Users->userData['chem_editor']) {
            $html .= "<div class='text-center'><canvas class='molFile' id='molFile_" . $upload['id'] .
                "' data-molpath='app/download.php?f=" . $filepath . "'></canvas></div>";
        // if this is something 3Dmol.js can handle
        } elseif (in_array($ext, $molExtensions, true)) {
            $molStyle = "stick";
            // use cartoon representation for pdb files
            if ($ext === 'pdb') {
                $molStyle = "cartoon:color=spectrum";
            }
            // the id of the div with the representation of the molecule
            $molDivId = '3Dmol_' . $upload['id'];

            // build control dropdown to change the representation style
            $controls = "<div style='padding-bottom: 5px' class='btn-group'>";
            $controls .= "<button type='button' class='btn btn-secondary btn-xs dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>" . _('Style') . " <span class='caret'></span></button>";
            $controls .= "<ul class='dropdown-menu clickable'>";

            // Build dropdown menu
            $controls .= "<li class='dropdown-item'><span data-divid='" . $molDivId . "' class='3dmol-cartoon'>" . _('Cartoon (proteins only)') . "</span></li>";
            $controls .= "<li class='dropdown-item'><span data-divid='" . $molDivId . "' class='3dmol-cross'>" . _('Cross') . "</span/></li>";
            $controls .= "<li class='dropdown-item'><span data-divid='" . $molDivId . "' class='3dmol-line'>" . _('Line') . "</span/></li>";
            $controls .= "<li class='dropdown-item'><span data-divid='" . $molDivId . "' class='3dmol-sphere'>" . _('Sphere') . "</span/></li>";
            $controls .= "<li class='dropdown-item'><span data-divid='" . $molDivId . "' class='3dmol-stick'>" . _('Stick') . "</span/></li>";

            $controls .= "</ul>";
            $controls .= "</div>";
            $molDiv = "<div class='row viewer_3Dmoljs' data-href='app/download.php?f=" . $filepath .
                "' data-style='" . $molStyle . "' data-backgroundcolor='0xffffff' id='" . $molDivId . "'></div>";
            $html .= $controls . $molDiv;

        } else {
            $html .= "<i class='fas " . Tools::getIconFromExtension($ext) . " thumb rounded mx-auto d-block'></i>";
        }

        // NOW DISPLAY THE NAME + COMMENT WITH ICONS
        $html .= "<div class='caption'><i class='fas fa-download mr-1'></i>";
        $linkUrl = 'app/download.php?f=' . $upload['long_name'] . '&name=' . $upload['real_name'];
        $html .= "<a href='" . $linkUrl . "' rel='noopener'>" . $upload['real_name'] . "</a>";
        $html .= "<span class='smallgray' style='display:inline'> " .
            Tools::formatBytes((int) filesize(\dirname(__DIR__, 2) . '/uploads/' . $upload['long_name'])) . "</span><br>";
        // if we are in view mode, we don't show the comment if it's the default text
        // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
        // your are in view mode

        if ($mode === 'edit' || ($upload['comment'] != 'Click to add a comment')) {
            $comment = "<i class='fas fa-comments'></i>
                        <p class='file-comment editable d-inline' data-type='" . $upload['type'] . "' data-itemid='" . $upload['item_id'] . "' id='filecomment_" . $upload['id'] . "'>" .
            $upload['comment'] . "</p>";
            $html .= $comment;
        }

        // INSERT IN TEXT
        if ($mode === 'edit' && preg_match('/(jpg|jpeg|png|gif|svg)$/i', $ext)) {
            $html .= "<div class='inserter clickable' data-link='" . $upload['long_name'] .
                "'><i class='fas fa-image mr-1'></i><p class='d-inline'>" . _('Insert in text at cursor position') . "</p></div>";
        }

        $html .= "</div>";

        return $html;
    }
}
