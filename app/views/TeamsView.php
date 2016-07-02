<?php
/**
 * \Elabftw\Elabftw\TeamsView
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
class TeamsView extends Teams
{
    /**
     * Output HTML for creating a team
     *
     * @return string $html
     */
    public function showCreate()
    {
        $html = "<div class='box'><h3>" . _('Add a new team') . "</h3>";
        $html .= "<input required type='text' placeholder='Enter new team name' id='teamsName' />";
        $html .= "<button id='teamsCreateButton' onClick='teamsCreate()' class='button'>" . ('Save') . "</button></div>";

        return $html;
    }

    /**
     * Output HTML for making someone sysadmin
     *
     */
    public function showPromoteSysadmin()
    {
        $html = "<div class='box'><h3>" . _('Promote someone to sysadmin') . "</h3>";
        $html .= "<input required type='text' placeholder='Enter email address of user' id='promoteSysadmin' />";
        $html .= "<button id='promoteSysadminButton' onClick='promoteSysadmin()' class='button'>" . ('Save') . "</button></div>";

        return $html;
    }

    /**
     * Output HTML with all the teams
     *
     * @return string $html
     */
    public function show()
    {
        $teamsArr = $this->read();

        $html = "<div class='box'><h3>" . _('Edit existing teams') . "</h3>";

        foreach ($teamsArr as $team) {
            $count = $this->getStats($team['team_id']);
            $html .= " <input onKeyPress='teamsUpdateButtonEnable(" . $team['team_id'] . ")' type='text' value='" . $team['team_name'] . "' id='team_" . $team['team_id'] . "' />";
            $html .= " <button disabled id='teamsUpdateButton_" . $team['team_id'] . "' onClick='teamsUpdate(" . $team['team_id'] . ")' class='button'>" . ('Save') . "</button>";
            if ($count['totusers'] == 0) {
                $html .= " <button id='teamsDestroyButton_" . $team['team_id'] . "' onClick='teamsDestroy(" . $team['team_id'] . ")' class='button'>" . ('Delete') . "</button>";
            } else {
                $html .= " <button id='teamsArchiveButton_" . $team['team_id'] . "' onClick='teamsArchive(" . $team['team_id'] . ")' class='button'>" . ('Archive') . "</button>";
            }
            $html .= "<p>" . _('Members') . ": " . $count['totusers'] . " − " . ngettext('Experiment', 'Experiments', $count['totxp']) . ": " . $count['totxp'] . " (" . $count['totxpts'] . " timestamped) − " . _('Items') . ": " . $count['totdb'] . " − " . _('Created') . ": " . $team['datetime'] . "<p>";
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * Output a line of stats for a team or for all
     *
     * @param int|null team
     * @return string stats
     */
    public function showStats($team = null)
    {
        $stats = "";

        if ($team === null) {
            $count = $this->getAllStats();
            $stats .= _('Teams') . ": " . $count['totteams'] . " − ";
        } else {
            $count = $this->getStats($team);
        }
            $stats .= _('Members') . ": " . $count['totusers'] . " − " .
            ngettext('Experiment', 'Experiments', $count['totxp']) . ": " . $count['totxp'] . " (" . $count['totxpts'] . " timestamped) − " .
            _('Items') . ": " . $count['totdb'];

        return $stats;
    }
}
