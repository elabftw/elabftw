// create the editor
const container = document.getElementById('jsoneditor');

const options = {onChangeJSON:function(){
  $('.jsonSaver').removeAttr('disabled', 0).text('Save').css('cursor','pointer');
},
modes:['tree','code','view','form'],
onModeChange:function(newMode){
  if(newMode==='code'){$('#jsoneditor').height('800px');}
  else{$('#jsoneditor').removeAttr('style');}
}
};

var JSONEditor;
const editor = new JSONEditor(container, options);
$('.jsonSaver').attr('disabled', 1).text('Saved').css('cursor','default');
$('.jsonEditorDiv').hide();

var currentFileUploadID;
var currentFileItemID;

key.filter = function(event){
  var tagName = (event.target || event.srcElement).tagName;
  return !(tagName == 'INPUT' || tagName == 'SELECT' || tagName == 'TEXTAREA' || (event.target || event.srcElement).hasAttribute('contenteditable'));
};

$(document).on('click', '.jsonLoader', function(){
  $.get('app/download.php', {f:$(this).data('link')}).done(function(data){
    editor.set(JSON.parse(data));
    $('.jsonEditorDiv').show();
  });
  currentFileUploadID = $(this).data('id');
  currentFileItemID = $(this).data('uploadid');
});

$(document).on('click', '.jsonSaver', function(){
  var formData = new FormData();
  var blob = new Blob([JSON.stringify(editor.get())], { type: 'application/json' });
  formData.append('replace', true);
  formData.append('upload_id', currentFileItemID);
  formData.append('id', currentFileUploadID);
  formData.append('type', 'experiments');
  formData.append('file', blob);

  $.ajax({
    url: 'app/controllers/EntityController.php',
    data: formData,
    processData: false,
    contentType: false,
    type: 'POST',
    success:function(){
      $('.jsonSaver').attr('disabled', 1).text('Saved').css('cursor','default');
    }
  });
});
