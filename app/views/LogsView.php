<?php
/**
 * \Elabftw\Elabftw\LogsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * HTML for the teams
 */
class LogsView extends Logs
{
    protected $logs;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->logs = new Logs();
    }

    /**
     * Output HTML for dispaying the logs
     *
     * @return string $html
     */
    public function show()
    {
        $logsArr = $this->logs->read();
        $html = "<div id='logsDiv'>";
        $html .= "<div class='well'><ul>";
        if (empty($logsArr)) {
            $html .= "<li>" . _('Nothing to display') . ".</li>";
        } else {
            foreach ($logsArr as $logs) {
                $html .= "<li>✪ " . $logs['datetime'] . " [" . $logs['type'] . "] " .
                    $logs['body'] . " (" . $logs['user'] . ")</li>";
            }
        }
        $html .= "</ul></div>";
        $html .= "<div class='submitButtonDiv'>";
        $html .= "<button id='logsDestroyButton' onClick='logsDestroy()' class='button'>" .
            ('Clear all logs') . "</button></div></div>";

        return $html;
    }
}
