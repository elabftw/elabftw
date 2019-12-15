/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-jeditable/src/jquery.jeditable.js';
import '@fancyapps/fancybox/dist/jquery.fancybox.js';
import { notif, displayMolFiles } from './misc';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });
  displayMolFiles(); // eslint-disable-line no-undef

  // REPLACE UPLOAD toggle form
  $(document).on('click', '.replaceUpload', function() {
    $(this).next('.replaceUploadForm').toggle();
  });

  // make file comments editable
  $(document).on('mouseenter', '.file-comment', function() {
    (<any>$('.editable')).editable(function(value: string) {
      $.post('app/controllers/EntityAjaxController.php', {
        updateFileComment : true,
        type: $(this).data('type'),
        comment : value,
        comment_id : $(this).attr('id'),
        id: $(this).data('itemid')
      }).done(function(json) {
        notif(json);
      });

      return(value);
    }, {
      tooltip : 'File comment',
      placeholder: 'File comment',
      indicator : 'Saving...',
      name : 'fileComment',
      onedit: function() {
        if ($(this).text() === 'Click to add a comment') {
          $(this).text('');
        }
      },
      submit : 'Save',
      onblur : 'ignore',
      cancel : 'Cancel',
      submitcssclass : 'button btn btn-primary',
      cancelcssclass : 'button btn btn-danger',
      style : 'display:inline'
    });
  });

  // Export mol in png
  $(document).on('click', '.saveAsImage', function() {
    let molCanvasId = $(this).parent().siblings().find('canvas').attr('id');
    let png = (<any>document.getElementById(molCanvasId)).toDataURL();
    $.post('app/controllers/EntityAjaxController.php', {
      saveAsImage: true,
      realName: $(this).data('name'),
      content: png,
      id: $('#info').data('id'),
      type: $('#info').data('type')
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#filesdiv').load('?mode=edit&id=' + $('#info').data('id') + ' #filesdiv', function() {
          displayMolFiles(); // eslint-disable-line no-undef
        });
      }
    });
  });

  // DESTROY UPLOAD
  $(document).on('click', '.uploadsDestroy', function() {
    var itemid = $(this).data('itemid');
    if (confirm($(this).data('msg'))) {
      $.post('app/controllers/EntityAjaxController.php', {
        uploadsDestroy: true,
        upload_id: $(this).data('id'),
        id: itemid,
        type: $(this).data('type')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#filesdiv').load('?mode=edit&id=' + itemid + ' #filesdiv', function() {
            displayMolFiles(); // eslint-disable-line no-undef
          });
        }
      });
    }
  });

  // ACTIVATE FANCYBOX
  (<any>$('[data-fancybox]')).fancybox();
});
