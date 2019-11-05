// create the editor
const container = document.getElementById('jsoneditor');

const options = {onChangeJSON:function(){
  $('.jsonSaver').removeAttr('disabled', 0).text('Save').css('cursor','pointer');
}};

var JSONEditor;
const editor = new JSONEditor(container, options);
$('.jsonSaver').attr('disabled', 1).text('Saved').css('cursor','default');
$('.jsonEditorDiv').hide();

var currentFileType;
var currentFileUploadID;
var currentFileItemID;

$(document).on('click', '.jsonLoader', function(){
  $.get('app/download.php', {f:$(this).data('link')}).done(function(data){
    editor.set(JSON.parse(data));
    $('.jsonEditorDiv').show();
  });
  currentFileType = $(this).data('type');
  currentFileUploadID = $(this).data('id');
  currentFileItemID = $(this).data('uploadid');
});

$(document).on('click', '.jsonSaver', function(){
  $.post('app/controllers/EntityAjaxController.php', {
    updateJsonFile: true,
    id:currentFileUploadID,
    upload_id: currentFileItemID,
    type: currentFileType,
    content: JSON.stringify(editor.get())
  }).done(function(data){
    notif(data);
    if(data.msg==='JSON file updated successfully'){
      $('.jsonSaver').attr('disabled', 1).text('Saved').css('cursor','default');
    }
  });
});
