/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;

import { notif } from './misc';
import JSONEditor from 'jsoneditor';

// editor div
const container = document.getElementById('jsonEditorContainer');

const options = {
  modes: ['tree','code','view','form','text'],
  onModeChange: function(newMode) {
    if (newMode==='code' || newMode==='text'){
      $('#jsoneditor').height('800px');
    } else {
      $('#jsoneditor').removeAttr('style');
    }
  }
};

const editor = new JSONEditor(container, options);

// temporary fix for elabftw css where all input have padding of 7px until css is fixed
$('.jsoneditor-search').find('input').css('padding', '0px');

// fix the keymaster shortcut library interfering with the editor
key.filter = function(event): boolean {
  const tagName = (event.target || event.srcElement).tagName;
  return !(tagName == 'INPUT' || tagName == 'SELECT' || tagName == 'TEXTAREA' || (event.target || event.srcElement).hasAttribute('contenteditable'));
};

let currentFileUploadID: string;
let currentFileItemID: string;

// the loader action appears under .json uploaded files
$(document).on('click', '.jsonLoader', function() {
  $.get('app/download.php', {
    f: $(this).data('link')
  }).done(function(data){
    try {
      editor.set(JSON.parse(data));
      $('#jsonEditorDiv').show();
    } catch(e){
      // If it is just a parsing error, then we let the user edit the file.
      if (e.message.includes('JSON.parse')) {
        editor.setMode('text');
        editor.updateText(data);
        $('#jsonEditorDiv').show();
      } else {
        notif({'res': false, 'msg':'JSON Editor: ' + e.message});
      }
    }
  });
  currentFileUploadID = $(this).data('id');
  currentFileItemID = $(this).data('uploadid');
});

$(document).on('click', '.jsonSaver', function(){
  if (typeof currentFileUploadID === 'undefined') {
    // we are creating a new file
    const realName = prompt('Enter name of the file');
    if (realName == null) {
      return;
    }
    const id = $('#main_form').find('input[name="id"]').attr('value');
    $.post('app/controllers/EntityAjaxController.php', {
      addFromString: true,
      type: 'experiments',
      id: id,
      realName: realName,
      fileType: 'json',
      string: JSON.stringify(editor.get())
    }).done(function(json) {
      $('#filesdiv').load('experiments.php?mode=edit&id=' + id + ' #filesdiv');
      notif(json);
    });
  } else {
    // we are editing an existing file
    const formData = new FormData();
    const blob = new Blob([JSON.stringify(editor.get())], { type: 'application/json' });
    formData.append('replace', 'true');
    formData.append('upload_id', currentFileItemID);
    formData.append('id', currentFileUploadID);
    formData.append('type', 'experiments');
    formData.append('file', blob);

    $.ajax({
      url: 'app/controllers/EntityAjaxController.php',
      data: formData,
      processData: false,
      contentType: false,
      type: 'POST',
      success:function(json){
        notif(json);
      }
    });
  }
});
