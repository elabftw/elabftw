<?php
/**
 * \Elabftw\Elabftw\ExperimentsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
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
    /** Read only switch */
    private $ro = false;

    /** instance of TeamGroups */
    public $TeamGroups;

    /**
     * Need an instance of Experiments
     *
     * @param Entity $entity
     * @throws Exception
     */
    public function __construct(Entity $entity)
    {
        $this->Entity = $entity;
        $this->limit = $_SESSION['prefs']['limit'];
        $this->showTeam = $_SESSION['prefs']['show_team'];

        $this->TeamGroups = new TeamGroups($this->Entity->Users->userData['team']);
    }

    /**
     * View experiment
     *
     * @return string HTML for viewXP
     */
    public function view()
    {
        $this->initViewEdit();
        $this->ro = $this->isReadOnly();

        if ($this->Entity->entityData['timestamped']) {
            $this->html .= $this->showTimestamp();
        }

        $this->html .= $this->buildView();
        $this->html .= $this->UploadsView->buildUploads('view');
        $this->html .= $this->buildComments();
        $this->html .= $this->buildCommentsCreate();
        $this->html .= $this->buildViewJs();

        return $this->html;
    }
    /**
     * Edit experiment
     *
     * @return string
     */
    public function edit()
    {
        $this->html .= $this->UploadsView->buildUploadForm();
        $this->html .= $this->DoodleView->buildDoodle();
        $this->html .= $this->UploadsView->buildUploads('edit');

        return $this->html;
    }

    /**
     * If int, get the name of the team group instead of a number
     *
     * @return string
     */
    private function getVisibility()
    {
        if (Tools::checkId($this->Entity->entityData['visibility'])) {
            return $this->TeamGroups->readName($this->Entity->entityData['visibility']);
        }
        return ucfirst($this->Entity->entityData['visibility']);
    }

    /**
     * Show info on timestamp
     *
     * @return string
     */
    private function showTimestamp()
    {
        $Users = new Users();
        $timestamper = $Users->read($this->Entity->entityData['timestampedby']);

        $this->UploadsView->Uploads->Entity->type = 'exp-pdf-timestamp';
        $pdf = $this->UploadsView->Uploads->readAll();

        $this->UploadsView->Uploads->Entity->type = 'timestamp-token';
        $token = $this->UploadsView->Uploads->readAll();

        // set correct type back
        $this->UploadsView->Uploads->Entity->type = 'experiments';

        $date = new DateTime($this->Entity->entityData['timestampedwhen']);

        return Tools::displayMessage(
            _('Experiment was timestamped by') . " " . $timestamper['fullname'] . " " . _('on') .
            " " . $date->format('Y-m-d') . " " . _('at') . " " .
            $date->format('H:i:s') . " " .
            $date->getTimezone()->getName() . " <a href='uploads/" .
            $pdf[0]['long_name'] . "'><img src='app/img/pdf.png' title='" .
            _('Download timestamped pdf') . "' alt='pdf' /></a> <a href='uploads/" . $token[0]['long_name'] .
            "'><img src='app/img/download.png' title=\"" . _('Download token') .
            "\" alt='download token' /></a> <a href='#'><img onClick=\"decodeAsn1('" . $token[0]['long_name'] .
            "', '" . $this->Entity->entityData['id'] . "')\" src='app/img/info.png' title=\"" . _('Decode token') .
            "\" alt='decode token' /></a><div style='color:black;overflow:auto;display:hidden' id='decodedDiv'></div>",
            'ok',
            false
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
            $ownerName = $this->Entity->Users->userData['fullname'];
            $message = sprintf(_('Read-only mode. Experiment of %s.'), $ownerName);
            $html .= Tools::displayMessage($message, 'ok', false);
        }

        $html .= $this->backToLink('experiments');

        $html .= "<section class='item' style='padding:15px;border-left: 6px solid #" . $this->Entity->entityData['color'] . "'>";
        $html .= "<span class='top_right_status'><img src='app/img/status.png'>" . $this->Entity->entityData['category'] .
            "<img src='app/img/eye.png' alt='eye' />" . $this->getVisibility() . "</span>";
        $html .= "<div><img src='app/img/calendar.png' title='date' alt='Date :' /> " .
            Tools::formatDate($this->Entity->entityData['date']) . "</div>
        <a class='elab-tooltip' href='experiments.php?mode=edit&id=" . $this->Entity->entityData['id'] . "'><span>Edit</span><img src='app/img/pen-blue.png' alt='Edit' /></a>
    <a class='elab-tooltip' href='app/controllers/ExperimentsController.php?duplicateId=" . $this->Entity->entityData['id'] . "'><span>Duplicate Experiment</span><img src='app/img/duplicate.png' alt='Duplicate' /></a>
    <a class='elab-tooltip' href='make.php?what=pdf&id=" . $this->Entity->entityData['id'] . "&type=experiments'><span>Make a PDF</span><img src='app/img/pdf.png' alt='PDF' /></a>
    <a class='elab-tooltip' href='make.php?what=zip&id=" . $this->Entity->entityData['id'] . "&type=experiments'><span>Make a ZIP</span><img src='app/img/zip.png' alt='ZIP' /></a> ";

        // lock
        $onClick = " onClick=\"toggleLock('experiments', " . $this->Entity->entityData['id'] . ")\"";
        $imgSrc = 'unlock.png';
        $alt = _('Lock/Unlock item');
        if ($this->Entity->entityData['locked'] != 0) {
            $imgSrc = 'lock-gray.png';
            // don't allow clicking lock if experiment is timestamped
            if ($this->Entity->entityData['timestamped']) {
                $onClick = '';
            }
        }
        $html .= "<a class='elab-tooltip' href='#'><span>" . $alt . "</span><img id='lock'" . $onClick . " src='app/img/" . $imgSrc . "' alt='" . $alt . "' /></a> ";
        // show timestamp button if not timestamped already
        if (!$this->Entity->entityData['timestamped']) {
            $html .= "<a class='elab-tooltip'><span>Timestamp Experiment</span><img onClick='confirmTimestamp()' src='app/img/stamp.png' alt='Timestamp' /></a>";
            $html .= "<div id='confirm-timestamp' title='" . _('Timestamp this experiment?') . "'>";
            $html .= "<p><span class='ui-icon ui-icon-alert' style='float:left; margin:12px 12px 20px 0;'></span>";
            $html .= _('Once timestamped an experiment cannot be edited anymore! Are you sure you want to do this?');
            $html .= "</p></div>";
        }

        // build the tag array
        if (strlen($this->Entity->entityData['tags'] > '1')) {
            $tagsValueArr = explode('|', $this->Entity->entityData['tags']);
            $tagsKeyArr = explode(',', $this->Entity->entityData['tags_id']);
            $tagsArr = array_combine($tagsKeyArr, $tagsValueArr);
            $html .= "<span class='tags'><img src='app/img/tags.png' alt='tags' /> ";
            foreach ($tagsArr as $tag) {
                $html .= "<a href='experiments.php?mode=show&tag=" .
                    urlencode(stripslashes($tag)) . "'>" . stripslashes($tag) . "</a> ";
            }
        }

        // TITLE : click on it to go to edit mode only if we are not in read only mode
        $html .= "<div ";
        if (!$this->ro && !$this->Entity->entityData['locked']) {
            $html .= "OnClick=\"document.location='experiments.php?mode=edit&id=" . $this->Entity->entityData['id'] . "'\"";
        }
        $html .= " class='title_view'>";
        $html .= $this->Entity->entityData['title'] . "</div>";
        // BODY (show only if not empty, click on it to edit
        if ($this->Entity->entityData['body'] != '') {
            $html .= "<div id='body_view' ";
            // make the body clickable only if we are not in read only
            if (!$this->ro && !$this->Entity->entityData['locked']) {
                $html .= "OnClick=\"document.location='experiments.php?mode=edit&id=" . $this->Entity->entityData['id'] . "'\"";
            }
            $html .= " class='txt'>" . $this->Entity->entityData['body'] . "</div>";
            $html .= "<br>";
        }

        $html .= $this->showLinks($this->Entity->id, 'view');

        // DISPLAY eLabID
        $html .= "<p class='elabid'>" . _('Unique eLabID:') . " " . $this->Entity->entityData['elabid'];
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
        $linksArr = $this->Entity->Links->read();
        $html = '';

        // Check there is at least one link to display
        if (count($linksArr) > 0) {
            $html .= "<ul class='list-group'>";
            foreach ($linksArr as $link) {
                if ($mode === 'edit') {
                    $html .= "<li class='list-group-item'>" . $link['name'] . " - <a href='database.php?mode=view&id=" . $link['itemid'] . "'>" .
                        $link['title'] . "</a>";
                    $html .= "<a onClick=\"experimentsDestroyLink(" . $link['linkid'] . ", " . $id . ", '" . _('Delete this?') . "')\">
                    <img class='align_right' src='app/img/small-trash.png' title='delete' alt='delete' /></a></li>";
                } else {
                    $html .= "<li class='list-group-item'><img src='app/img/link.png'> " . $link['name'] . " - <a href='database.php?mode=view&id=" . $link['itemid'] . "'>" .
                    $link['title'] . "</a></li>";
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
                        name: 'commentsUpdate',
                        tooltip : 'Click to edit',
                        indicator : '" ._('Saving') . "',
                        submit : '" . _('Save') . "',
                        cancel : '" . _('Cancel') . "',
                        style : 'display:inline',
                        callback : function() {
                            // now we reload the comments part to show the comment we just submitted
                            $('#expcomment_container').load('experiments.php?mode=view&id=" .
                            $this->Entity->id . " #expcomment');
                            // we reload the function so editable zones are editable again
                            commentsUpdate();
                        }
                    })
                });
            }
            // TIMESTAMP
            function confirmTimestamp() {
                $('#confirm-timestamp').dialog({
                    resizable: false,
                    height: 'auto',
                    width: 400,
                    modal: true,
                    buttons: {
                        'Timestamp it': function() {
                            timestamp(" . $this->Entity->id . ");
                        },
                        Cancel: function() {
                            $(this).dialog('close');
                        }
                    }
                });
            }

            // READY ? GO !!
            $(document).ready(function() {
                $('#confirm-timestamp').hide();
                $('#commentsCreateButtonDiv').hide();

                // Keyboard shortcuts
                key('" . $_SESSION['prefs']['shortcuts']['create'] .
                    "', function(){location.href = 'app/controllers/ExperimentsController?create=true'});
                key('" . $_SESSION['prefs']['shortcuts']['edit'] .
                    "', function(){location.href = 'experiments.php?mode=edit&id=" . $this->Entity->id . "'});
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
        $Comments = new Comments($this->Entity);
        $commentsArr = $Comments->read();

        //  we need to add a container here so the reload function in the callback of .editable() doesn't mess things up
        $html = "<section id='expcomment_container'>";
        $html .= "<div id='expcomment' class='box'>";
        $html .= "<h3><img src='app/img/comment.png' alt='comment' />" . _('Comments') . "</h3>";

        if (is_array($commentsArr)) {
            // there is comments to display
            foreach ($commentsArr as $comment) {
                if (empty($comment['firstname'])) {
                    $comment['firstname'] = '[deleted]';
                }
                $html .= "<div class='expcomment_box'>
                    <img class='align_right' src='app/img/small-trash.png' ";
                $html .= "title='delete' alt='delete' onClick=\"commentsDestroy(" .
                    $comment['id'] . ", " . $this->Entity->id . ", '" . _('Delete this?') . "')\" />";
                $html .= "<span>On " . $comment['datetime'] . " " . $comment['firstname'] . " " .
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
            $this->Entity->id . ")'>" . _('Save') . "</button></div></div></section>";

        return $html;
    }
}
