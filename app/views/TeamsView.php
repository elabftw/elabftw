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
     * Output HTML for creating a team
     *
     * @return string $html
     */
    public function showCreate()
    {
        $html = "<div class='box'><h3>" . _('Add a New Team') . "</h3><hr>";
        $html .= "<input class='clean-form col-3-form' required type='text' placeholder='Pasteur lab' id='teamsName' />";
        $html .= "<button id='teamsCreateButton' onClick='teamsCreate()' class='button'>" . ('Save') . "</button></div>";

        return $html;
    }

    /**
     * Output HTML for making someone sysadmin
     *
     */
    public function showPromoteSysadmin()
    {
        $html = "<div class='box'><h3>" . _('Promote Someone to Sysadmin') . "</h3><hr>";
        $html .= "<input class='clean-form col-3-form' required type='email' placeholder='louis@pasteur.fr' id='promoteSysadmin' />";
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
        $teamsArr = $this->Teams->readAll();

        $html = "<div class='box'><h3>" . _('Edit Existing Teams') . "</h3><hr>";

        foreach ($teamsArr as $team) {
            $count = $this->Teams->getStats($team['team_id']);
            $html .= " <input class='clean-form col-3-form' onKeyPress='teamsUpdateButtonEnable(" . $team['team_id'] . ")' type='text' value='" . $team['team_name'] . "' id='team_" . $team['team_id'] . "' />";
            $html .= " <button disabled id='teamsUpdateButton_" . $team['team_id'] . "' onClick='teamsUpdate(" . $team['team_id'] . ")' class='button'>" . ('Save') . "</button>";
            if ($count['totusers'] == 0) {
                $html .= " <button id='teamsDestroyButton_" . $team['team_id'] . "' onClick='teamsDestroy(" . $team['team_id'] . ")' class='button button-delete'>" . ('Delete') . "</button>";
            } else {
                $html .= " <button id='teamsArchiveButton_" . $team['team_id'] . "' onClick='teamsArchive(" . $team['team_id'] . ")' class='button button-neutral'>" . ('Archive') . "</button>";
            }
            $html .= "<p class='smallgray'>" . _('Members') . ": " . $count['totusers'] . " − " . ngettext('Experiment', 'Experiments', $count['totxp']) . ": " . $count['totxp'] . " (" . $count['totxpts'] . " timestamped) − " . _('Items') . ": " . $count['totdb'] . " − " . _('Created') . ": " . $team['datetime'] . "<p>";
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * Output a line of stats for a team or for all
     *
     * @param bool|null team set to true to get stats from the team
     * @return string stats
     */
    public function showStats($team = null)
    {
        $stats = "";

        if ($team === null) {
            $count = $this->Teams->getAllStats();
            $stats .= _('Teams') . ": " . $count['totteams'] . " − ";
        } else {
            $count = $this->Teams->getStats($this->Teams->team);
        }
            $stats .= _('Members') . ": " . $count['totusers'] . " − " .
            ngettext('Experiment', 'Experiments', $count['totxp']) . ": " . $count['totxp'] . " (" . $count['totxpts'] . " timestamped) − " .
            _('Items') . ": " . $count['totdb'];

        return $stats;
    }

    /**
     * Generate HTML for sending a mass email
     *
     * @return string
     */
    public function showMassEmail()
    {
        $html = "<div class='box'><h3>" . _('Send a Mass Email') . "</h3><hr>";
        $html .= "<p>" . _('Email Subject') . "<br><input class='clean-form col-3-form' type='text' id='massSubject' size='45' /><br>";
        $html .= _('Email Body') . "<br><textarea class='clean-form col-textarea-form' id='massBody'></textarea><br>";
        $html .= "<button id='massSend' onClick='massSend()' class='button'>" . ('Send') . "</button>";
        $html .= "</p></div>";

        return $html;
    }
}
