<?php
/**
 * \Elabftw\Elabftw\DatabaseView
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
 * Database View
 */
class DatabaseView extends EntityView
{
    /** the UploadsView class */
    private $UploadsView;

    /** Revisions class */
    private $Revisions;

    /** can be tag, query or filter */
    public $searchType = '';


    /**
     * Need an ID of an item
     *
     * @param Database $database
     * @param int $userid
     * @throws Exception
     */
    public function __construct(Database $database, $userid)
    {
        $this->Database = $database;
        $this->limit = $_SESSION['prefs']['limit'];

        $this->UploadsView = new UploadsView(new Uploads('items', $this->Database->id));
        $this->Revisions = new Revisions('items', $this->Database->id, $userid);
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
        $html .= $this->UploadsView->buildUploads('view');
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
        $itemArr = $this->Database->read();
        // a locked item cannot be edited
        if ($itemArr['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }
        $html = $this->buildEdit();
        $html .= $this->UploadsView->buildUploadForm();
        $html .= $this->UploadsView->buildUploads('edit');
        $html .= $this->buildEditJs();

        return $html;
    }

    /**
     * Generate HTML for show DB
     * we have html and html2 because to build html we need the idArr
     * from html2
     *
     * @return string
     */
    public function buildShow()
    {
        $html = '';
        $html2 = '';

        // get all DB items for the team
        $itemsArr = $this->Database->readAll();

        $total_time = get_total_time();

        // loop the results array and display results
        $idArr = array();
        foreach ($itemsArr as $item) {

            // fill an array with the ID of each item to use in the csv/zip export menu
            $idArr[] = $item['itemid'];

            $html2 .= $this->showUnique($item);
        }

        // show number of results found
        $count = count($itemsArr);
        if ($count === 0 && $this->searchType != '') {
            return display_message('ko_nocross', _("Sorry. I couldn't find anything :("));
        } elseif ($count === 0 && $this->searchType === '') {
            return display_message('ok_nocross', sprintf(_('Welcome to eLabFTW. Head to the %sAdmin Panel%s to create a new type of item.'), "<a href='admin.php?tab=4'>", "</a>"));
        } else {
            $html .= $this->buildExportMenu($idArr, 'items');

            $html .= "<p class='smallgray'>" . $count . " " .
                ngettext("result found", "results found", $count) . " (" .
                $total_time['time'] . " " . $total_time['unit'] . ")</p>";
        }
        $load_more_button = "<div class='center'>
            <button class='button' id='loadButton'>" . sprintf(_('Show %s more'), $this->limit) . "</button>
            <button class='button button-neutral' id='loadAllButton'>". _('Show all') . "</button>
            </div>";
        // show load more button if there are more results than the default display number
        if ($count > $this->limit) {
            $html2 .= $load_more_button;
        }
        $html .= $this->buildShowJs('database');
        return $html . $html2;
    }

    /**
     * Show an item
     *
     * @param int $item ID of the item to show
     * @return string
     */
    public function showUnique($item)
    {
        $html = "<section class='item " . $this->display . "' style='border-left: 6px solid #" . $item['bgcolor'] . "'>";
        $html .= "<a href='database.php?mode=view&id=" . $item['itemid'] . "'>";

        // show attached if there is a file attached
        if ($item['attachment']) {
            $html .= "<img style='clear:both' class='align_right' src='app/img/attached.png' alt='file attached' />";
        }
        // STARS
        $html .= $this->showStars($item['rating']);
        $html .= "<p class='title'>";
        // LOCK
        if ($item['locked']) {
            $html .= "<img style='padding-bottom:3px;' src='app/img/lock-blue.png' alt='lock' />";
        }
        // TITLE
        $html .= $item['title'] . "</p></a>";
        // ITEM TYPE
        $html .= "<span style='text-transform:uppercase;font-size:80%;padding-left:20px;color:#" . $item['bgcolor'] . "'>" . $item['name'] . " </span>";
        // DATE
        $html .= "<span class='date'><img class='image' src='app/img/calendar.png' /> " . Tools::formatDate($item['date']) . "</span> ";
        // TAGS
        $html .= $this->showTags('items', 'view', $item['itemid']);

        $html .= "</section>";

        return $html;
    }

    /**
     * Generate HTML for view DB
     *
     * @return string
     */
    private function buildView()
    {
        $itemArr = $this->Database->read();
        $html = '';

        $html .= $this->backToLink('database');

        $html .= "<section class='box'>";
        $html .= "<div><img src='app/img/calendar.png' title='date' alt='Date :' /> ";
        $html .= Tools::formatDate($itemArr['date']) . "</div>";
        $html .= $this->showStars($itemArr['rating']);
        // buttons
        $html .= "<a class='elab-tooltip' href='database.php?mode=edit&id=" . $itemArr['itemid'] . "'><span>Edit</span><img src='app/img/pen-blue.png' alt='Edit' /></a> 
        <a class='elab-tooltip' href='app/controllers/DatabaseController.php?databaseDuplicateId=" . $itemArr['itemid'] . "'><span>Duplicate Item</span><img src='app/img/duplicate.png' alt='Duplicate' /></a> 
        <a class='elab-tooltip' href='make.php?what=pdf&id=" . $itemArr['itemid'] . "&type=items'><span>Make a PDF</span><img src='app/img/pdf.png' alt='PDF' /></a> 
        <a class='elab-tooltip' href='make.php?what=zip&id=" . $itemArr['itemid'] . "&type=items'><span>Make a ZIP</span><img src='app/img/zip.png' alt='ZIP' /></a>
        <a class='elab-tooltip' href='experiments.php?mode=show&related=".$itemArr['itemid'] . "'><span>Linked Experiments</span><img src='app/img/link.png' alt='Linked Experiments' /></a> ";
        // lock
        $imgSrc = 'unlock.png';
        $alt = _('Lock/Unlock item');
        if ($itemArr['locked'] != 0) {
            $imgSrc = 'lock-gray.png';
        }
        $html .= "<a class='elab-tooltip' href='#'><span>" . $alt . "</span><img id='lock' onClick=\"toggleLock('database', " . $itemArr['itemid'] . ")\" src='app/img/" . $imgSrc . "' alt='" . $alt . "' /></a>";
        // TAGS
        $html .= " " . $this->showTags('items', 'view', $this->Database->id);

        // TITLE : click on it to go to edit mode
        $html .= "<div ";
        if ($itemArr['locked'] === '0' || $itemArr['locked'] === NULL) {
            $html .= "onClick=\"document.location='database.php?mode=edit&id=" . $itemArr['itemid'] . "'\" ";
        }
        $html .= "class='title_view'>";
        $html .= "<span style='color:#" . $itemArr['bgcolor'] . "'>" . $itemArr['name'] . " </span>";
        $html .= $itemArr['title'];
        $html .= "</div>";
        // BODY (show only if not empty)
        if ($itemArr['body'] != '') {
            $html .= "<div ";
            if ($itemArr['locked'] === '0' || $itemArr['locked'] === NULL) {
                $html .= "onClick='go_url(\"database.php?mode=edit&id=" . $itemArr['itemid'] . "\")'";
            }
            $html .= " id='body_view' class='txt'>" . $itemArr['body'] . "</div>";
        }
        // SHOW USER
        $html .= _('Last modified by') . ' ' . $itemArr['firstname'] . " " . $itemArr['lastname'];
        $html .= "</section>";

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
        if ($_SESSION['prefs']['chem_editor']) {
            $html .= "<script src='js/chemdoodle/chemdoodle.min.js'></script>
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
        $itemArr = $this->Database->read();
        $html = '';


        // load tinymce
        $html .= "<script src='js/tinymce/tinymce.min.js'></script>";

        $html .= $this->backToLink('database');
        // begin page
        $html .= "<section class='box' style='border-left: 6px solid #" . $itemArr['bgcolor'] . "'>";
        $html .= "<img class='align_right' src='app/img/big-trash.png' title='delete' alt='delete' onClick=\"databaseDestroy(" . $this->Database->id . ", '" . _('Delete this?') . "')\" />";

        // tags
        $html .= $this->showTags('items', 'edit', $this->Database->id);

        // main form
        $html .= "<form method='post' action='app/controllers/DatabaseController.php' enctype='multipart/form-data'>";
        $html .= "<input name='update' type='hidden' value='true' />";
        $html .= "<input name='id' type='hidden' value='" . $this->Database->id . "' />";

        // date
        $html .= "<div class='row'>";
        $html .= "<div class='col-md-4'>";
        $html .= "<img src='app/img/calendar.png' title='date' alt='Date :' />";
        $html .= "<label for='datepicker'>" . _('Date') . "</label>";
        // if one day firefox has support for it: type = date
        $html .= "<input name='date' id='datepicker' size='8' type='text' value='" . $itemArr['date'] . "' />";
        $html .= "</div></div>";

        // star rating
        $html .= "<div class='align_right'>";
        for ($i = 1; $i < 6; $i++) {
            $html .= "<input id='star" . $i . "' name='star' type='radio' class='star' value='" . $i . "'";
            if ($itemArr['rating'] == $i) {
                $html .= 'checked=checked';
            }
            $html .= "/>";
        }
        $html .= "</div>";

        // title
        $html .= "<h4>" . _('Title') . "</h4>";
        $html .= "<input id='title_input' name='title' rows='1' value='" . $itemArr['title'] . "' required />";

        // body
        $html .= "<h4>" . _('Infos') . "</h4>";
        $html .= "<textarea class='mceditable' name='body' rows='15' cols='80'>";
        $html .= $itemArr['body'];
        $html .= "</textarea>";

        // submit button
        $html .= "<div class='center' id='saveButton'>";
        $html .= "<button type='submit' name='Submit' class='button'>";
        $html .= _('Save and go back') . "</button></div></form>";

        // revisions
        $html .= $this->Revisions->showCount();

        $html .= "</section>";

        $html .= $this->injectChemEditor();

        return $html;
    }

    /**
     * Build the JS code for edit mode
     *
     * @return string
     */
    private function buildEditJs()
    {
        $tags = new Tags('items', $this->Database->id);

        $html = "<script>
        // READY ? GO !
        $(document).ready(function() {
            // AUTOSAVE
            var typingTimer;                // timer identifier
            var doneTypingInterval = 7000;  // time in ms between end of typing and save

            // user finished typing, save work
            function doneTyping () {
                quickSave('items', " . $this->Database->id . ");
            }
            // ADD TAG JS
            // listen keypress, add tag when it's enter
            $('#createTagInput').keypress(function (e) {
                createTag(e, " . $this->Database->id . ", 'items');
            });

            // autocomplete the tags
            $('#createTagInput').autocomplete({
                source: [" . $tags->generateTagList() . "]
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
                content_css : 'app/css/tinymce.css',
                plugins : 'table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link pagebreak mention',
                pagebreak_separator: '<pagebreak>',
                toolbar1: 'undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | image link | save',
                removed_menuitems : 'newdocument',
                // save button :
                save_onsavecallback: function() {
                    quickSave('items', " . $this->Database->id . ");
                },
                // keyboard shortcut to insert today's date at cursor in editor
                setup : function(editor) {
                    editor.addShortcut('ctrl+shift+d', 'add date at cursor', function() { addDateOnCursor(); });
                    editor.on('keydown', function(event) {
                        clearTimeout(typingTimer);
                    });
                    editor.on('keyup', function(event) {
                        clearTimeout(typingTimer);
                        typingTimer = setTimeout(doneTyping, doneTypingInterval);
                    });
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
        $('input.star').rating();";
        for ($i = 1; $i < 6; $i++) {
            $html .= "$('#star" . $i . "').click(function() {
            updateRating(" . $i . ", " . $this->Database->id . ");
        });";
        }

        $html .= $this->injectCloseWarning();
        $html .= "});</script>";

        return $html;

    }

    /**
     * Display the stars rating for a DB item
     *
     * @param int $rating The number of stars to display
     * @return string HTML of the stars
     */
    private function showStars($rating)
    {
        $html = "<span class='align_right'>";

        $green = "<img src='app/img/star-green.png' alt='☻' />";
        $gray = "<img src='app/img/star-gray.png' alt='☺' />";

        $html .= str_repeat($green, $rating);
        $html .= str_repeat($gray, (5 - $rating));

        $html .= "</span>";

        return $html;
    }
}
