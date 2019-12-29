/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getGetParameters } from './misc';

document.addEventListener('DOMContentLoaded', function() {
  // get the tab=X parameter in the url
  const params = getGetParameters();
  let tab = parseInt((params as any).tab, 10);
  if (tab % 1 !== 0) {
    tab = 1;
  }
  let initdiv = '#tab' + tab + 'div';
  let inittab = '#tab' + tab;
  // init
  $('.divhandle').hide();
  $(initdiv).show();
  $(inittab).addClass('selected');

  $('.tabhandle' ).on('click', function(event) {
    const tabhandle = '#' + event.target.id;
    const divhandle = '#' + event.target.id + 'div';
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

  $('.subtabhandle' ).on('click', function(event) {
    const tabhandle = '#' + event.target.id;
    const divhandle = '#' + event.target.id + 'div';
    $('.subdivhandle').hide();
    $(divhandle).show();
    $('.subtabhandle').removeClass('badgetabactive');
    $(tabhandle).addClass('badgetabactive');
  });
});
