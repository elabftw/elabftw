/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// DISPLAY COMMENT TIME RELATIVE TO NOW
function relativeMoment() {
  $.each($('.relative-moment'), function(i, el) {
    let commentTime = el.title;
    let relMom = moment(commentTime, 'YYYY-MM-DD H:m:s').fromNow();
    el.textContent = relMom;
  });
}

$(document).ready(function() {
  // i18n for moment.js
  //moment.locale($('#info').data('locale'));
  relativeMoment();
});
