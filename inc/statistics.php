<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
// Count number of experiments for each case of outcome
// SUCCESS
$outcome_arr = array();
$count_arr = array();
$outcome_arr[] = 'success';
$outcome_arr[] = 'fail';
$outcome_arr[] = 'redo';
$outcome_arr[] = 'running';
foreach ($outcome_arr as $outcome){
$sql = "SELECT COUNT(id)
    FROM experiments 
    WHERE userid = :userid 
    AND outcome LIKE'".$outcome."'";
$req = $bdd->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid']);
$req->execute();
$count_arr[] = $req->fetch();
}

$success = $count_arr[0][0];
$fail = $count_arr[1][0];
$redo = $count_arr[2][0];
$running = $count_arr[3][0];
// MAKE TOTAL
$total = ($success + $fail + $redo + $running);
// Make percentage
$success_p = round(($success / $total)*100);
$fail_p = round(($fail / $total)*100);
$redo_p = round(($redo / $total)*100);
$running_p = round(($running / $total)*100);
$total_p = ($success_p + $fail_p + $redo_p + $running_p);

// BEGIN CONTENT
echo "<img src='themes/".$_SESSION['prefs']['theme']."/img/statistics.png' alt='' /> <h4>STATISTICS</h4>";
?>
<script type='text/javascript' src='js/google-jsapi.js'></script>
<script type='text/javascript'>
      //google.load('visualization', '1', {packages:['imagepiechart']});
      google.load('visualization', '1', {packages:['corechart']});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Outcome');
        data.addColumn('number', 'Experiments number');
        data.addRows([
            ['Running', <?php echo $running_p;?>],
            ['Fail',  <?php echo $fail_p;?>],
            ['Need to be redone',    <?php echo $redo_p;?>],
            ['Success',      <?php echo $success_p;?>],
                      ]);

        var options = {
            title: 'Experiments for <?php echo $_SESSION['username'];?>',
            backgroundColor: '#EEE'
        }
        var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
 <div id="chart_div" class='center'></div>
