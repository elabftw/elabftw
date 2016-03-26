<?php
/**
 * \Elabftw\Elabftw\Uploads
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * All about the file uploads
 */
class Uploads extends Entity
{
    /** pdo object */
    protected $pdo;

    public $type;

    public $itemId;

    protected $id;

    /**
     * Constructor
     *
     * @param string $type experiment or items
     * @param int $itemId
     * @param int|null $id ID of a single file
     */
    public function __construct($type, $itemId, $id = null)
    {
        $this->pdo = Db::getConnection();
        $this->type = $type;
        $this->itemId = $itemId;

        if (!is_null($id)) {
            $this->setId($id);
        }
    }

    /**
     * Read infos from an upload ID
     *
     * @return array
     */
    private function read()
    {
        // Check that the item we view has attached files
        $sql = "SELECT * FROM uploads WHERE id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':type', $this->type);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read all uploads for an item
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->itemId);
        $req->bindParam(':type', $this->type);
        $req->execute();

        return $req->fetchAll();
    }


    /**
     * Create a jpg thumbnail from images of type jpg, png or gif.
     *
     * @param string $src Path to the original file
     * @param string $ext Extension of the file
     * @param string $dest Path to the place to save the thumbnail
     * @param int $desiredWidth Width of the thumbnail (height is automatic depending on width)
     * @return null|false
     */
    private function makeThumb($src, $ext, $dest, $desiredWidth)
    {
        // we don't want to work on too big images
        // put the limit to 5 Mbytes
        if (filesize($src) > 5000000) {
            return false;
        }

        // the used fonction is different depending on extension
        if (preg_match('/(jpg|jpeg)$/i', $ext)) {
            $sourceImage = imagecreatefromjpeg($src);
        } elseif (preg_match('/(png)$/i', $ext)) {
            $sourceImage = imagecreatefrompng($src);
        } elseif (preg_match('/(gif)$/i', $ext)) {
            $sourceImage = imagecreatefromgif($src);
        } else {
            return false;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        // find the "desired height" of this thumbnail, relative to the desired width
        $desiredHeight = floor($height * ($desiredWidth / $width));

        // create a new, "virtual" image
        $virtualImage = imagecreatetruecolor($desiredWidth, $desiredHeight);

        // copy source image at a resized size
        imagecopyresized($virtualImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width, $height);

        // create the physical thumbnail image to its destination (85% quality)
        imagejpeg($virtualImage, $dest, 85);
    }

    /**
     * Destroy an upload
     *
     * @return bool
     */
    public function destroy()
    {
        $uploadArr = $this->read();

        if ($this->type === 'experiments') {
            // Check file id is owned by connected user
            if ($uploadArr['userid'] =! $_SESSION['userid']) {
                throw new Exception(_('This section is out of your reach!'));
            }
        } else {
            $User = new Users();
            $userArr = $User->read($_SESSION['userid']);
            if ($userArr['team'] != $_SESSION['team_id']) {
                throw new Exception(_('This section is out of your reach!'));
            }
        }

        // remove thumbnail
        $thumbPath = ELAB_ROOT . 'uploads/' . $uploadArr['long_name'] . '_th.jpg';
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        // now delete file from filesystem
        $filePath = ELAB_ROOT . 'uploads/' . $uploadArr['long_name'];
        unlink($filePath);

        // Delete SQL entry (and verify the type)
        // to avoid someone deleting files saying it's DB whereas it's exp
        $sql = "DELETE FROM uploads WHERE id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':type', $this->type);

        return $req->execute();
    }

    /**
     * Generate html for the upload form
     *
     */
    public function buildUploadForm()
    {
        $html = "<section class='box'>";
        $html .= "<img src='img/attached.png' class='bot5px'> ";
        $html .= "<h3 style='display:inline'>" . _('Attach a file') . "</h3>";
        $html .= "<form action='app/upload.php' class='dropzone' id='elabftw-dropzone'></form>";
        $html .= "</section>";

        $html .= "<script>
        // we need this to reload the #filesdiv (div displaying uploaded files)
        var type = '" . $this->type . "';
        if (type == 'items') {
            type = 'database';
        }
        var item_id = '" . $this->itemId . "';

        // config for dropzone, id is camelCased.
        Dropzone.options.elabftwDropzone = {
            // i18n message to user
            dictDefaultMessage: '" . _('Drop files here to upload') . "',
            maxFilesize: '" . Tools::returnMaxUploadSize() . "', // MB
            init: function() {

                // add additionnal parameters (id and type)
                this.on('sending', function(file, xhr, formData) {
                    formData.append('item_id', '" . $this->itemId . "');
                    formData.append('type', '" . $this->type . "');
                });

                // once it is done
                this.on('complete', function(answer) {
                    // check the answer we get back from app/uploads.php
                    if (answer.xhr.responseText != 0) {
                        alert('Upload failed: ' + answer.xhr.responseText);
                    }
                    // reload the #filesdiv once the file is uploaded
                    if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                        $('#filesdiv').load(type + '.php?mode=edit&id=' + item_id + ' #filesdiv', function() {
                            // make the comment zone editable (fix issue #54)
                            $('.thumbnail p.editable').editable('app/editinplace.php', {
                             indicator : 'Saving...',
                             id   : 'id',
                             name : 'filecomment',
                             submit : 'Save',
                             cancel : 'Cancel',
                             style : 'display:inline'
                            });
                        });
                    }
                });
            }
        };
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
        $uploadsArr = $this->readAll();

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
        $html .= "<img src='img/attached.png' class='bot5px'> <h3 style='display:inline'>" .
            ngettext('Attached file', 'Attached files', $count) . "</h3>";
        $html .= "<div class='row'>";
        foreach ($uploadsArr as $upload) {
            $html .= "<div class='col-md-4 col-sm-6'>";
            $html .= "<div class='thumbnail'>";
            // show the delete button only in edit mode, not in view mode
            if ($mode === 'edit') {
                $html .= "<a class='align_right' onClick=\"uploadsDestroy(" . $upload['id'] . "
                    , '" . $upload['type'] . "', " . $upload['item_id'] . ", '" . _('Delete this?') . "')\">";
                $html .= "<img src='img/small-trash.png' title='delete' alt='delete' /></a>";
            } // end if it is in edit mode

            // get file extension
            $ext = Tools::getExt($upload['real_name']);
            $filepath = 'uploads/' . $upload['long_name'];
            $thumbpath = $filepath . '_th.jpg';

            // list of extensions with a corresponding img/thumb-*.png image
            $commonExtensions = array('avi', 'csv', 'doc', 'docx', 'mov', 'pdf', 'ppt', 'rar', 'xls', 'xlsx', 'zip');

            // list of extensions understood by 3Dmol.js
            $molExtensions = array('pdb', 'sdf', 'mol2', 'mmcif', 'cif');

            // Make thumbnail only if it isn't done already
            if (!file_exists($thumbpath)) {
                $this->makeThumb($filepath, $ext, $thumbpath, 100);
            }

            // only display the thumbnail if the file is here
            if (file_exists($thumbpath) && preg_match('/(jpg|jpeg|png|gif)$/i', $ext)) {
                // we add rel='gallery' to the images for fancybox to display it as an album
                // (possibility to go next/previous)
                $html .= "<a href='app/download.php?f=" . $upload['long_name'] . "' class='fancybox' rel='gallery' ";
                if ($upload['comment'] != 'Click to add a comment') {
                    $html .= "title='" . $upload['comment'] . "'";
                }
                $html .= "><img class='thumb' src='" . $thumbpath . "' alt='thumbnail' /></a>";

            // not an image
            } elseif (in_array($ext, $commonExtensions)) {
                $html .= "<img class='thumb' src='img/thumb-" . $ext . ".png' alt='' />";

            // special case for mol files, only in view mode
            } elseif ($ext === 'mol' && $_SESSION['prefs']['chem_editor'] && $mode === 'view') {
                // we need to escape \n in the mol file or we get unterminated string literal error in JS
                $mol = str_replace("\n", "\\n", file_get_contents($filepath));
                $html .= "<div class='center'><script>
                      showMol('" . $mol . "');
                      </script></div>";

            // if this is something 3Dmol.js can handle
            } elseif (in_array($ext, $molExtensions)) {
                $molviewer = new MolViewer($upload['id'], $filepath);
                $html .= $molviewer->getViewerDiv();

            } else {
                // uncommon extension without a nice image to display
                $html .= "<img class='thumb' src='img/thumb.png' alt='' />";
            }

            // now display the name + comment with icons
            $html .= "<div class='caption'><img src='img/attached.png' class='bot5px' alt='attached' /> ";
            $html .= "<a href='app/download.php?f=" . $upload['long_name'] .
                "&name=" . $upload['real_name'] . "' target='_blank'>" . $upload['real_name'] . "</a>";
            $html .= "<span class='smallgray' style='display:inline'> " .
                Tools::formatBytes(filesize('uploads/' . $upload['long_name'])) . "</span><br>";
            // if we are in view mode, we don't show the comment if it's the default text
            // this is to avoid showing 'Click to add a comment' where in fact you can't click to add a comment because
            // your are in view mode

            if ($mode === 'edit' || ($upload['comment'] != 'Click to add a comment')) {
                $comment = "<img src='img/comment.png' class='bot5px' alt='comment' />
                            <p class='editable inline' id='filecomment_" . $upload['id'] . "'>" .
                stripslashes($upload['comment']) . "</p>";
                $html .= $comment;
            }
            $html .= "</div></div></div>";
        } // end foreach
        $html .= "</div></div></div>";

        // add fancy stuff in edit mode
        if ($mode === 'edit') {
            $html .= "<script>
                $('.thumbnail').on('mouseover', '.editable', function(){
                $('.thumbnail p.editable').editable('app/editinplace.php', {
                 tooltip : 'Click to edit',
                 indicator : 'Saving...',
                 name : 'filecomment',
                 submit : 'Save',
                 cancel : 'Cancel',
                 style : 'display:inline'
                });
            });</script>";
        }
        $html .= "<script>$(document).ready(function() {
                // we use fancybox to display thumbnails
                $('a.fancybox').fancybox();
            });
            </script>";
        return $html;
    }
}
