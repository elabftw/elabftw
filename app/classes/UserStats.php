<?php
/**
 * \Elabftw\Elabftw\UserStats
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Generate and display experiments statistics for a user
 */
class UserStats
{

    /** our team */
    private $team;

    /** id of our user */
    private $userid;

    /** count of experiments */
    private $count = 0;

    /** pdo object */
    private $pdo;

    /** array with status id and count */
    private $countArr = array();

    /** array with status id and name */
    private $statusArr = array();

    /** array with colors for status */
    private $statusColors = array();

    /** array with percentage and status name */
    private $percentArr = array();

    /**
     * Init the object with a userid and the total count of experiments
     *
     * @param int $team
     * @param int $userid
     * @param int $count total count of experiments
     */
    public function __construct($team, $userid, $count)
    {
        $this->team = $team;
        $this->userid = $userid;
        $this->count = $count;
        $this->pdo = Db::getConnection();
        $this->countStatus();
        $this->makePercent();
    }

    /**
     * Count number of experiments for each status
     *
     */
    private function countStatus()
    {
        // get all status name and id
        $Status = new Status($this->team);
        $statusAll = $Status->readAll();

        // populate arrays
        foreach ($statusAll as $status) {
            $this->statusArr[$status['id']] = $status['name'];
            $this->statusColors[] = $status['color'];
        }

        // count experiments for each status
        foreach ($this->statusArr as $key => $value) {
            $sql = "SELECT COUNT(*)
                FROM experiments
                WHERE userid = :userid
                AND status = :status";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':userid', $this->userid);
            $req->bindParam(':status', $key);
            $req->execute();
            $this->countArr[$key] = $req->fetchColumn();
        }
    }

    /**
     * Create an array with status name => percent
     *
     */
    private function makePercent()
    {
        foreach ($this->statusArr as $key => $value) {
            $this->percentArr[$value] = round(($this->countArr[$key] / $this->count) * 100);
        }
    }

    /**
     * Generate a JS list of colors
     *
     * @return string
     */
    private function getColorList()
    {
        // string that will hold the list of colors correctly formatted
        $colorList = "";
        foreach ($this->statusColors as $color) {
            $colorList .= "'#" . $color . "',";
        }
        // remove last ,
        $colorList = rtrim($colorList, ",");

        return $colorList;
    }

    /**
     * Generate HTML for the graph
     *
     * @return string
     */
    public function show()
    {
        $html = "<img src='app/img/statistics.png' alt='' /> <h4 style='display:inline'>" . _('Statistics') . "</h4>";
        $html .= "<hr>";
        $html .= "<script src='https://www.google.com/jsapi'></script>";
        $html .= "<script>
          google.load('visualization', '1', {packages:['corechart']});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'status');
            data.addColumn('number', 'Experiments number');
            data.addRows([";
        foreach ($this->percentArr as $name => $percent) {
            // as we replace all the quotes (ENT_QUOTES), we need to translate ' to \'
            // otherwise the js code is broken
            $name = str_replace("'", "\'", html_entity_decode($name, ENT_QUOTES));
            $html .= "['$name', $percent],";
        }
        $html .= "]);";

        $html .= "var options = {
            title: '" . ngettext('Experiment', 'Experiments', 2) . "',
            backgroundColor: '#fff',
            colors: [" . $this->getColorList() . "]};";
        $html .= "var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
            chart.draw(data, options);
          }
        </script>";
        $html .= "<div id='chart_div' class='center'></div>";

        return $html;
    }
}
