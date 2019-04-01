/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  // READY ? GO !!
  $(document).ready(function() {
    var Templates = {
      controller: 'app/controllers/EntityAjaxController.php',
      saveToFile: function(id, name) {
        // we have the name of the template used for filename
        // and we have the id of the editor to get the content from
        // we don't use activeEditor because it requires a click inside the editing area
        var content = tinymce.get(id).getContent();
        var blob = new Blob([content], {type: 'text/plain;charset=utf-8'});
        saveAs(blob, name + '.elabftw.tpl');
      },
      destroy: function(id) {
        if (confirm('Delete this ?')) {
          $.post(this.controller, {
            destroy: true,
            id: id,
            type: 'experiments_tpl'
          }).done(function(json) {
            notif(json);
            if (json.res) {
              window.location.replace('ucp.php?tab=3');
            }
          });
        }
      }
    };

    $(document).on('click', '.saveToFile', function() {
      Templates.saveToFile($(this).data('id'), $(this).data('name'));
    });
    $(document).on('click', '.destroy-template', function() {
      Templates.destroy($(this).data('id'));
    });

    $(document).on('click', '#import-from-file', function() {
      $('#import_tpl').toggle();
    });

    // input to upload an elabftw.tpl file
    $('#import_tpl').hide().on('change', function() {
      var title = document.getElementById('import_tpl').value.replace('.elabftw.tpl', '').replace('C:\\fakepath\\', '');
      if (!window.FileReader) {
        alert('Please use a modern web browser. Import aborted.');
        return false;
      }
      var reader = new FileReader();
      reader.onload = function(e) {
        // switch for markdown mode
        if ($('#new_tpl_txt').hasClass('mceditable')) {
          tinymce.get('new_tpl_txt').setContent(e.target.result);
        } else {
          $('#new_tpl_txt').text(e.target.result);
        }
        $('#new_tpl_name').val(title);
        $('#import_tpl').hide();
      };

      reader.readAsText(this.files[0]);
    });

    // TinyMCE
    tinymce.init({
      mode : 'specific_textareas',
      editor_selector : 'mceditable',
      content_css : 'app/css/tinymce.css',
      plugins : 'table textcolor searchreplace code lists advlist fullscreen insertdatetime paste charmap save image link',
      toolbar1: 'undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link',
      removed_menuitems : 'newdocument',
      language : $('#language').data('lang')
    });
  });
}());
