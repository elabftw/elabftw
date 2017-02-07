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
        $this->limit = $this->Entity->Users->userData['limit_nb'];

    }

    /**
     * View item
     *
     * @return string HTML for viewDB
     */
    public function view()
    {
        $this->html .= $this->UploadsView->buildUploads('view');

        return $this->html;
    }

    /**
     * Display the stars rating for a DB item
     *
     * @param int $rating The number of stars to display
     * @return string HTML of the stars
     */
    public function showStars($rating)
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
