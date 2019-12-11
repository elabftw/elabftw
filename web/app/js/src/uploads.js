/**
 * uploads.js - for the uploaded files
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  $(document).ready(function() {
    //displayMolFiles(); // eslint-disable-line no-undef

    // REPLACE UPLOAD toggle form
    $(document).on('click', '.replaceUpload', function() {
      $(this).next('.replaceUploadForm').toggle();
    });

    // make file comments editable
    $(document).on('mouseenter', '.file-comment', function() {
      $('.editable').editable(function(value) {
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
        submitcssclass : 'button',
        cancelcssclass : 'button button-delete',
        style : 'display:inline'
      });
    });

    // Export mol in png
    $(document).on('click', '.saveAsImage', function() {
      let molCanvasId = $(this).parent().siblings().find('canvas').attr('id');
      let png = document.getElementById(molCanvasId).toDataURL();
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
            //displayMolFiles(); // eslint-disable-line no-undef
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
              //displayMolFiles(); // eslint-disable-line no-undef
            });
          }
        });
      }
    });
  });
}());
