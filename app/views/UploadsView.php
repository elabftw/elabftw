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
 */
class UploadsView extends EntityView
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
     * Generate html for the upload form
     *
     */
    public function buildUploadForm()
    {
        $page = 'experiments';
        if ($this->Uploads->Entity->type === 'items') {
            $page = 'database';
        }
        $html = "<section class='box'>";
        $html .= "<img src='app/img/attached.png' /> ";
        $html .= "<h3 style='display:inline'>" . _('Attach a file') . "</h3>";
        $html .= "<form action='app/controllers/EntityController.php' class='dropzone' id='elabftw-dropzone'></form>";
        $html .= "</section>";

        $html .= "<script>
        // config for dropzone, id is camelCased.
        Dropzone.options.elabftwDropzone = {
            // i18n message to user
            dictDefaultMessage: '" . _('Drop files here to upload') . "',
            maxFilesize: '" . Tools::returnMaxUploadSize() . "', // MB
            init: function() {

                // add additionnal parameters (id and type)
                this.on('sending', function(file, xhr, formData) {
                    formData.append('upload', true);
                    formData.append('id', '" . $this->Uploads->Entity->id . "');
                    formData.append('type', '" . $this->Uploads->Entity->type . "');
                });

                // once it is done
                this.on('complete', function(answer) {
                    // check the answer we get back from app/controllers/EntityController.php
                    var json = JSON.parse(answer.xhr.responseText);
                    if (json.res) {
                        notif(json.msg, 'ok');
                    } else {
                        notif(json.msg, 'ko');
                    }
                    // reload the #filesdiv once the file is uploaded
                    if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                        $('#filesdiv').load('" . $page .
                            ".php?mode=edit&id=" . $this->Uploads->Entity->id . " #filesdiv', function() {
                            // make the comment zone editable (fix issue #54)
                            makeEditableFileComment('" . $this->Uploads->Entity->type . "', " .
                                $this->Uploads->Entity->id . ");
                        });
                    }
                });
            }
        }
            </script>";
        return $html;
    }

    /**
     * Generate HTML for displaying uploaded files
     *
     * @param string $mode edit or view
     * @return string html
     */
    public function buildUploads($mode)
    {
        $uploadsArr = $this->Uploads->readAll();

        $count = count($uploadsArr);
        if ($count < 1) {
            // return the empty div so it can be reloaded upon file upload
            return "<div id='filesdiv'></div>";
        }

        // this is for the plural of the ngettext function below
        if ($count > 1) {
            $count = 2;
        }

        // begin HTML build
        $html = "<div id='filesdiv'>";
        $html .= "<div class='box'>";
        $html .= "<img src='app/img/attached.png' /> <h3 style='display:inline'>" .
            ngettext('Attached file', 'Attached files', $count) . "</h3>";
        $html .= "<div class='row'>";
        foreach ($uploadsArr as $upload) {
            $html .= "<div class='col-md-4 col-sm-6'>";
            $html .= "<div class='thumbnail'>";
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

            // list of extensions with a corresponding app/img/thumb-*.png image
            $commonExtensions = array('avi', 'csv', 'doc', 'docx', 'mov', 'pdf', 'ppt', 'rar', 'xls', 'xlsx', 'zip');

            // list of extensions understood by 3Dmol.js
            $molExtensions = array('pdb', 'sdf', 'mol2', 'mmcif', 'cif');

            // Make thumbnail only if it isn't done already
            if (!file_exists($thumbpath)) {
                $this->Uploads->makeThumb($filepath, $thumbpath, 100);
            }

            // only display the thumbnail if the file is here
            if (file_exists($thumbpath) && preg_match('/(jpg|jpeg|png|gif)$/i', $ext)) {
                // if it's a picture, we display it with fancybox
                // see: http://fancyapps.com/fancybox/3/docs/
                $html .= "<a href='app/download.php?f=" . $upload['long_name'] . "' data-fancybox='group' ";
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
            $html .= "<a href='app/download.php?f=" . $upload['long_name'] .
                "&name=" . $upload['real_name'] . "' target='_blank'>" . $upload['real_name'] . "</a>";
            $html .= "<span class='smallgray' style='display:inline'> " .
                Tools::formatBytes(filesize('uploads/' . $upload['long_name'])) . "</span><br>";
            // if we are in view mode, we don't show the comment if it's the default text
            // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
            // your are in view mode

            if ($mode === 'edit' || ($upload['comment'] != 'Click to add a comment')) {
                $comment = "<img src='app/img/comment.png' alt='comment' />
                            <p class='editable inline' id='filecomment_" . $upload['id'] . "'>" .
                stripslashes($upload['comment']) . "</p>";
                $html .= $comment;
            }
            $html .= "</div></div></div>";
        } // end foreach
        $html .= "</div></div></div>";

        $html .= "<script>$(document).ready(function() {
                // we use fancybox to display thumbnails
                $('a.fancybox').fancybox();";

        // add editable comments in edit mode
        if ($mode === 'edit') {
            $html .= "$('.thumbnail').on('mouseover', '.editable', function(){
                    makeEditableFileComment('" . $this->Uploads->Entity->type . "', " . $this->Uploads->Entity->id . ");
                });";
        }
        $html .= "});</script>";
        return $html;
    }
}
