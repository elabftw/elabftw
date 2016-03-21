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

    /** HTML that will be outputed */
    private $html = '';

    /** object holding class Comments */
    private $comments;

    /** ID of the experiment we want to view */
    private $id;

    /** TODO remove */
    private $pdo;

    /**
     * Need an ID of an experiment
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->experiments = new Experiments();
        $this->comments = new Comments();
        $this->pdo = Db::getConnection();
    }

    /**
     * View experiment
     *
     * @return string HTML for viewXP
     */
    public function generateHTML()
    {
        $this->experiment = $this->experiments->read($this->id);
        $this->visibility = $this->setVisibility();
        $this->ro = $this->isReadOnly();
        if ($this->isTimestamped()) {
            $this->html .= $this->showTimestamp();
        }
        $this->html .= $this->showMain();
        return $this->html;
    }

    private function setVisibility()
    {
        // if visibility of experiment is an int, it is a team_groups
        // so we want to display the name of the group
        if (is_pos_int($this->experiment['visibility'])) {
            $sql = "SELECT name FROM team_groups WHERE id = :id";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':id', $this->experiment['visibility'], PDO::PARAM_INT);
            $req->execute();
            return $req->fetchColumn();
        }
        return $this->experiment['visibility'];
    }

    private function isInTeamGroup($userid, $groupid)
    {
        $sql = "SELECT DISTINCT userid FROM users2team_groups WHERE groupid = :groupid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':groupid', $this->visibility);
        $req->execute();
        $authUsersArr = array();
        while ($authUsers = $req->fetch()) {
            $authUsersArr[] = $authUsers['userid'];
        }

        return in_array($userid, $authUsersArr);
    }

    private function getOwnerInfos()
    {
        // get who owns the experiment
        $sql = 'SELECT firstname, lastname, team FROM users WHERE userid = :userid';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->experiment['userid']);
        $req->execute();

        return $req->fetch();
    }

    private function isReadOnly()
    {
        // Check id is owned by connected user to show read only message if not
        if ($this->experiment['userid'] != $_SESSION['userid']) {
            // Can the user see this experiment which is not his ?
            if ($this->visibility === 'user') {

                throw new Exception(_("<strong>Access forbidden:</strong> the visibility setting of this experiment is set to 'owner only'."));

            } elseif (is_pos_int($this->visibility)) {
                // the visibility of this experiment is set to a group
                // we must check if current user is in this group
                if (!$this->isInTeamGroup($_SESSION['userid'], $this->visibility)) {
                    throw new Exception(_("<strong>Access forbidden:</strong> you don't have the rights to access this."));
                }

            } else {
                $owner = $this->getOwnerInfos($this->experiment['userid']);

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

    private function isTimestamped() {
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

    private function showMain()
    {

            $html = "<section class='item' style='padding:15px;border-left: 6px solid #" . $this->experiment['color'] . "'>";
            $html .= "<span class='top_right_status'><img src='img/status.png'>" . $this->experiment['name'] .
                "<img src='img/eye.png' alt='eye' />" . $this->visibility . "</span>";
            $html .=  "<span class='date_view'><img src='img/calendar.png' class='bot5px' title='date' alt='Date :' /> " . Tools::formatDate($this->experiment['date']) . "</span><br />
            <a href='experiments.php?mode=edit&id=".$this->experiment['id'] . "'><img src='img/pen-blue.png' title='edit' alt='edit' /></a>
        <a href='app/duplicate_item.php?id=".$this->experiment['id'] . "&type=exp'><img src='img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a>
        <a href='make.php?what=pdf&id=".$this->experiment['id'] . "&type=experiments'><img src='img/pdf.png' title='make a pdf' alt='pdf' /></a>
        <a href='make.php?what=zip&id=".$this->experiment['id'] . "&type=experiments'><img src='img/zip.png' title='make a zip archive' alt='zip' /></a> ";

        if ($this->experiment['locked'] == 0) {
            $html .= "<a href='app/lock.php?id=" . $this->experiment['id'] . "&action=lock&type=experiments'><img src='img/unlock.png' title='lock experiment' alt='lock' /></a>";
        } else { // experiment is locked
            $html .= "<a href='app/lock.php?id=" . $this->experiment['id'] . "&action=unlock&type=experiments'><img src='img/lock-gray.png' title='unlock experiment' alt='unlock' /></a>";
            // show timestamp button if it's not timestamped already
            if (!$this->isTimestamped()) {
                $html .= "<a onClick=\"return confirmStamp('" . _('Once timestamped an experiment cannot be edited anymore ! Are you sure you want to do this ?') . "')\" href='app/timestamp.php?id=" . $this->experiment['id'] . "'><img src='img/stamp.png' title='timestamp experiment' alt='timestamp' /></a>";
            }
        }

        // TAGS
        //$html .= show_tags($id, 'experiments_tags');
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

        $html .= $this->showLinks($id, 'view');

        // DISPLAY eLabID
        $html .= "<p class='elabid'>" . _('Unique eLabID:') . " " . $this->experiment['elabid'];
        $html .= "</section>";

        return $html;
    }

    /**
     * Output html for displaying links
     *
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
     * Display comments for an experiment
     *
     */
    public function showComments()
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
                    <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick=\"commentsDestroy(".$comment['id'] . ", " . $this->id . ", '" .  _('Delete this?') . "')\" />";
                $html .= "<span class='smallgray'>On " . $comment['datetime'] . " " . $comment['firstname'] . " " . $comment['lastname'] . " wrote :</span><br />";
                $html .= "<p class='editable' id='" . $comment['id'] . "'>" . $comment['comment'] . "</p></div>";
            }
        }
        return $html;
    }

    /**
     * HTML for the add new comment block
     */
    public function showCommentsCreate()
    {
        $html = "<textarea onFocus='commentsCreateButtonDivShow()' id='commentsCreateArea' placeholder='" . _('Add a comment') . "'></textarea>";
        $html .= "<div id='commentsCreateButtonDiv' class='submitButtonDiv'>";
        $html .= "<button class='button' id='commentsCreateButton' onClick='commentsCreate(" . $this->id . ")'>" . _('Save') . "</button></div></div></section>";

        return $html;
    }
}
