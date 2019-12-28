/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import 'jquery-jeditable/src/jquery.jeditable.js';
import { notif } from './misc';
// not working
//import { key } from '../js/vendor/keymaster.js';
declare let key: any;

$(document).ready(function() {
  // add the title in the page name (see #324)
  document.title = $('.title_view').text() + ' - eLabFTW';

  const type = $('#info').data('type');
  const id = $('#info').data('id');

  // EDIT
  key($('#shortcuts').data('edit'), function() {
    window.location.href = '?mode=edit&id=' + id;
  });

  // TOGGLE LOCK
  $('#lock').on('click', function() {
    $.post('app/controllers/EntityAjaxController.php', {
      lock: true,
      type: type,
      id: id
    }).done(function(json) {
      notif(json);
      if (json.res) {
        // change the lock icon
        $('#lock').toggleClass('fa-lock-open').toggleClass('fa-lock');
      }
    });
  });

  // CLICK TITLE TO GO IN EDIT MODE
  $(document).on('click', '.click2Edit', function() {
    window.location.href = '?mode=edit&id=' + id;
  });

  // DECODE ASN1
  $(document).on('click', '.decodeAsn1', function() {
    $.post('app/controllers/ExperimentsAjaxController.php', {
      asn1: $(this).data('token'),
      id: $(this).data('id')
    }).done(function(data) {
      $('#decodedDiv').html(data.msg);
    });
  });

  // DUPLICATE
  $('.duplicateItem').on('click', function() {
    $.post('app/controllers/EntityAjaxController.php', {
      duplicate: true,
      id: $(this).data('id'),
      type: type
    }).done(function(data) {
      window.location.replace('?mode=edit&id=' + data.msg);
    });
  });

  // SHARE
  $('.shareItem').on('click', function() {
    $.post('app/controllers/EntityAjaxController.php', {
      getShareLink: true,
      id: $(this).data('id'),
      type: type
    }).done(function(data) {
      $('#shareLinkInput').val(data.msg).toggle().focus().select();
    });
  });

  // TIMESTAMP
  $(document).on('click', '#goForTimestamp', function() {
    $(this).prop('disabled', true);
    $.post('app/controllers/ExperimentsAjaxController.php', {
      timestamp: true,
      id: id
    }).done(function(json) {
      if (json.res) {
        window.location.replace('experiments.php?mode=view&id=' + id);
      } else {
        $('.modal-body').css('color', 'red');
        $('.modal-body').html(json.msg);
      }
    });
  });
});
