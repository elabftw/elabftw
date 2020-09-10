/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
document.addEventListener('DOMContentLoaded', function() {
  // get the tab=X parameter in the url
  const params = new URLSearchParams(document.location.search.slice(1));
  let tab = parseInt(params.get('tab'), 10);
  if (tab % 1 !== 0) {
    tab = 1;
  }
  const initdiv = '#tab' + tab + 'div';
  const inittab = '#tab' + tab;
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
  // hide all subtabs on init
  $('.subdivhandle').hide();
  $('.subtabhandle').on('click', function(event) {
    const divhandle = '#' + event.target.id + 'div';
    $('.subdivhandle').hide();
    $(divhandle).show();
  });
});
