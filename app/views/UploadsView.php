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

/**
 * Experiments View
 * @deprecated should be a twig template
 */
class UploadsView
{
    /** the Uploads object */
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
    public function displayUpload($mode, $upload)
    {
        $html = '';

        // list of extensions with a corresponding app/img/thumb-*.png image
        $commonExtensions = array('avi', 'csv', 'doc', 'docx', 'mov', 'pdf', 'ppt', 'rar', 'xls', 'xlsx', 'zip');

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

        $html .= "<div class='col-md-4 col-sm-6'>";
        $html .= "<div class='thumbnail' data-type='" . $this->Uploads->Entity->type .
            "' data-id='" . $this->Uploads->Entity->id . "'>";
        // show the delete button only in edit mode, not in view mode
        if ($mode === 'edit') {
            $html .= "<a class='align_right' onClick=\"uploadsDestroy(" . $upload['id'] . "
                , '" . $upload['type'] . "', " . $upload['item_id'] . ", '" . _('Delete this?') . "')\">";
            $html .= "<img src='app/img/small-trash.png' title='delete' alt='delete' /></a>";
        } // end if it is in edit mode

        // get file extension
        $ext = Tools::getExt($upload['real_name']);
        $filepath = 'uploads/' . $upload['long_name'];
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
            if ($upload['comment'] != 'Click to add a comment') {
                $html .= "title='" . $upload['comment'] . "' data-caption='" . $upload['comment'] . "'";
            }
            $html .= "><img class='thumb' src='" . $thumbpath . "' alt='thumbnail' /></a>";

        // not an image
        } elseif (in_array($ext, $commonExtensions)) {
            $html .= "<img class='thumb' src='app/img/thumb-" . $ext . ".png' alt='' />";

        // special case for mol files, only in view mode
        } elseif ($ext === 'mol' && $this->Uploads->Entity->Users->userData['chem_editor'] && $mode === 'view') {
            // we need to escape \n in the mol file or we get unterminated string literal error in JS
            $mol = str_replace("\n", "\\n", file_get_contents($filepath));
            $html .= "<div class='center'><script>
                  showMol('" . $mol . "');
                  </script></div>";

        // if this is something 3Dmol.js can handle
        } elseif (in_array($ext, $molExtensions)) {
            // try to be clever and choose stick representation for
            // all files that are not in pdb format
            $style = 'stick';
            if ($ext === 'pdb') {
                $style = 'cartoon:color=spectrum';
            }
            $molviewer = new MolViewer($upload['id'], $filepath, false, $style);
            $html .= $molviewer->getViewerDiv();

        } else {
            // uncommon extension without a nice image to display
            $html .= "<img class='thumb' src='app/img/thumb.png' alt='' />";
        }

        // now display the name + comment with icons
        $html .= "<div class='caption'><img src='app/img/attached.png' alt='attached' /> ";
        $linkUrl = "app/download.php?f=" . $upload['long_name'] . "&name=" . $upload['real_name'];
        $html .= "<a href='" . $linkUrl . "' target='_blank' rel='noopener'>" . $upload['real_name'] . "</a>";
        $html .= "<span class='smallgray' style='display:inline'> " .
            Tools::formatBytes(filesize('uploads/' . $upload['long_name'])) . "</span><br>";
        // if we are in view mode, we don't show the comment if it's the default text
        // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
        // your are in view mode

        if ($mode === 'edit' || ($upload['comment'] != 'Click to add a comment')) {
            $comment = "<img src='app/img/comment.png' alt='comment' />
                        <p class='editable inline' id='filecomment_" . $upload['id'] . "'>" .
            $upload['comment'] . "</p>";
            $html .= $comment;
        }

        if ($mode === 'edit' && preg_match('/(jpg|jpeg|png|gif|svg)$/i', $ext)) {
            $html .= "<div class='inserter clickable' data-link='" . $upload['long_name'] .
                "'><img src='app/img/show-more.png' /> <p class='inline'>" . _('Insert in text at cursor position') . "</p></div>";
        }
        $html .= "</div></div></div>";

        return $html;
    }
}
