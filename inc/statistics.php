<?php
/**
 * inc/statistics.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

// Count number of experiments for each status
// get all status name and id
$Status = new Status();
$statusArr = $Status->read($_SESSION['team_id']);

$status_arr = array();
$status_colors = array();
$count_arr = array();

foreach ($statusArr as $status) {
    $status_arr[$status['id']] = $status['name'];
    $status_colors[] = $status['color'];
}

foreach ($status_arr as $key => $value) {
    $sql = "SELECT COUNT(*)
        FROM experiments
        WHERE userid = :userid
        AND status = :status";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid']);
    $req->bindParam(':status', $key);
    $req->execute();
    $count_arr[$key] = $req->fetchColumn();
}

$percent_arr = array();

// BEGIN PAGE
echo "<section class='box'>";

// Make percentage
if ($count === 0) {
    echo _('No statistics available yet.'); // fix division by zero
} else {
    foreach ($status_arr as $key => $value) {
        $percent_arr[$value] = round(($count_arr[$key] / $count) * 100);
    }

    // BEGIN CONTENT
    echo "<img src='img/statistics.png' alt='' class='bot5px' /> <h4 style='display:inline'>" . _('Statistics') . "</h4>";
    ?>
     <!--Load the AJAX API-->
    <script src="https://www.google.com/jsapi"></script>
    <script>
          google.load('visualization', '1', {packages:['corechart']});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'status');
            data.addColumn('number', 'Experiments number');
            data.addRows([
    <?php
    foreach ($percent_arr as $name => $percent) {
        // as we replace all the quotes (ENT_QUOTES), we need to translate ' to \'
        // otherwise the js code is broken
        $name = str_replace("'", "\'", html_entity_decode($name, ENT_QUOTES));
        echo "['$name', $percent],";
    }
    ?>
            ]);

            var options = {
                title: '<?php echo _('Experiments for') . ' ' . $_SESSION['username']; ?>',
                backgroundColor: '#fff',
                colors: [
    <?php
    // string that will hold the list of colors correctly formatted
    $color_list = "";
    foreach ($status_colors as $color) {
        $color_list .= "'#" . $color . "',";
    }
    // remove last ,
    $color_list = rtrim($color_list, ",");
    echo $color_list;
    ?>
            ]};
            var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
            chart.draw(data, options);
          }
        </script>
     <div id="chart_div" class='center'></div>
    <?php
}
?>
</section>
