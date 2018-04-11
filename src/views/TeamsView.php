<?php
/**
 * \Elabftw\Elabftw\TeamsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * HTML for the teams
 */
class TeamsView
{
    /** Teams instance */
    public $Teams;

    /**
     * Constructor
     *
     * @param Teams $teams
     */
    public function __construct(Teams $teams)
    {
        $this->Teams = $teams;
    }

    /**
     * Output a line of stats for a team or for all
     *
     * @param bool team set to true to get stats from the team
     * @return string stats
     */
    public function showStats(bool $team = false): string
    {
        $stats = '';

        if ($team) {
            $count = $this->Teams->getStats($this->Teams->Users->userData['team']);
        } else {
            $count = $this->Teams->getAllStats();
            $stats .= _('Teams') . ': ' . $count['totteams'] . ' − ';
        }

        $stats .= _('Members') . ': ' . $count['totusers'] . ' − ' .
        ngettext('Experiment', 'Experiments', $count['totxp']) . ': ' . $count['totxp'] . ' (' . $count['totxpts'] . ' timestamped) − ' .
        _('Items') . ': ' . $count['totdb'];

        return $stats;
    }
}
