<?php
/**
 * \Elabftw\Elabftw\TeamGroupsView
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * HTML for the team groups
 */
class TeamGroupsView
{
    /** The PDO object */
    private $pdo;

    /** instance of TeamGroups */
    public $TeamGroups;

    /**
     * Constructor
     *
     * @param TeamGroups $teamGroups
     */
    public function __construct(TeamGroups $teamGroups)
    {
        $this->TeamGroups = $teamGroups;
        $this->pdo = Db::getConnection();
    }

    /**
     * Output html for displaying a list of existing team groups
     *
     * @param array $teamGroupsArr The full array from read()
     * @return string $html The HTML listing groups and users
     */
    public function show($teamGroupsArr)
    {
        $sql = "SELECT DISTINCT CONCAT(users.firstname, ' ', users.lastname) AS name
            FROM users CROSS JOIN users2team_groups
            ON (users2team_groups.userid = users.userid AND users2team_groups.groupid = :groupid)";

        $html = '';

        foreach ($teamGroupsArr as $teamGroup) {
            $html .= "<div class='well'>";
            $html .= "<a class='elab-tooltip' style='float:right;' href='#'>";
            $html .= "<span>Remove this Group</span>";
            $html .= "<img onclick=\"teamGroupDestroy(" . $teamGroup['id'] . ", '" . str_replace(array("\"", "'"), '', _('Delete this?')) . "')\" src='app/img/small-trash.png' style='float:right' alt='trash' /></a>";
            $html .= "<h3 title='Click to Edit' class='inline editable teamgroup_name' id='teamgroup_" . $teamGroup['id'] . "'>" . $teamGroup['name'] . "</h3>";
            $html .= "<ul class='group-list'>";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':groupid', $teamGroup['id']);
            $req->execute();
            while ($user = $req->fetch()) {
                $html .= "<li>" . $user['name'] . "</li>";
            }
            $html .= "</ul></div>";
        }
        return $html;
    }
}
