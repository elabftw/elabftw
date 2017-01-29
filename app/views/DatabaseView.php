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
    /**
     * Need a Database object
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->Entity = $entity;
        $this->limit = $_SESSION['prefs']['limit'];

    }

    /**
     * View item
     *
     * @return string HTML for viewDB
     */
    public function view()
    {
        $this->initViewEdit();

        $this->html .= $this->buildView();
        $this->html .= $this->UploadsView->buildUploads('view');

        return $this->html;
    }
    /**
     * Edit item
     *
     * @return string HTML for editDB
     */
    public function edit()
    {
        $this->initViewEdit();

        // a locked item cannot be edited
        if ($this->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }
        $this->html .= $this->buildEdit();
        $this->html .= $this->UploadsView->buildUploadForm();
        $this->html .= $this->UploadsView->buildUploads('edit');
        $this->html .= $this->buildEditJs();

        return $this->html;
    }

    /**
     * Generate HTML for view DB
     *
     * @return string
     */
    private function buildView()
    {
        $html = '';

        $html .= $this->backToLink('database');

        $html .= "<section class='box'>";
        $html .= "<div><img src='app/img/calendar.png' title='date' alt='Date :' /> ";
        $html .= Tools::formatDate($this->Entity->entityData['date']) . "</div>";
        $html .= $this->showStars($this->Entity->entityData['rating']);
        // buttons
        $html .= "<a class='elab-tooltip' href='database.php?mode=edit&id=" . $this->Entity->entityData['id'] . "'><span>Edit</span><img src='app/img/pen-blue.png' alt='Edit' /></a> 
        <a class='elab-tooltip' href='app/controllers/DatabaseController.php?databaseDuplicateId=" . $this->Entity->entityData['id'] . "'><span>Duplicate Item</span><img src='app/img/duplicate.png' alt='Duplicate' /></a> 
        <a class='elab-tooltip' href='make.php?what=pdf&id=" . $this->Entity->entityData['id'] . "&type=items'><span>Make a PDF</span><img src='app/img/pdf.png' alt='PDF' /></a> 
        <a class='elab-tooltip' href='make.php?what=zip&id=" . $this->Entity->entityData['id'] . "&type=items'><span>Make a ZIP</span><img src='app/img/zip.png' alt='ZIP' /></a>
        <a class='elab-tooltip' href='experiments.php?mode=show&related=".$this->Entity->entityData['id'] . "'><span>Linked Experiments</span><img src='app/img/link.png' alt='Linked Experiments' /></a> ";
        // lock
        $imgSrc = 'unlock.png';
        $alt = _('Lock/Unlock item');
        if ($this->Entity->entityData['locked'] != 0) {
            $imgSrc = 'lock-gray.png';
        }
        $html .= "<a class='elab-tooltip' href='#'><span>" . $alt . "</span><img id='lock' onClick=\"toggleLock('database', " . $this->Entity->entityData['id'] . ")\" src='app/img/" . $imgSrc . "' alt='" . $alt . "' /></a>";
        // TAGS
        $html .= " " . $this->showTags('view');

        // CATEGORY

        // TITLE : click on it to go to edit mode
        $onClick = '';
        if ($this->Entity->entityData['locked'] === '0' || $this->Entity->entityData['locked'] === null) {
            $onClick .= "onClick=\"document.location='database.php?mode=edit&id=" . $this->Entity->entityData['id'] . "'\" ";
        }
        $html .= "<div " . $onClick . " class='title_view'>";
        $html .= "<span style='color:#" . $this->Entity->entityData['color'] . "'>" . $this->Entity->entityData['category'] . " </span>";
        $html .= $this->Entity->entityData['title'];
        $html .= "</div>";
        // BODY (show only if not empty)
        if ($this->Entity->entityData['body'] != '') {
            $html .= "<div " . $onClick . " id='body_view' class='txt'>" . $this->Entity->entityData['body'] . "</div>";
        }
        // SHOW USER
        $html .= _('Last modified by') . ' ' . $this->Entity->entityData['fullname'];
        $html .= "</section>";

        return $html;
    }

    /**
     * Generate HTML for edit DB
     *
     * @return string
     */
    private function buildEdit()
    {
        $html = '';
        // load tinymce
        $html .= "<script src='app/js/edit.mode.min.js'></script>";

        $html .= $this->backToLink('database');
        // begin page
        $html .= "<section class='box' style='border-left: 6px solid #" . $this->Entity->entityData['color'] . "'>";
        $html .= "<img class='align_right' src='app/img/big-trash.png' title='delete' alt='delete' onClick=\"databaseDestroy(" . $this->Entity->id . ", '" . _('Delete this?') . "')\" />";

        // tags
        $html .= $this->showTags('edit');

        // main form
        $html .= "<form method='post' action='app/controllers/DatabaseController.php' enctype='multipart/form-data'>";
        $html .= "<input name='update' type='hidden' value='true' />";
        $html .= "<input name='id' type='hidden' value='" . $this->Entity->id . "' />";

        // date
        $html .= "<div class='row'>";
        $html .= "<div class='col-md-4'>";
        $html .= "<img src='app/img/calendar.png' title='date' alt='Date :' />";
        $html .= "<label for='datepicker'>" . _('Date') . "</label>";
        // if one day firefox has support for it: type = date
        $html .= "<input name='date' id='datepicker' size='8' type='text' value='" . $this->Entity->entityData['date'] . "' />";
        $html .= "</div></div>";

        // star rating
        $html .= "<div class='align_right'>";
        for ($i = 1; $i < 6; $i++) {
            $html .= "<input id='star" . $i . "' name='star' type='radio' class='star' value='" . $i . "'";
            if ($this->Entity->entityData['rating'] == $i) {
                $html .= 'checked=checked';
            }
            $html .= "/>";
        }
        $html .= "</div>";

        // title
        $html .= "<h4>" . _('Title') . "</h4>";
        $html .= "<input id='title_input' name='title' rows='1' value='" . $this->Entity->entityData['title'] . "' required />";

        // body
        $html .= "<h4>" . _('Infos') . "</h4>";
        $html .= "<textarea class='mceditable' name='body' rows='15' cols='80'>";
        $html .= $this->Entity->entityData['body'];
        $html .= "</textarea>";

        // submit button
        $html .= "<div class='center' id='saveButton'>";
        $html .= "<button type='submit' name='Submit' class='button'>";
        $html .= _('Save and go back') . "</button></div></form>";

        // revisions
        $Revisions = new Revisions($this->Entity);
        $html .= $Revisions->showCount();

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
        $Tags = new Tags($this->Entity);

        $html = "<script>
        // READY ? GO !
        $(document).ready(function() {
            // AUTOSAVE
            var typingTimer;                // timer identifier
            var doneTypingInterval = 7000;  // time in ms between end of typing and save

            // user finished typing, save work
            function doneTyping () {
                quickSave('items', " . $this->Entity->id . ");
            }
            // ADD TAG JS
            // listen keypress, add tag when it's enter
            $('#createTagInput').keypress(function (e) {
                createTag(e, 'items', " . $this->Entity->id . ");
            });

            // autocomplete the tags
            $('#createTagInput').autocomplete({
                source: [" . $Tags->generateTagList('autocomplete') . "]
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
                    quickSave('items', " . $this->Entity->id . ");
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
                    delimiter: ['#', '$'],
                    source: function (query, process, delimiter) {
                                if (delimiter === '#') {
                                    process([" . getDbList('mention') . "]);
                                }
                                if (delimiter === '$') {
                                    process([" . getDbList('mention-user') . "]);
                                }
                            }
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
            updateRating(" . $i . ", " . $this->Entity->id . ");
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
