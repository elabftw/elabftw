/**
 * profile.js - for the profile page
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    // GENERATE STATUS PIE CHART
    /* commented out because https://github.com/google/google-visualization-issues/issues/1356
    var json = $('#stats').data('stats');
    function drawChart() {
        var data = new google.visualization.DataTable(json);
        var options = {
            title: $('#stats').data('title'),
            backgroundColor: '#fff',
            colors: $('#stats').data('colors')
        };
        var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        chart.draw(data, options);
    }
    google.load('visualization', '1', {packages:['corechart']});
    google.setOnLoadCallback(drawChart);
    */

    $(document).ready(function() {
        // GENERATE API KEY
        $(document).on('click', '.generateApiKey', function() {
            $.post('app/controllers/UsersController.php', {
                generateApiKey: true
            }).done(function() {
                $("#api_div").load("profile.php #api_div");
            });
        });
    });
}());
