<?php
/**
 * \Elabftw\Elabftw\DatabaseView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;
use \Datetime;

/**
 * Database View
 */
class DatabaseView
{
    /** object holding class Database */
    private $database;

    /** the database array with data */
    private $item;

    /** ID of the item we want to view */
    private $id;

    /** the Uploads class */
    private $uploads;

    /** the Users class */
    private $users;

    /** Revisions class */
    private $revisions;

    /** the Status class */
    private $status;


    /**
     * Need an ID of an item
     *
     * @param Database $database
     * @param int|null $id
     * @throws Exception
     */
    public function __construct(Database $database)
    {
        $this->database = $database;

        $this->status = new Status();
        $this->uploads = new Uploads();
        $this->revisions = new Revisions($this->database->id, 'items');
    }

    /**
     * View item
     *
     * @return string HTML for viewDB
     */
    public function view()
    {
        $html = '';

        $html .= $this->buildView();
        $html .= $this->uploads->buildUploads($this->database->id, 'view', 'items');
        $html .= $this->buildViewJs();

        return $html;
    }
    /**
     * Edit item
     *
     * @return string HTML for editDB
     */
    public function edit()
    {
        $itemArr = $this->database->read();
        // a locked item cannot be edited
        if ($itemArr['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }
        $html = $this->buildEdit();
        $html .= $this->uploads->buildUploadForm($this->database->id, 'items');
        $html .= $this->uploads->buildUploads($this->database->id, 'edit', 'items');
        $html .= $this->buildEditJs();

        return $html;
    }

    /**
     * Generate HTML for view DB
     *
     * @return string
     */
    private function buildView()
    {
        $itemArr = $this->database->read();

        $html = "<section class='box'>";
        $html .= "<span class='date_view'><img src='img/calendar.png' title='date' alt='Date :' /> ";
        $html .= Tools::formatDate($itemArr['date']) . "</span><br>";
        $html .= show_stars($itemArr['rating']);
        // buttons
        $html .=  "<a href='database.php?mode=edit&id=" . $itemArr['itemid'] . "'><img src='img/pen-blue.png' title='edit' alt='edit' /></a> 
        <a href='app/duplicate_item.php?id=".$itemArr['itemid'] . "&type=db'><img src='img/duplicate.png' title='duplicate item' alt='duplicate' /></a> 
        <a href='make.php?what=pdf&id=".$itemArr['itemid'] . "&type=items'><img src='img/pdf.png' title='make a pdf' alt='pdf' /></a> 
        <a href='make.php?what=zip&id=".$itemArr['itemid'] . "&type=items'><img src='img/zip.png' title='make a zip archive' alt='zip' /></a>
        <a href='experiments.php?mode=show&related=".$itemArr['itemid'] . "'><img src='img/link.png' alt='Linked experiments' title='Linked experiments' /></a> ";
        // lock
        if ($itemArr['locked'] == 0) {
            $html .= "<a href='app/lock.php?id=" . $itemArr['itemid'] . "&action=lock&type=items'><img src='img/unlock.png' title='lock item' alt='lock' /></a>";
        } else { // item is locked
            $html .= "<a href='app/lock.php?id=" . $itemArr['itemid'] . "&action=unlock&type=items'><img src='img/lock-gray.png' title='unlock item' alt='unlock' /></a>";
        }
        // TAGS
        $html .= " " . show_tags($this->database->id, 'items_tags');

        // TITLE : click on it to go to edit mode
        $html .= "<div ";
        if ($itemArr['locked'] == 0) {
            $html .= " onClick=\"document.location='database.php?mode=edit&id=" . $itemArr['itemid'] . "'\" ";
        }
        $html .= "class='title_view'>";
        $html .= "<span style='color:#" . $itemArr['bgcolor'] . "'>" . $itemArr['name'] . " </span>";
        $html .= stripslashes($itemArr['title']);
        $html .= "</div>";
        // BODY (show only if not empty)
        if ($itemArr['body'] != '') {
            // TODO don't put js in here but bottom of page
            $html .= "<div ";
            if ($itemArr['locked'] == 0) {
                $html .=  " onClick=\"document.location='database.php?mode=edit&id=" . $itemArr['itemid'] . "'\" ";
            }
            $html .= "id='body_view' class='txt'>" . stripslashes($itemArr['body']) . "</div>";
        }
        // SHOW USER
        $html .=  _('Last modified by') . ' ' . $itemArr['firstname'] . " " . $itemArr['lastname'];
        $html .=  "</section>";

        return $html;
    }

    /**
     * Generate JS code for view DB
     *
     * @return string
     */
    private function buildViewJs()
    {
        $html = '';
        // TODO same as in experimentsview
        if ($_SESSION['prefs']['chem_editor']) {
            $html .= "<script src='js/chemdoodle.js'></script>
            <script src='js/chemdoodle-uis.js'></script>
                    <script>
                        ChemDoodle.iChemLabs.useHTTPS();
                    </script>";
        }

        return $html;
    }

    /**
     * Generate HTML for edit DB
     *
     * @return string
     */
    private function buildEdit()
    {
        $itemArr = $this->database->read();

        // load tinymce
        $html = "<script src='js/tinymce/tinymce.min.js'></script>";

        // begin page
        $html .= "<section class='box' style='border-left: 6px solid #" . $itemArr['bgcolor'] . "'>";
        $html .= "<img class='align_right' src='img/big-trash.png' title='delete' alt='delete' onClick=\"deleteThis('" . $this->database->id . "','item', 'database.php')\" />";

        // tags
        $html .= displayTags('items', $this->database->id);

        // main form
        $html .= "<form method='post' action='app/controllers/DatabaseController.php' enctype='multipart/form-data'>";
        $html .= "<input name='databaseUpdate' type='hidden' value='true' />";
        $html .= "<input name='databaseId' type='hidden' value='" . $this->database->id . "' />";

        // date
        $html .= "<div class='row'>";
        $html .= "<div class='col-md-4'>";
        $html .= "<img src='img/calendar.png' class='bot5px' title='date' alt='Date :' />";
        $html .= "<label for='datepicker'>" . _('Date') . "</label>";
        // TODO if firefox has support for it: type = date
        $html .= "<input name='databaseUpdateDate' id='datepicker' size='8' type='text' value='" . $itemArr['date'] . "' />";
        $html .= "</div></div>";

        // star rating
        $html .= "<div class='align_right'>";
        for ($i = 1;$i < 6;$i++) {
            $html .= "<input id='star" . $i . "' name='star' type='radio' class='star' value='" . $i ."'";
            if ($itemArr['rating'] == $i) {
                $html .= 'checked=checked';
            }
            $html .= "/>";
        }
        $html .= "</div>";

        // title
        $html .= "<h4>" . _('Title') . "</h4>";
        $html .= "<input id='title_input' name='databaseUpdateTitle' rows='1' value='" . stripslashes($itemArr['title']) . "' required />";

        // body
        $html .= "<h4>" . _('Infos') . "</h4>";
        $html .= "<textarea class='mceditable' name='databaseUpdateBody' rows='15' cols='80'>";
        $html .= stripslashes($itemArr['body']);
        $html .= "</textarea>";

        // submit button
        $html .= "<div class='center' id='saveButton'>";
        $html .= "<button type='submit' name='Submit' class='button'>";
        $html .= _('Save and go back') . "</button></div></form>";

        // revisions
        $html .= $this->revisions->showCount();

        $html .= "</section>";

        // CHEM EDITOR
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
     * Build the JS code for edit mode
     *
     * @return string
     */
    private function buildEditJs()
    {
        $tags = new Tags('items');

        $html = "<script>
        // DELETE TAG
        function delete_tag(tag_id,item_id){
            var you_sure = confirm('" . _('Delete this?') . "');
            if (you_sure == true) {
                $.post('app/delete.php', {
                    id:tag_id,
                    item_id:item_id,
                    type:'itemtag'
                })
                .success(function() {
                    $('#tags_div').load('database.php?mode=edit&id=' + item_id + ' #tags_div');
                })
            }
            return false;
        }
        // STAR RATINGS
        function updateRating(rating) {
            // POST request
            $.post('app/star-rating.php', {
                star: rating,
                item_id: " . $this->database->id . "
            })
            // reload the div
            .success(function () {
                return false;
            })
        }

        // READY ? GO !
        $(document).ready(function() {
            // ADD TAG JS
            // listen keypress, add tag when it's enter
            $('#createTagInput').keypress(function (e) {
                createTag(e, " . $this->database->id . ", 'items');
            });

            // autocomplete the tags
            $('#createTagInput').autocomplete({
                source: [" . $tags->generateTagList('items') . "]
            });

            // If the title is 'Untitled', clear it on focus
            $('#title_input').focus(function(){
                if ($(this).val() === 'Untitled') {
                    $('#title_input').val('');
                }
            });

            // EDITOR
            tinymce.init({
                mode : 'specific_textareas',
                editor_selector : 'mceditable',
                content_css : 'css/tinymce.css',
                plugins : 'table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link pagebreak mention',
                pagebreak_separator: '<pagebreak>',
                toolbar1: 'undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | image link | save',
                removed_menuitems : 'newdocument',
                // save button :
                save_onsavecallback: function() {
                    $.post('app/quicksave.php', {
                        id : " . $this->database->id . ",
                        type : 'items',
                        // we need this to get the updated content
                        title : document.getElementById('title_input').value,
                        date : document.getElementById('datepicker').value,
                        body : tinymce.activeEditor.getContent()
                    }).success(function(data) {
                        if (data == 1) {
                            notif('" . _('Saved') . "', 'ok');
                        } else {
                            notif('" . _('Something went wrong! :(') . "', 'ko');
                        }
                    });
                },
                // keyboard shortcut to insert today's date at cursor in editor
                setup : function(editor) {
                    editor.addShortcut('ctrl+shift+d', 'add date at cursor', function() { addDateOnCursor(); });
                },
                language : '" . $_SESSION['prefs']['lang'] . "',
                mentions: {
                    source: [" . getDbList('mention') . "],
                    delimiter: '#'
                },
                style_formats_merge: true,
                style_formats: [
                    {
                        title: 'Image Left',
                        selector: 'img',
                        styles: {
                            'float': 'left',
                            'margin': '0 10px 0 10px'
                        }
                     },
                     {
                         title: 'Image Right',
                         selector: 'img',
                         styles: {
                             'float': 'right',
                             'margin': '0 0 10px 10px'
                         }
                     }
                ]

            });
        // DATEPICKER
        $( '#datepicker' ).datepicker({dateFormat: 'yymmdd'});
        // STARS
        $('input.star').rating();
        $('#star1').click(function() {
            updateRating(1);
        });
        $('#star2').click(function() {
            updateRating(2);
        });
        $('#star3').click(function() {
            updateRating(3);
        });
        $('#star4').click(function() {
            updateRating(4);
        });
        $('#star5').click(function() {
            updateRating(5);
        });";
        // ask the user if he really wants to navigate out of the page
        if (isset($_SESSION['prefs']['close_warning']) && $_SESSION['prefs']['close_warning'] === 1) {
            $html .= "window.onbeforeunload = function (e) {
                      e = e || window.event;
                      return '"._('Do you want to navigate away from this page? Unsaved changes will be lost!') . "';
                };";
        }
        $html .= "});</script>";

        return $html;

    }
}
