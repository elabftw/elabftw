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
        $this->html .= $this->UploadsView->buildUploadForm();
        $this->html .= $this->UploadsView->buildUploads('edit');

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
