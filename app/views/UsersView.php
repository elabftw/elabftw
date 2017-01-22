<?php
/**
 * \Elabftw\Elabftw\UsersView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Show html related to users edition
 *
 */
class UsersView
{

    /** Users instance */
    private $Users;

    /**
     * Constructor
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Users = $users;
    }
    /**
     * Display users edit form. Show all users if $team is null
     *
     * @param int|null $team
     * @return string
     */
    public function showEditUsers($team = null)
    {
        $Auth = new Auth();

        if ($team === null) {
            $usersArr = $this->Users->readAll();
        } else {
            $usersArr = $this->Users->readAllFromTeam($team);
        }

        $html = "<ul class='list-group'>";

        foreach ($usersArr as $user) {
            $html .= "<li>";
            $html .= "<form method='post' action='app/controllers/UsersController.php'>";
            if ($team === null) {
                $html .= "<input type='hidden' value='1' name='fromSysconfig' />";
                $html .= "<p>" . _('Team') . ": " . $user['teamname'] . "</p>";
            }
            $html .= "<input type='hidden' value='true' name='usersUpdate' />";
            $html .= "<input type='hidden' value='" . $user['userid'] . "' name='userid' />";
            $html .= "<ul class='list-inline'>";
            $html .= "<li><label class='block' for='usersUpdateFirstname'>" . _('Firstname') . "</label>";
            $html .= "<input class='clean-form' id='usersUpdateFirstname' type='text' value='" .
                $user['firstname'] . "' name='firstname' /></li>";
            $html .= "<li><label class='block' for='usersUpdateLastname'>" . _('Lastname') . "</label>";
            $html .= "<input class='clean-form' id='usersUpdateLastname' type='text' value='" .
                $user['lastname'] . "' name='lastname' /></li>";
            $html .= "<li><label class='block' for='usersUpdateEmail'>" . _('Email') . "</label>";
            $html .= "<input class='clean-form' id='usersUpdateEmail' type='email' value='" .
                $user['email'] . "' name='email' /></li>";
            $html .= "<li>";
            $html .= "<label class='block' for='usersUpdateValidated'>" . _('Has an active account?') . "</label>";
            $html .= "<select class='clean-form' name='validated' id='usersUpdateValidated'>";
            $html .= "<option value='1'";
            if ($user['validated'] == '1') {
                $html .= " selected='selected'";
            }
            $html .= ">" . _('Yes') . "</option>";

            $html .= "<option value='0'";
            if ($user['validated'] == '0') {
                $html .= " selected='selected'";
            }
            $html .= ">" . _('No') . "</option>";
            $html .= "</select>";
            $html .= "</li>";
            $html .= "<li><label class='block' for='usersUpdateUsergroup'>" . _('Group') . "</label>";
            $html .= "<select class='clean-form' name='usergroup' id='usersUpdateUsergroup'>";
            if ($_SESSION['is_sysadmin']) {
                $html .= "<option value='1'";
                if ($user['usergroup'] == 1) {
                    $html .= " selected='selected'";
                }
                $html .= ">Sysadmins</option>";
            }

            $html .= "<option value='2'";
            if ($user['usergroup'] == 2) {
                $html .= " selected='selected'";
            }
            $html .= ">Admins</option>";

            $html .= "<option value='3'";
            if ($user['usergroup'] == 3) {
                $html .= " selected='selected'";
            }
            $html .= ">Admin + Lock power</option>";

            $html .= "<option value='4'";
            if ($user['usergroup'] == 4) {
                $html .= " selected='selected'";
            }
            $html .= ">Users</option>";
            $html .= "</select></li>";
            $html .= "<li><label class='block' for='usersUpdatePassword'>" . _('Reset user password') . "</label>";
            // add empty input to prevent FF from putting password in field
            // because autocomplete doesn't work
            // from http://stackoverflow.com/questions/17781077/autocomplete-off-is-not-working-on-firefox
            $html .= "<input type='text' style='display:none'>";
            $html .= "<input type='password' style='display:none'>";
            $html .= "<input autocomplete='new-password' class='clean-form' id='usersUpdatePassword' type='password' pattern='.{0}|.{" .
                $Auth::MIN_PASSWORD_LENGTH . ",}' value='' name='password' />";
            $html .= "<span class='smallgray'>" .
                $Auth::MIN_PASSWORD_LENGTH . " " . _('characters minimum') . "</span></li>";
            $html .= "</ul>";
            $html .= "<button type='submit' class='button'>" . _('Save') . "</button>";
            $html .= "</form>";
            $html .= "</li>";
            $html .= "<hr>";
        }
        $html .= "</ul>";

        return $html;
    }
}
