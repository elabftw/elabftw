/**
 * tabs.js - for the tabs
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// get the tab=X parameter in the url
var params = getGetParameters();
var tab = parseInt(params.tab, 10);
if (tab % 1 !== 0) {
  tab = 1;
}
var initdiv = '#tab' + tab + 'div';
var inittab = '#tab' + tab;
// init
$('.divhandle').hide();
$(initdiv).show();
$(inittab).addClass('selected');

$('.tabhandle' ).click(function(event) {
  var tabhandle = '#' + event.target.id;
  var divhandle = '#' + event.target.id + 'div';
  $('.divhandle').hide();
  $(divhandle).show();
  $('.tabhandle').removeClass('selected');
  $(tabhandle).addClass('selected');
});

/**
 * SUB TABS for templates
 */
initdiv = '#subtab_1div';
inittab = '#subtab_1';
// init
$('.subdivhandle').hide();
$(initdiv).show();
$(inittab).addClass('selected');

$('.subtabhandle' ).click(function(event) {
  var tabhandle = '#' + event.target.id;
  var divhandle = '#' + event.target.id + 'div';
  $('.subdivhandle').hide();
  $(divhandle).show();
  $('.subtabhandle').removeClass('badgetabactive');
  $(tabhandle).addClass('badgetabactive');
});
