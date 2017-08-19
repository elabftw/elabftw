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
     * Display the stars rating for a DB item
     *
     * @param int $rating The number of stars to display
     * @return string HTML of the stars
     */
    public function showStars($rating)
    {
        $html = "";

        $green = "<img src='app/img/star-green.png' alt='☻' />";
        $gray = "<img src='app/img/star-gray.png' alt='☺' />";

        $html .= str_repeat($green, $rating);
        $html .= str_repeat($gray, (5 - $rating));

        return $html;
    }
}
