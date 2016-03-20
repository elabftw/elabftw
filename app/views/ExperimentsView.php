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

/**
 * Experiments
 */
class ExperimentsView
{

    public function __construct()
    {
        $this->experiments = new Experiments();
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
}
