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
import * as $3Dmol from '3dmol/build/3Dmol-nojquery.js';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });
  displayMolFiles();

  // REPLACE UPLOAD toggle form
  $(document).on('click', '.replaceUpload', function() {
    $(this).next('.replaceUploadForm').toggle();
  });

  // make file comments editable
  $(document).on('mouseenter', '.file-comment', function() {
    ($('.editable') as any).editable(function(value: string) {
      $.post('app/controllers/EntityAjaxController.php', {
        updateFileComment : true,
        type: $(this).data('type'),
        comment : value,
        commentId : $(this).attr('id'),
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
    const molCanvasId = $(this).parent().siblings().find('canvas').attr('id');
    const png = (document.getElementById(molCanvasId) as any).toDataURL();
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
          displayMolFiles();
        });
      }
    });
  });

  // DESTROY UPLOAD
  $(document).on('click', '.uploadsDestroy', function() {
    const itemid = $(this).data('itemid');
    if (confirm($(this).data('msg'))) {
      $.post('app/controllers/EntityAjaxController.php', {
        uploadsDestroy: true,
        uploadId: $(this).data('id'),
        id: itemid,
        type: $(this).data('type')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#filesdiv').load('?mode=edit&id=' + itemid + ' #filesdiv', function() {
            displayMolFiles();
          });
        }
      });
    }
  });

  // ACTIVATE FANCYBOX
  $('[data-fancybox]').fancybox();

  // 3DMOL
  // Top left menu to change the style of the displayed molecule
  $('.dropdown-item').on('click', '.3dmol-style', function() {
    const targetStyle = $(this).data('style');
    let options = {};
    const style = {};
    if (targetStyle === 'cartoon') {
      options = { color: 'spectrum' };
    }
    style[targetStyle] = options;

    $3Dmol.viewers[$(this).data('divid')].setStyle(style).render();
  });
});
