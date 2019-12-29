/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { GoogleCharts } from 'google-charts';

function drawChart(): void {
  const json = $('#stats').data('stats');
  const data = new GoogleCharts.api.visualization.DataTable(json);
  const options = {
    title: $('#stats').data('title'),
    backgroundColor: '#fff',
    colors: $('#stats').data('colors')
  };
  const chart = new GoogleCharts.api.visualization.PieChart(document.getElementById('pieChart'));
  chart.draw(data, options);
}

$(document).ready(function() {
  // GENERATE STATUS PIE CHART
  GoogleCharts.load(drawChart);
});
