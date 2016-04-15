<?php
/**
 * \Elabftw\Elabftw\ExperimentsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Datetime;

/**
 * Experiments View
 */
class ExperimentsView extends EntityView
{
    /** our Experiments instance */
    public $Experiments;

    /** the experiment array with data */
    private $experiment;

    /** Read only switch */
    private $ro = false;

    /** the UploadsView object */
    private $UploadsView;

    /** instance of TeamGroups */
    private $teamGroups;

    /** can be tag, query or filter */
    public $searchType = '';

    /** are we looking for exp related to an item ? */
    public $related = 0;

    /**
     * Need an ID of an experiment
     *
     * @param Experiments $experiments
     * @throws Exception
     */
    public function __construct(Experiments $experiments)
    {
        $this->Experiments = $experiments;
        $this->limit = $_SESSION['prefs']['limit'];

        $this->teamGroups = new TeamGroups();
    }

    /**
     * View experiment
     *
     * @return string HTML for viewXP
     */
    public function view()
    {
        // get data of experiment
        $this->experiment = $this->Experiments->read();
        $this->UploadsView = new UploadsView(new Uploads('experiments', $this->experiment['id']));

        $html = '';

        $this->ro = $this->isReadOnly();

        if ($this->experiment['timestamped']) {
            $html .= $this->showTimestamp();
        }

        $html .= $this->buildView();
        $html .= $this->UploadsView->buildUploads('view');
        $html .= $this->buildComments();
        $html .= $this->buildCommentsCreate();
        $html .= $this->buildViewJs();

        return $html;
    }
    /**
     * Edit experiment
     *
     * @return string
     */
    public function edit()
    {
        // get data of experiment
        $this->experiment = $this->Experiments->read();
        $this->UploadsView = new UploadsView(new Uploads('experiments', $this->experiment['id']));

        // only owner can edit an experiment
        if (!$this->isOwner()) {
            throw new Exception(_('<strong>Cannot edit:</strong> this experiment is not yours!'));
        }

        // a locked experiment cannot be edited
        if ($this->experiment['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }
        $html = $this->buildEdit();
        $html .= $this->UploadsView->buildUploadForm();
        $html .= $this->UploadsView->buildUploads('edit');
        $html .= $this->buildEditJs();

        return $html;
    }

    /**
     * Generate HTML for show XP
     * we have html and html2 because to build html we need the idArr
     * from html2
     *
     * @return string
     */
    public function buildShow()
    {
        $html = '';
        $html2 = '';

        // RELATED SEARCH (links)
        if ($this->related) {

            $itemsArr = $this->Experiments->readRelated($this->related);

        } else {

            // get all XP items for the user
            $itemsArr = $this->Experiments->readAll();

        }

        // loop the results array and display results
        $idArr = array();
        foreach ($itemsArr as $item) {

            // fill an array with the ID of each item to use in the csv/zip export menu
            $idArr[] = $item['id'];
            $html2 .= $this->showUnique($item);

        }

        // show number of results found
        $count = count($itemsArr);
        if ($count === 0 && $this->searchType != '') {
            return display_message('ko_nocross', _("Sorry. I couldn't find anything :("));
        } elseif ($count === 0 && $this->searchType === '') {
            return display_message('ok_nocross', sprintf(_("Welcome to eLabFTW. %sClick here%s to create your first experiment."), "<a href='app/controllers/ExperimentsController.php?create=true'>", "</a>"));
        } else {
            $html .= $this->buildExportMenu($idArr, 'experiments');

            $total_time = get_total_time();
            $html .= "<p class='smallgray'>" . $count . " " .
                ngettext("result found", "results found", $count) . " (" .
                $total_time['time'] . " " . $total_time['unit'] . ")</p>";
        }
        $load_more_button = "<div class='center'>
            <button class='button' id='loadButton'>" . sprintf(_('Show %s more'), $this->limit) . "</button>
            <button class='button' id='loadAllButton'>". _('Show all') . "</button>
            </div>";
        // show load more button if there are more results than the default display number
        if ($count > $this->limit) {
            $html2 .= $load_more_button;
        }
        $html .= $this->buildShowJs('experiments');
        return $html . $html2;
    }

    /**
     * Show an experiment
     *
     * @param int $item ID of the experiment to show
     * @return string
     */
    public function showUnique($item)
    {
        $html = "<section class='item " . $this->display . "' style='border-left: 6px solid #" . $item['color'] . "'>";
        $html .= "<a href='experiments.php?mode=view&id=" . $item['id'] . "'>";

        // show attached if there is a file attached
        // we need an id to look for attachment
        $this->Experiments->id = $item['id'];
        if ($this->Experiments->hasAttachment('experiments')) {
            $html .= "<img style='clear:both' class='align_right' src='img/attached.png' alt='file attached' />";
        }
        // we show the abstract of the experiment on mouse hover with the title attribute
        // we check if it is our experiment. It would be best to check if we have visibility rights on it
        // but atm there is no such function. So we limit this feature to experiments we own, for simplicity.
        if (is_owned_by_user($item['id'], 'experiments', $_SESSION['userid'])) {
            $bodyAbstract = str_replace("'", "", substr(strip_tags($item['body']), 0, 100));
        } else {
            $bodyAbstract = '';
        }
        $html .= "<a title='" . $bodyAbstract . "' href='experiments.php?mode=view&id=" . $item['id'] . "'>";
        $html .= "<p class='title'>";
        if ($item['timestamped']) {
            $html .= "<img class='align_right' src='img/stamp.png' alt='stamp' title='experiment timestamped' />";
        }
        // LOCK
        if ($item['locked']) {
            $html .= "<img style='padding-bottom:3px;' src='img/lock-blue.png' alt='lock' />";
        }
        // TITLE
        $html .= stripslashes($item['title']) . "</p></a>";
        // STATUS
        $html .= "<span style='text-transform:uppercase;font-size:80%;padding-left:20px;color:#" . $item['color'] . "'>" . $item['name'] . " </span>";
        // DATE
        $html .= "<span class='date'><img class='image' src='img/calendar.png' /> " . Tools::formatDate($item['date']) . "</span> ";
        // TAGS
        $html .= $this->showTags('experiments', 'view', $item['id']);

        $html .= "</section>";

        return $html;
    }

    /**
     * Generate HTML for edit experiment
     *
     * @return string $html
     */
    private function buildEdit()
    {
        // load tinymce
        $html = "<script src='js/tinymce/tinymce.min.js'></script>";
        $html .= "<menu class='border'><a href='experiments.php?mode=show'>";
        $html .= "<img src='img/arrow-left-blue.png' class='bot5px' alt='' /> " . _('Back to experiments listing') . "</a></menu>";

        $html .= "<section class='box' id='main_section' style='border-left: 6px solid #" . $this->experiment['color'] . "'>";
        $html .= "<img class='align_right' src='img/big-trash.png' title='delete' alt='delete' onClick=\"experimentsDestroy(" . $this->Experiments->id . ", '" . _('Delete this?') . "')\" />";

        // tags
        $html .= $this->showTags('experiments', 'edit', $this->Experiments->id);

        // main form
        $html .= "<form method='post' action='app/controllers/ExperimentsController.php' enctype='multipart/form-data'>";
        $html .= "<input name='update' type='hidden' value='true' />";
        $html .= "<input name='id' type='hidden' value='" . $this->Experiments->id . "' />";

        // DATE
        $html .= "<div class='row'><div class='col-md-4'>";
        $html .= "<img src='img/calendar.png' class='bot5px' title='date' alt='calendar' />";
        $html .= "<label for='datepicker'>" . _('Date') . "</label>";
        // if firefox has support for it: type = date
        // https://bugzilla.mozilla.org/show_bug.cgi?id=825294
        $html .= " <input name='date' id='datepicker' size='8' type='text' value='" . $this->experiment['date'] . "' />";
        $html .= "</div>";

        // VISIBILITY
        $html .= "<div class='col-md-4'>";
        $html .= "<img src='img/eye.png' class='bot5px' alt='visibility' />";
        $html .= "<label for='visibility_select'>" . _('Visibility') . "</label>";
        $html .= " <select id='visibility_select' onchange='updateVisibility(" . $this->Experiments->id . ", this.value)'>";
        $html .= "<option value='organization' ";
        if ($this->experiment['visibility'] === 'organization') {
            $html .= "selected";
        }
        $html .= ">" . _('Everyone with an account') . "</option>";
        $html .= "<option value='team' ";
        if ($this->experiment['visibility'] === 'team') {
            $html .= "selected";
        }
        $html .= ">" . _('Only the team') . "</option>";
        $html .= "<option value='user' ";
        if ($this->experiment['visibility'] === 'user') {
            $html .= "selected";
        }
        $html .= ">" . _('Only me') . "</option>";

        // Teamgroups
        $teamGroupsArr = $this->teamGroups->read($_SESSION['team_id']);
        foreach ($teamGroupsArr as $teamGroup) {
            $html .= "<option value='" . $teamGroup['id'] . "' ";
            if ($this->experiment['visibility'] === $teamGroup['id']) {
                $html .= "selected";
            }
            $html .= ">Only " . $teamGroup['name'] . "</option>";
        }
        $html .= "</select></div>";

        // STATUS
        $html .= "<div class='col-md-4'>";
        $html .= "<img src='img/status.png' class='bot5px' alt='status' />";
        $html .= "<label for='status_select'>" . ngettext('Status', 'Status', 1) . "</label>";
        $html .= " <select id='status_select' name='status' onchange='updateStatus(" . $this->Experiments->id . ", this.value)'>";

        $Status = new Status();
        $statusArr = $Status->read($_SESSION['team_id']);

        foreach ($statusArr as $status) {
            $html .= "<option ";
            if ($this->experiment['status'] === $status['id']) {
                $html .= "selected ";
            }
            $html .= "value='" . $status['id'] . "'>" . $status['name'] . "</option>";
        }
        $html .= "</select></div></div>";

        // TITLE
        $html .= "<h4>" . _('Title') . "</h4>";
        $html .= "<input id='title_input' name='title' rows='1' value='" . stripslashes($this->experiment['title']) . "' required />";

        // BODY
        $html .= "<h4>". ngettext('Experiment', 'Experiments', 1) . "</h4>";
        $html .= "<textarea id='body_area' class='mceditable' name='body' rows='15' cols='80'>";
        $html .= stripslashes($this->experiment['body']) . "</textarea>";

        $html .= "<div id='saveButton'>
            <button type='submit' name='Submit' class='button'>" ._('Save and go back') . "</button>
            </div></form>";

        // REVISIONS
        $Revisions = new Revisions('experiments', $this->experiment['id']);
        $html .= $Revisions->showCount();

        // LINKS
        $html .= "<section>
                <img src='img/link.png' class='bot5px' class='bot5px'> <h4 style='display:inline'>" . _('Linked items') . "</h4><br>";
        $html .= "<span id='links_div'>";
        $html .= $this->showLinks($this->Experiments->id, 'edit');
        $html .= "</span>";
        $html .= "<p class='inline'>" . _('Add a link') . "</p> ";
        $html .= "<input id='linkinput' size='60' type='text' name='link' placeholder='" . _('from the database') . "' />";
        $html .= "</section>";

        // end main section
        $html .= "</section>";

        $html .= $this->injectChemEditor();

        return $html;
    }

    /**
     * Check we own the experiment
     *
     * @return bool
     */
    private function isOwner()
    {
        return $this->experiment['userid'] == $_SESSION['userid'];
    }


    /**
     * If int, get the name of the team group instead of a number
     *
     * @return string
     */
    private function getVisibility()
    {
        if (Tools::checkId($this->experiment['visibility'])) {
            return $this->teamGroups->readName($this->experiment['visibility']);
        }
        return $this->experiment['visibility'];
    }

    /**
     * Check if we have writing rights
     *
     * @return bool
     */
    private function isReadOnly()
    {
        // Check id is owned by connected user to show read only message if not
        if ($this->experiment['userid'] != $_SESSION['userid']) {
            // Can the user see this experiment which is not his ?
            if ($this->experiment['visibility'] === 'user') {

                throw new Exception(_("<strong>Access forbidden:</strong> the visibility setting of this experiment is set to 'owner only'."));

            } elseif (Tools::checkId($this->experiment['visibility'])) {
                // the visibility of this experiment is set to a group
                // we must check if current user is in this group
                if (!$this->teamGroups->isInTeamGroup($_SESSION['userid'], $this->experiment['visibility'])) {
                    throw new Exception(_("<strong>Access forbidden:</strong> you don't have the rights to access this."));
                }

            } else {
                $users = new Users();
                $owner = $users->read($this->experiment['userid']);

                if ($owner['team'] != $_SESSION['team_id']) {
                    // the experiment needs to be organization for us to see it as we are not in the team of the owner
                    if ($this->experiment['visibility'] != 'organization') {
                        throw new Exception(_("<strong>Access forbidden:</strong> you don't have the rights to access this."));
                    }
                }
                // read only
                return true;
            }
        }
        return false;
    }

    /**
     * Show info on timestamp
     *
     * @return string
     */
    private function showTimestamp()
    {
        $Users = new Users();
        $timestamper = $Users->read($this->experiment['timestampedby']);

        // we clone the object so we don't mess with the type
        $ClonedUploads = clone $this->UploadsView->Uploads;

        $ClonedUploads->type = 'exp-pdf-timestamp';
        $pdf = $ClonedUploads->readAll();

        $ClonedUploads->type = 'timestamp-token';
        $token = $ClonedUploads->readAll();

        $date = new DateTime($this->experiment['timestampedwhen']);

        return display_message(
            'ok_nocross',
            _('Experiment was timestamped by') . " " . $timestamper['firstname'] . " " . $timestamper['lastname'] . " " . _('on') . " " . $date->format('Y-m-d') . " " . _('at') . " " . $date->format('H:i:s') . " "
            . $date->getTimezone()->getName() . " <a href='uploads/" . $pdf[0]['long_name'] . "'><img src='img/pdf.png' class='bot5px' title='" . _('Download timestamped pdf') . "' alt='pdf' /></a> <a href='uploads/" . $token[0]['long_name'] . "'><img src='img/download.png' title=\"" . _('Download token') . "\" alt='token' class='bot5px' /></a>"
        );
    }

    /**
     * Output HTML for viewing an experiment
     *
     */
    private function buildView()
    {
        $html = '';

        if ($this->ro) {
            $message = _('Read-only mode.');
            $html .= display_message('ok', $message);
        }

        $html .= "<section class='item' style='padding:15px;border-left: 6px solid #" . $this->experiment['color'] . "'>";
        $html .= "<span class='top_right_status'><img src='img/status.png'>" . $this->experiment['name'] .
            "<img src='img/eye.png' alt='eye' />" . $this->getVisibility() . "</span>";
        $html .=  "<div><img src='img/calendar.png' class='bot5px' title='date' alt='Date :' /> " .
            Tools::formatDate($this->experiment['date']) . "</div>
        <a href='experiments.php?mode=edit&id=" . $this->experiment['id'] . "'><img src='img/pen-blue.png' title='edit' alt='edit' /></a>
    <a href='app/controllers/ExperimentsController.php?duplicateId=" . $this->experiment['id'] . "'><img src='img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a>
    <a href='make.php?what=pdf&id=" . $this->experiment['id'] . "&type=experiments'><img src='img/pdf.png' title='make a pdf' alt='pdf' /></a>
    <a href='make.php?what=zip&id=" . $this->experiment['id'] . "&type=experiments'><img src='img/zip.png' title='make a zip archive' alt='zip' /></a> ";

        if ($this->experiment['locked'] == 0) {
            $html .= "<a href='app/lock.php?id=" . $this->experiment['id'] . "&type=experiments'><img src='img/unlock.png' title='lock experiment' alt='lock' /></a>";
        } else { // experiment is locked
            $html .= "<a href='app/lock.php?id=" . $this->experiment['id'] . "&type=experiments'><img src='img/lock-gray.png' title='unlock experiment' alt='unlock' /></a>";
            // show timestamp button if it's not timestamped already
            if (!$this->experiment['timestamped']) {
                $html .= "<a onClick=\"return confirm('" . _('Once timestamped an experiment cannot be edited anymore ! Are you sure you want to do this ?') . "')\" href='app/timestamp.php?id=" . $this->experiment['id'] . "'><img src='img/stamp.png' title='timestamp experiment' alt='timestamp' /></a>";
            }
        }

        $html .= $this->showTags('experiments', 'view', $this->Experiments->id);
        // TITLE : click on it to go to edit mode only if we are not in read only mode
        $html .=  "<div ";
        if (!$this->ro && !$this->experiment['locked']) {
            $html .=  "OnClick=\"document.location='experiments.php?mode=edit&id=" . $this->experiment['id'] . "'\"";
        }
        $html .=  " class='title_view'>";
        $html .=  stripslashes($this->experiment['title']) . "</div>";
        // BODY (show only if not empty, click on it to edit
        if ($this->experiment['body'] != '') {
            $html .= "<div id='body_view' ";
            // make the body clickable only if we are not in read only
            if (!$this->ro && !$this->experiment['locked']) {
                $html .= "OnClick=\"document.location='experiments.php?mode=edit&id=" . $this->experiment['id'] . "'\"";
            }
            $html .= " class='txt'>" . stripslashes($this->experiment['body']) . "</div>";
            $html .= "<br>";
        }

        $html .= $this->showLinks($this->Experiments->id, 'view');

        // DISPLAY eLabID
        $html .= "<p class='elabid'>" . _('Unique eLabID:') . " " . $this->experiment['elabid'];
        $html .= "</section>";

        return $html;
    }

    /**
     * Build the JS code for edit mode
     *
     * @return string
     */
    private function buildEditJs()
    {
        $tags = new Tags('experiments', $this->Experiments->id);

        $html = "<script>
            function delete_tag(tag_id, item_id) {
                var you_sure = confirm('" . _('Delete this?') . "');
                if (you_sure == true) {
                    $.post('app/delete.php', {
                        id: tag_id,
                        item_id: item_id,
                        type: 'exptag'
                    }).done(function () {
                        $('#tags_div').load('experiments.php?mode=edit&id=' + item_id + ' #tags_div');
                    })
                }
                return false;
            }

    // READY ? GO !!
    $(document).ready(function() {
        // KEYBOARD SHORTCUTS
        key('" . $_SESSION['prefs']['shortcuts']['create'] . "', function(){location.href = 'app/controllers/ExperimentsController?create=true'});
        key('" . $_SESSION['prefs']['shortcuts']['submit'] . "', function(){document.forms['editXP'].submit()});

        // autocomplete the tags
        $('#createTagInput').autocomplete({
            source: [" . $tags->generateTagList() . "]
        });

        // autocomplete the links
        $( '#linkinput' ).autocomplete({
            source: [" . getDbList('default') . "]
        });

        // CREATE TAG
        // listen keypress, add tag when it's enter
        $('#createTagInput').keypress(function (e) {
            createTag(e, " . $this->Experiments->id . ", 'experiments');
        });
        // CREATE LINK
        // listen keypress, add link when it's enter
        $('#linkinput').keypress(function (e) {
            experimentsCreateLink(e, " . $this->Experiments->id . ");
        });

        // DATEPICKER
        $( '#datepicker' ).datepicker({dateFormat: 'yymmdd'});
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
                    id : " . $this->Experiments->id . ",
                    type : 'experiments',
                    // we need this to get the updated content
                    title : document.getElementById('title_input').value,
                    date : document.getElementById('datepicker').value,
                    body : tinymce.activeEditor.getContent()
                }).done(function(data) {
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
            mentions: {
                source: [" . getDbList('mention') . "],
                delimiter: '#'
            },
            language : '" . $_SESSION['prefs']['lang'] . "',
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
        });";

        $html .= $this->injectCloseWarning();
        $html .= "});</script>";

        return $html;
    }

    /**
     * Output html for displaying links
     *
     * @param int $id Experiment id
     * @param string $mode edit or view
     * @return string $html
     */
    public function showLinks($id, $mode)
    {
        $linksArr = $this->Experiments->Links->read();
        $html = '';

        // Check there is at least one link to display
        if (count($linksArr) > 0) {
            $html .= "<ul class='list-group'>";
            foreach ($linksArr as $link) {
                if ($mode === 'edit') {
                    $html .= "<li class='list-group-item'>" . $link['name'] . " - <a href='database.php?mode=view&id=" . $link['itemid'] . "'>" .
                        stripslashes($link['title']) . "</a>";
                    $html .= "<a onClick=\"experimentsDestroyLink(" . $link['linkid'] . ", " . $id . ", '" . _('Delete this?') . "')\">
                    <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' /></a></li>";
                } else {
                    $html .= "<li class='list-group-item'><img src='img/link.png'> " . $link['name'] . " - <a href='database.php?mode=view&id=" . $link['itemid'] . "'>" .
                    stripslashes($link['title']) . "</a></li>";
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }

    /**
     * Build JS for view mode
     *
     * @return string
     */
    private function buildViewJs()
    {
        $html = "<script>
            function commentsUpdate() {
                // Experiment comment is editable
                $('div#expcomment').on('mouseover', '.editable', function(){
                    $('div#expcomment p.editable').editable('app/controllers/CommentsController.php', {
                        name: 'commentsUpdateComment',
                        tooltip : 'Click to edit',
                        indicator : '" ._('Saving') . "',
                        commentsUpdate: true,
                        submit : '" . _('Save') . "',
                        cancel : '" . _('Cancel') . "',
                        style : 'display:inline',
                        callback : function() {
                            // now we reload the comments part to show the comment we just submitted
                            $('#expcomment_container').load('experiments.php?mode=view&id=" .
                            $this->Experiments->id . " #expcomment');
                            // we reload the function so editable zones are editable again
                            commentsUpdate();
                        }
                    })
                });
            }

            // READY ? GO !!
            $(document).ready(function() {
                $('#commentsCreateButtonDiv').hide();

                // Keyboard shortcuts
                key('" . $_SESSION['prefs']['shortcuts']['create'] .
                    "', function(){location.href = 'app/controllers/ExperimentsController?create=true'});
                key('" . $_SESSION['prefs']['shortcuts']['edit'] .
                    "', function(){location.href = 'experiments.php?mode=edit&id=" . $this->Experiments->id . "'});
                // make editable
                setInterval(commentsUpdate, 50);
            });
            </script>";
        return $html;
    }

    /**
     * Display comments for an experiment
     *
     */
    private function buildComments()
    {
        $Comments = new Comments($this->Experiments);
        $commentsArr = $Comments->read();

        //  we need to add a container here so the reload function in the callback of .editable() doesn't mess things up
        $html = "<section id='expcomment_container'>";
        $html .= "<div id='expcomment' class='box'>";
        $html .= "<h3><img src='img/comment.png' alt='comment' />" . _('Comments') . "</h3>";

        if (is_array($commentsArr)) {
            // there is comments to display
            foreach ($commentsArr as $comment) {
                if (empty($comment['firstname'])) {
                    $comment['firstname'] = '[deleted]';
                }
                $html .= "<div class='expcomment_box'>
                    <img class='align_right' src='img/small-trash.png' ";
                $html .= "title='delete' alt='delete' onClick=\"commentsDestroy(".
                    $comment['id'] . ", " . $this->Experiments->id . ", '" .  _('Delete this?') . "')\" />";
                $html .= "<span class='smallgray'>On " . $comment['datetime'] . " " . $comment['firstname'] . " " .
                    $comment['lastname'] . " wrote :</span><br />";
                $html .= "<p class='editable' id='" . $comment['id'] . "'>" . $comment['comment'] . "</p></div>";
            }
        }
        return $html;
    }

    /**
     * HTML for the add new comment block
     */
    private function buildCommentsCreate()
    {
        $html = "<textarea onFocus='commentsCreateButtonDivShow()' id='commentsCreateArea' placeholder='" .
            _('Add a comment') . "'></textarea>";
        $html .= "<div id='commentsCreateButtonDiv' class='submitButtonDiv'>";
        $html .= "<button class='button' id='commentsCreateButton' onClick='commentsCreate(" .
            $this->Experiments->id . ")'>" . _('Save') . "</button></div></div></section>";

        return $html;
    }
}
