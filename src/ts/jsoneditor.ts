/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare var key: any;

import { notif } from './misc';
import JSONEditor from 'jsoneditor';

function enableSaveButton(){
  $('.jsonSaver').removeAttr('disabled').text('Save').css('cursor','pointer');
}

// editor div
const container = document.getElementById('jsonEditorContainer');

const options = {onChange:enableSaveButton,
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

$('.jsonSaver').attr('disabled', 1);
$('.jsonEditorDiv').css('margin-top', '5px'); //Added some margin to allow the + icon to be separated from the editor
$('.jsoneditor-search').find('input').css('padding', '0px'); //Added to fix the search bar CSS issue. There is a problem with the inherited padding value from elabsftw CSS files

var currentFileUploadID;
var currentFileItemID;

key.filter = function(event){
  var tagName = (event.target || event.srcElement).tagName;
  return !(tagName == 'INPUT' || tagName == 'SELECT' || tagName == 'TEXTAREA' || (event.target || event.srcElement).hasAttribute('contenteditable'));
};

$(document).on('click', '.jsonLoader', function(){
  $.get('app/download.php', {f:$(this).data('link')}).done(function(data){
    try{
      editor.set(JSON.parse(data));
      $('.jsonEditorDiv').show();
    }
    catch(e){
      // If it is just a parsing error, then we let the user edit the file.
      if(e.message.includes('JSON.parse')){
        editor.setMode('text');
        editor.updateText(data);
        $('.jsonEditorDiv').show();
      }
      else{
        notif({'msg':'JSON Editor: ' + e.message});
      }
    }
  });
  currentFileUploadID = $(this).data('id');
  currentFileItemID = $(this).data('uploadid');
});

$(document).on('click', '.jsonSaver', function(){
  if(typeof currentFileUploadID === 'undefined'){
    const realName = prompt('Enter name of the file');
    if (realName == null) {
      return;
    }
    var id = $('#main_form').find('input[name="id"]').attr('value');
    $.post('app/controllers/EntityAjaxController.php', {
      addFromString: true,
      type: 'experiments',
      id: id,
      realName: realName,
      fileType: 'json',
      string: JSON.stringify(editor.get())
    }).done(function(json) {
      $('.jsonSaver').attr('disabled', 1).text('Saved').css('cursor','default');
      $('#filesdiv').load('experiments.php?mode=edit&id=' + id + ' #filesdiv');
      notif(json);
    });
  } else {
    var formData = new FormData();
    var blob = new Blob([JSON.stringify(editor.get())], { type: 'application/json' });
    formData.append('replace', 'true');
    formData.append('upload_id', currentFileItemID);
    formData.append('id', currentFileUploadID);
    formData.append('type', 'experiments');
    formData.append('file', blob);

    $.ajax({
      // TODO this should call an ajax controller that returns json
      url: 'app/controllers/EntityController.php',
      data: formData,
      processData: false,
      contentType: false,
      type: 'POST',
      success:function(){
        $('.jsonSaver').attr('disabled', 1).text('Saved').css('cursor','default');
      }
    });
  }
});
