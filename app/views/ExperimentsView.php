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

use \PDO;
use \Exception;
use \Datetime;

/**
 * Experiments View
 */
class ExperimentsView
{
    /** object holding class Experiments */
    private $experiments;

    /** the experiment array with data */
    private $experiment;

    /** can be int or string. Int is team groups */
    private $visibility;

    /** Read only switch */
    private $ro = false;

    /** revisions class */
    private $revisions;

    /** object holding class Comments */
    private $comments;

    /** ID of the experiment we want to view */
    private $id;

    /** TODO remove */
    private $pdo;

    /** the Uploads class */
    private $uploads;

    /**
     * Need an ID of an experiment
     *
     * @param int $id Experiment id from GET[]
     * @throws Exception
     */
    public function __construct($id)
    {
        $this->pdo = Db::getConnection();
        $this->id = Tools::checkId($id);
        if ($this->id === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }
        $this->experiments = new Experiments();
        $this->status = new Status();
        $this->revisions = new Revisions();
        $this->comments = new Comments();
        $this->uploads = new Uploads();

        // get data of experiment
        $this->experiment = $this->experiments->read($this->id);
        // visibility can be a string, or number if teamgroup
        $this->visibility = $this->setVisibility();
    }

    /**
     * View experiment
     *
     * @return string HTML for viewXP
     */
    public function view()
    {
        $html = '';

        $this->ro = $this->isReadOnly();

        if ($this->isTimestamped()) {
            $html .= $this->showTimestamp();
        }

        $html .= $this->buildView();
        $html .= $this->buildUploads('view');
        $html .= $this->buildComments();
        $html .= $this->buildCommentsCreate();
        $html .= $this->buildJs();

        return $html;
    }
    /**
     * Generate HTML for edit mode
     *
     * @return string
     */
    public function edit()
    {
        // only owner can edit an experiment
        if (!$this->isOwner()) {
            throw new Exception(_('<strong>Cannot edit:</strong> this experiment is not yours!'));
        }

        // a locked experiment cannot be edited
        if ($this->experiment['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }
        $html = $this->buildEdit();
        $html .= $this->buildUploads('edit');

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
        $html .= "<img class='align_right' src='img/big-trash.png' title='delete' alt='delete' onClick=\"deleteThis($this->id,'exp', 'experiments.php')\" />";

        $html .=  displayTags('experiments', $this->id);
        $html .= "<form method='post' action='app/editXP-exec.php' enctype='multipart/form-data'>";
        $html .= "<input name='item_id' type='hidden' value='" . $this->id . "' />";

        $html .= "<div class='row'><div class='col-md-4'>";
        $html .= "<img src='img/calendar.png' class='bot5px' title='date' alt='calendar' />";
        $html .= "<label for='datepicker'>" . _('Date') . "</label>";
        // TODO if firefox has support for it: type = date
        // https://bugzilla.mozilla.org/show_bug.cgi?id=825294
        $html .= "<input name='date' id='datepicker' size='8' type='text' value='" . $this->experiment['date'] . "' />";
        $html .= "</div>";

        // VISIBILITY
        $html .= "<div class='col-md-4'>";
        $html .= "<img src='img/eye.png' class='bot5px' alt='visibility' />";
        $html .= "<label for='visibility_select'>" . _('Visibility') . "</label>";
        $html .= "<select id='visibility_select' name='visibility' onchange='experimentsUpdateVisibility(" . $this->id . ", this.value)'>";
        $html .= "<option value='organization' ";
        if ($this->visibility === 'organization') {
            $html .= "selected";
        }
        $html .= ">" . _('Everyone with an account') . "</option>";
        $html .= "<option value='team' ";
        if ($this->visibility === 'team') {
            $html .= "selected";
        }
        $html .= ">" . _('Only the team') . "</option>";
        $html .= "<option value='user' ";
        if ($this->visibility === 'user') {
            $html .= "selected";
        }
        $html .= ">" . _('Only me') . "</option>";

        // Teamgroups
        $teamGroups = new TeamGroups();
        $teamGroupsArr = $teamGroups->read($_SESSION['team_id']);
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
        $html .= "<select id='status_select' name='status' onchange='experimentsUpdateStatus(" . $this->id . ", this.value)'>";

        $statusArr = $this->status->read($_SESSION['team_id']);

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
        $count = $this->revisions->readCount($this->id);
        if ($count > 0) {
            $html .= "<span class='align_right'>";
            $html .= $count . " " . ngettext('revision available.', 'revisions available.', $count);
            $html .= " <a href='revision.php?type=experiments&item_id=" . $this->id . "'>" . _('Show history') . "</a>";
            $html .= "</span>";
        }

        // LINKS
        $html .= "<section>
                <img src='img/link.png' class='bot5px' class='bot5px'> <h4 style='display:inline'>" . _('Linked items') . "</h4><br>";
        $html .= "<span id='links_div'>";
        $html .= $this->showLinks($this->id, 'edit');
        $html .= "</span>";
        $html .= "<p class='inline'>" . _('Add a link') . "</p>";
        $html .= "<input id='linkinput' size='60' type='text' name='link' placeholder='" . _('from the database') . "' />";
        $html .= "</section>";

        // end main section
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
    private function setVisibility()
    {
        if (is_pos_int($this->experiment['visibility'])) {
            $sql = "SELECT name FROM team_groups WHERE id = :id";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':id', $this->experiment['visibility'], PDO::PARAM_INT);
            $req->execute();
            return $req->fetchColumn();
        }
        return $this->experiment['visibility'];
    }

    /**
     * Check if user is in a team group
     *
     * @param int $userid
     * @param int $groupid
     * @return bool
     */
    private function isInTeamGroup($userid, $groupid)
    {
        $sql = "SELECT DISTINCT userid FROM users2team_groups WHERE groupid = :groupid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':groupid', $groupid);
        $req->execute();
        $authUsersArr = array();
        while ($authUsers = $req->fetch()) {
            $authUsersArr[] = $authUsers['userid'];
        }

        return in_array($userid, $authUsersArr);
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
            if ($this->visibility === 'user') {

                throw new Exception(_("<strong>Access forbidden:</strong> the visibility setting of this experiment is set to 'owner only'."));

            } elseif (is_pos_int($this->experiment['visibility'])) {
                // the visibility of this experiment is set to a group
                // we must check if current user is in this group
                if (!$this->isInTeamGroup($_SESSION['userid'], $this->experiment['visibility'])) {
                    throw new Exception(_("<strong>Access forbidden:</strong> you don't have the rights to access this."));
                }

            } else {
                $users = new Users();
                $owner = $users->read($this->experiment['userid']);

                if ($owner['team'] != $_SESSION['team_id']) {
                    // the experiment needs to be organization for us to see it as we are not in the team of the owner
                    if ($this->visibility != 'organization') {
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
     * Check if experiment is timestamped
     *
     * @return bool
     */
    private function isTimestamped()
    {
        return $this->experiment['timestamped'];
    }

    /**
     * Show info on timestamp
     * TODO put in a model and remove pdo from this class
     */
    private function showTimestamp()
    {
        // who what when ?
        $sql = 'SELECT firstname, lastname FROM users WHERE userid = :userid';
        $req_stamper = $this->pdo->prepare($sql);
        $req_stamper->bindParam(':userid', $this->experiment['timestampedby']);
        $req_stamper->execute();
        $timestamper = $req_stamper->fetch();

        // display timestamped pdf download link
        $sql = "SELECT * FROM uploads WHERE type = 'exp-pdf-timestamp' AND item_id = :item_id LIMIT 1";
        $req_stamper = $this->pdo->prepare($sql);
        $req_stamper->bindParam(':item_id', $this->id);
        $req_stamper->execute();
        $uploads = $req_stamper->fetch();

        // display a link to download the .asn1 token also
        $sql = "SELECT * FROM uploads WHERE type = 'timestamp-token' AND item_id = :item_id LIMIT 1";
        $req_stamper = $this->pdo->prepare($sql);
        $req_stamper->bindParam(':item_id', $this->id);
        $req_stamper->execute();
        $token = $req_stamper->fetch();

        $date = new DateTime($this->experiment['timestampedwhen']);

        // there is a \" in title attribute of img to prevent ' (apostrophe) cutting the string for french translation
        return display_message(
            'ok_nocross',
            _('Experiment was timestamped by') . " " . $timestamper['firstname'] . " " . $timestamper['lastname'] . " " . _('on') . " " . $date->format('Y-m-d') . " " . _('at') . " " . $date->format('H:i:s') . " "
            . $date->getTimezone()->getName() . " <a href='uploads/" . $uploads['long_name'] . "'><img src='img/pdf.png' class='bot5px' title='" . _('Download timestamped pdf') . "' alt='pdf' /></a> <a href='uploads/" . $token['long_name'] . "'><img src='img/download.png' title=\"" . _('Download token') . "\" alt='token' class='bot5px' /></a>"
        );
    }

    /**
     * Output HTML for viewing an experiment
     *
     */
    private function buildView()
    {

        $html = "<section class='item' style='padding:15px;border-left: 6px solid #" . $this->experiment['color'] . "'>";
        $html .= "<span class='top_right_status'><img src='img/status.png'>" . $this->experiment['name'] .
            "<img src='img/eye.png' alt='eye' />" . $this->visibility . "</span>";
        $html .=  "<span class='date_view'><img src='img/calendar.png' class='bot5px' title='date' alt='Date :' /> " .
            Tools::formatDate($this->experiment['date']) . "</span><br />
        <a href='experiments.php?mode=edit&id=" . $this->experiment['id'] . "'><img src='img/pen-blue.png' title='edit' alt='edit' /></a>
    <a href='app/duplicate_item.php?id=" . $this->experiment['id'] . "&type=exp'><img src='img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a>
    <a href='make.php?what=pdf&id=" . $this->experiment['id'] . "&type=experiments'><img src='img/pdf.png' title='make a pdf' alt='pdf' /></a>
    <a href='make.php?what=zip&id=" . $this->experiment['id'] . "&type=experiments'><img src='img/zip.png' title='make a zip archive' alt='zip' /></a> ";

        if ($this->experiment['locked'] == 0) {
            $html .= "<a href='app/lock.php?id=" . $this->experiment['id'] . "&action=lock&type=experiments'><img src='img/unlock.png' title='lock experiment' alt='lock' /></a>";
        } else { // experiment is locked
            $html .= "<a href='app/lock.php?id=" . $this->experiment['id'] . "&action=unlock&type=experiments'><img src='img/lock-gray.png' title='unlock experiment' alt='unlock' /></a>";
            // show timestamp button if it's not timestamped already
            if (!$this->isTimestamped()) {
                $html .= "<a onClick=\"return confirmStamp('" . _('Once timestamped an experiment cannot be edited anymore ! Are you sure you want to do this ?') . "')\" href='app/timestamp.php?id=" . $this->experiment['id'] . "'><img src='img/stamp.png' title='timestamp experiment' alt='timestamp' /></a>";
            }
        }

        // TAGS TODO
        $html .= show_tags($this->id, 'experiments_tags');
        // TITLE : click on it to go to edit mode only if we are not in read only mode
        $html .=  "<div ";
        if ($this->ro === false && $this->experiment['locked'] == 0) {
            $html .=  "OnClick=\"document.location='experiments.php?mode=edit&id=" . $this->experiment['id'] . "'\"";
        }
        $html .=  " class='title_view'>";
        $html .=  stripslashes($this->experiment['title']) . "</div>";
        // BODY (show only if not empty, click on it to edit
        if ($this->experiment['body'] != '') {
            $html .= "<div id='body_view' ";
            // make the body clickable only if we are not in read only
            if ($this->ro === false && $this->experiment['locked'] == 0) {
                $html .= "OnClick=\"document.location='experiments.php?mode=edit&id=" . $this->experiment['id'] . "'\"";
            }
            $html .= " class='txt'>" . stripslashes($this->experiment['body']) . "</div>";
            $html .= "<br>";
        }

        $html .= $this->showLinks($this->id, 'view');

        // DISPLAY eLabID
        $html .= "<p class='elabid'>" . _('Unique eLabID:') . " " . $this->experiment['elabid'];
        $html .= "</section>";

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
        $linksArr = $this->experiments->readLink($id);
        $html = '';

        // Check there is at least one link to display
        if (count($linksArr) > 0) {
            $html .= "<ul class='list-group'>";
            foreach ($linksArr as $link) {
                if ($mode === 'edit') {
                    $html .= "<li>- [" . $link['name'] . "] - <a href='database.php?mode=view&id=" . $link['itemid'] . "'>" .
                        stripslashes($link['title']) . "</a>";
                    $html .= "<a onClick=\"experimentsDestroyLink(" . $link['linkid'] . ", " . $id . ", '" . _('Delete this?') . "')\">
                    <img src='img/small-trash.png' title='delete' alt='delete' /></a></li>";
                } else {
                    $html .= "<li><img src='img/link.png'> [" . $link['name'] . "] - <a href='database.php?mode=view&id=" . $link['itemid'] . "'>" .
                    stripslashes($link['title']) . "</a></li>";
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }

    /**
     * Generate HTML for displaying uploaded files
     *
     * @param string $mode edit or view
     * @return string html
     */
    private function buildUploads($mode)
    {
        $uploadsArr = $this->uploads->read($this->id, 'experiments');

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
        $html .= "<div id='filesdiv'>";
        $html .= "<div class='box'>";
        $html .= "<img src='img/attached.png' class='bot5px'> <h3 style='display:inline'>" .
            ngettext('Attached file', 'Attached files', $count) . "</h3>";
        $html .= "<div class='row'>";
        foreach ($uploadsArr as $upload) {
            $html .= "<div class='col-md-4 col-sm-6'>";
            $html .= "<div class='thumbnail'>";
            // show the delete button only in edit mode, not in view mode
            if ($mode === 'edit') {
                $html .= "<a class='align_right' href='app/delete_file.php?id=" . $upload['id'] . "&type=" .
                    $upload['type'] . "&item_id=" . $upload['item_id'] .
                    "' onClick=\"return confirm('Delete this file ?');\">";
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
                make_thumb($filepath, $ext, $thumbpath, 100);
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
            });
            $(document).ready(function() {
                // we use fancybox to display thumbnails
                $('a.fancybox').fancybox();
            });
            </script>";
        }
        return $html;
    }

    private function buildJs()
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
                            $this->id . " #expcomment');
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
                    "', function(){location.href = 'app/create_item.php?type=exp'});
                key('" . $_SESSION['prefs']['shortcuts']['edit'] .
                    "', function(){location.href = 'experiments.php?mode=edit&id=" . $this->id . "'});
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
        $commentsArr = $this->comments->read($this->id);

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
                    $comment['id'] . ", " . $this->id . ", '" .  _('Delete this?') . "')\" />";
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
            $this->id . ")'>" . _('Save') . "</button></div></div></section>";

        return $html;
    }
}
