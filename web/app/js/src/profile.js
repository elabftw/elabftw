/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  function drawChart() {
    var json = $('#stats').data('stats');
    var data = new google.visualization.DataTable(json);
    var options = {
      title: $('#stats').data('title'),
      backgroundColor: '#fff',
      colors: $('#stats').data('colors')
    };
    var chart = new google.visualization.PieChart(document.getElementById('pieChart'));
    chart.draw(data, options);
  }

  $(document).ready(function() {

    // GENERATE STATUS PIE CHART
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

  });
}());
