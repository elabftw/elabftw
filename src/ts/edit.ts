/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
declare let MathJax: any;
import { displayMolFiles, display3DMolecules, insertParamAndReload, notif } from './misc';
import { getTinymceBaseConfig, quickSave } from './tinymce';
import 'jquery-ui/ui/widgets/datepicker';
import './doodle';
import tinymce from 'tinymce/tinymce';
import Dropzone from 'dropzone';
import i18next from 'i18next';

// the dropzone is created programmatically, disable autodiscover
Dropzone.autoDiscover = false;

$(document).ready(function() {
  if ($('#info').data('page') !== 'edit') {
    return;
  }

  // UPLOAD FORM
  new Dropzone('form#elabftw-dropzone', {
    // i18n message to user
    //dictDefaultMessage: $('#info').data('upmsg'),
    dictDefaultMessage: i18next.t('dropzone-upload-area'),
    maxFilesize: $('#info').data('maxsize'), // MB
    timeout: 900000,
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    },
    init: function(): void {

      // add additionnal parameters (id and type)
      this.on('sending', function(file: string, xhr: string, formData: any) {
        formData.append('upload', true);
        formData.append('id', $('#info').data('id'));
        formData.append('type', $('#info').data('type'));
      });

      // once it is done
      this.on('complete', function(answer: any) {
        // check the answer we get back from app/controllers/EntityController.php
        const json = JSON.parse(answer.xhr.responseText);
        notif(json);
        // reload the #filesdiv once the file is uploaded
        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
          $('#filesdiv').load('?mode=edit&id=' + $('#info').data('id') + ' #filesdiv', function() {
            displayMolFiles();
            display3DMolecules(true);
            const dropZone = Dropzone.forElement('#elabftw-dropzone');

            // Check to make sure the success function is set by tinymce and we are dealing with an image drop and not a regular upload
            if (typeof dropZone.tinyImageSuccess !== 'undefined' && dropZone.tinyImageSuccess !== null) {
              let url = $('#uploadsDiv').children().last().find('img').attr('src');
              // This is from the html element that shows the thumbnail. The ending appended to the original upload is: "_th.jpg"
              // Removing this appendage allows us to have the original file. This is a hack to demonstrate the pasting functionality.
              url = url.substring(0, url.length-7);
              dropZone.tinyImageSuccess(url);
              // This is to make sure that we do not end up adding a file to tinymce if a previous file was pasted and a consecutive file was uploaded using Dropzone.
              // The 'undefined' check is not enough. That is just for before any file was pasted.
              dropZone.tinyImageSuccess = null;
            }
          });
        }
      });
    }
  });

  // add the title in the page name (see #324)
  document.title = $('#title_input').val() + ' - eLabFTW';

  const type = $('#info').data('type');
  const id = $('#info').data('id');
  let location = 'experiments.php';
  if (type != 'experiments') {
    location = 'database.php';
  }

  // KEYBOARD SHORTCUT
  key($('#shortcuts').data('submit'), function() {
    $('#main_form').submit();
  });

  ////////////////
  // DATA RECOVERY

  // check if there is some local data with this id to recover
  if ((localStorage.getItem('id') == id) && (localStorage.getItem('type') == type)) {
    const bodyRecovery = $('<div></div>', {
      'class' : 'alert alert-warning',
      html: 'Recovery data found (saved on ' + localStorage.getItem('date') + '). It was probably saved because your session timed out and it could not be saved in the database. Do you want to recover it?<br><button class="button recover-yes">YES</button> <button class="button btn btn-danger recover-no">NO</button><br><br>Here is what it looks like: ' + localStorage.getItem('body')
    });
    $('#main_section').before(bodyRecovery);
  }

  // RECOVER YES
  $(document).on('click', '.recover-yes', function() {
    $.post('app/controllers/EntityAjaxController.php', {
      quickSave: true,
      type : type,
      id : id,
      // we need this to get the updated content
      title : (document.getElementById('title_input') as HTMLInputElement).value,
      date : (document.getElementById('datepicker') as HTMLInputElement).value,
      body : localStorage.getItem('body')
    }).done(function() {
      localStorage.clear();
      document.location.reload(true);
    });
  });

  // RECOVER NO
  $(document).on('click', '.recover-no', function() {
    localStorage.clear();
    document.location.reload();
  });

  // END DATA RECOVERY
  ////////////////////

  // GET MOL FILES
  function getListFromMolFiles(): void {
    const mols: any = [];
    $.get('app/controllers/Ajax.php', {
      action: 'readAll',
      what: 'upload',
      type: type,
      params: {
        itemId: id,
      },
    }).done(function(json) {
      const uploadedFiles = json.msg;
      uploadedFiles.forEach(function(upload: any) {
        if (upload.real_name.split('.').pop() === 'mol') {
          mols.push([upload.real_name, upload.long_name]);
        }
      });
      if (mols.length === 0) {
        notif({res: false, msg: 'No mol files found.'});
        return;
      }
      let listHtml = '<ul class="text-left">';
      mols.forEach(function(mol: any, index: any) {
        listHtml += '<li style="color:#29aeb9" class="clickable loadableMolLink" data-target="app/download.php?f=' + mols[index][1] + '">' + mols[index][0] + '</li>';
      });
      $('.getMolButton').text('Refresh list');
      $('.getMolDiv').html(listHtml + '</ul>');
    });
  }

  $(document).on('click', '.getMolButton', function() {
    getListFromMolFiles();
  });

  // Load the content of a mol file from the list in the mol editor
  $(document).on('click', '.loadableMolLink', function() {
    $.get($(this).data('target')).done(function(molContent) {
      $('#sketcher_open_text').val(molContent);
    });
  });
  // END GET MOL FILES

  class Entity {

    destroy(): void {
      if (confirm(i18next.t('entity-delete-warning'))) {
        const controller = 'app/controllers/EntityAjaxController.php';
        $.post(controller, {
          destroy: true,
          id: id,
          type: type
        }).done(function(json) {
          notif(json);
          if (json.res) {
            window.location.replace(location);
          }
        });
      }
    }
  }

  class Star {
      controller: string;

      constructor() {
        this.controller = 'app/controllers/EntityAjaxController.php';
      }

      update(rating: any): void {
        $.post(this.controller, {
          rating: rating,
          id: id,
          type: 'items',
        }).done(function(json) {
          notif(json);
        });
      }
  }

  // DESTROY ENTITY
  const EntityC = new Entity();
  $(document).on('click', '.entityDestroy', function() {
    EntityC.destroy();
  });

  // CAN READ/WRITE SELECT
  $(document).on('change', '.permissionSelect', function() {
    const value = $(this).val();
    const rw = $(this).data('rw');
    $.post('app/controllers/EntityAjaxController.php', {
      updatePermissions: true,
      rw: rw,
      id: id,
      type: type,
      value: value,
    }).done(function(json) {
      notif(json);
    });
  });

  // STATUS SELECT
  $(document).on('change', '#category_select', function() {
    const categoryId = $(this).val();
    $.post('app/controllers/EntityAjaxController.php', {
      updateCategory: true,
      id: id,
      type: type,
      categoryId : categoryId
    }).done(function(json) {
      notif(json);
      if (json.res) {
        // change the color of the item border
        // we first remove any status class
        $('#main_section').css('border', null);
        // and we add our new border color
        // first : get what is the color of the new status
        const css = '6px solid #' + json.color;
        $('#main_section').css('border-left', css);
      }
    });
  });

  // SWITCH EDITOR
  $(document).on('click', '.switchEditor', function() {
    const currentEditor = $(this).data('editor');
    if (currentEditor === 'md') {
      insertParamAndReload('editor', 'tiny');
    } else {
      insertParamAndReload('editor', 'md');
    }
  });

  // DISPLAY MARKDOWN EDITOR
  if ($('#body_area').hasClass('markdown-textarea')) {
    ($('.markdown-textarea') as any).markdown({
      onPreview: function() {
        // ask mathjax to reparse the page
        // if we call typeset directly it doesn't work
        // so add a timeout
        setTimeout(function() {
          MathJax.typeset();
        }, 1);
      }
    });
  }

  // DATEPICKER
  $('#datepicker').datepicker({
    dateFormat: 'yymmdd',
    onClose: (date) => {
      $.post('app/controllers/EntityAjaxController.php', {
        updateDate: true,
        type : type,
        id : id,
        date : date,
      }).done((json) => notif(json));
    },
  });

  // If the title is 'Untitled', clear it on focus
  $('#title_input').focus(function(){
    if ($(this).val() === i18next.t('entity-default-title')) {
      $('#title_input').val('');
    }
  });

  // ANNOTATE IMAGE
  $(document).on('click', '.annotateImg',  function() {
    $('#doodleDiv').show();
    $(document).scrollTop($('#doodle-anchor').offset().top);
    const context: CanvasRenderingContext2D = (document.getElementById('doodleCanvas') as HTMLCanvasElement).getContext('2d');
    const img = new Image();
    // set src attribute to image path
    img.src = 'app/download.php?f=' + $(this).data('path');
    img.onload = (): void => {
      // make canvas bigger than image
      context.canvas.width = (this as HTMLImageElement).width * 2;
      context.canvas.height = (this as HTMLImageElement).height * 2;
      // add image to canvas
      context.drawImage(img, (this as HTMLImageElement).width / 2, (this as HTMLImageElement).height / 2);
    };
  });
  // STAR RATING
  const StarC = new Star();
  $(document).on('click', '.rating-cancel', function() {
    StarC.update(0);
  });
  $(document).on('click', '.star', function() {
    StarC.update($(this).data('rating').current[0].innerText);
  });

  // Object to hold control data for selected image
  let tinymceEditImage = {
    selected: false,
    uploadId: '',
    itemId: '',
    url:'',
  };

  const tinyConfig = getTinymceBaseConfig('edit');
  const tinyConfigForEdit = {
    images_upload_handler: (blobInfo, success) => { // eslint-disable-line @typescript-eslint/camelcase
      const dropZone = Dropzone.forElement('#elabftw-dropzone');
      // Edgecase for editing an image using tinymce ImageTools
      // Check if it was selected. This is set by an event hook below
      if (tinymceEditImage.selected == true && confirm(i18next.t('replace-edited-file'))) {
        // Use jquery to replace the file on the server
        const formData = new FormData();
        formData.append('replace', 'true');
        formData.append('upload_id', tinymceEditImage.uploadId);
        formData.append('id', tinymceEditImage.itemId);
        formData.append('type', 'experiments');
        formData.append('file', blobInfo.blob());

        $.post({
          url: 'app/controllers/EntityAjaxController.php',
          data: formData,
          processData: false,
          contentType: false
        }).done(function(json) {
          notif(json);
          // Send the same url we stored before the edit menu was clicked to tinymce
          success(tinymceEditImage.url);
          tinymceEditImage = {selected:false, uploadId:'', itemId:'', url:''};
        });
      // If the blob has no filename, ask for one. (Firefox edgecase: Embedded image in Data URL)
      } else if (typeof blobInfo.blob().name=== 'undefined') {
        const filename = prompt('Enter filename with extension e.g. .jpeg');
        if (typeof filename !== 'undefined' && filename !== null) {
          const fileOfBlob = new File([blobInfo.blob()], filename);
          dropZone.addFile(fileOfBlob);
          dropZone.tinyImageSuccess = success;
        } else {
          // Just disregard the edit if the name prompt is cancelled
          tinymce.activeEditor.undoManager.undo();
        }
      } else {
        dropZone.addFile(blobInfo.blob());
        dropZone.tinyImageSuccess = success;
      }
    },
    // use a custom function for the save button in toolbar
    save_onsavecallback: () => quickSave(type , $('#info').data('id')), // eslint-disable-line @typescript-eslint/camelcase
  };

  tinymce.init(Object.assign(tinyConfig, tinyConfigForEdit));
  // Hook into the SelectionChange event - This is to make sure we reset our control variable correctly
  tinymce.activeEditor.on('SelectionChange', () => {
    // Check if the user has selected an image
    if (tinymce.activeEditor.selection.getNode().tagName == 'IMG')
    {
      // Save all the details needed for replacing upload
      // Then check for and get those details when you are handling file uploads
      let url = (tinymce.activeEditor.selection.getNode() as any).src;
      url = url.slice(url.lastIndexOf('app/'));
      // Sometimes tinymce adds an identifier on modification
      // This checks for and removes it
      if (url.lastIndexOf('&') != -1){
        url = url.slice(0, url.lastIndexOf('&'));
      }
      // Find the element in the uploads html section to got uploadid and itemid
      const uploadsDestroyEl = $('a[href="' + url + '"]' ).prev();
      tinymceEditImage.selected = true;
      tinymceEditImage.uploadId = uploadsDestroyEl.data('id');
      tinymceEditImage.itemId = uploadsDestroyEl.data('itemid');
      tinymceEditImage.url = url;
    } else {
      tinymceEditImage = {selected:false, uploadId:'', itemId:'', url:''};
    }
  });

  // INSERT IMAGE AT CURSOR POSITION IN TEXT
  $(document).on('click', '.inserter',  function() {
    // link to the image
    const url = 'app/download.php?f=' + $(this).data('link');
    // switch for markdown or tinymce editor
    const editor = $('#iHazEditor').data('editor');
    if (editor === 'md') {
      const cursorPosition = $('#body_area').prop('selectionStart');
      const content = ($('#body_area').val() as string);
      const before = content.substring(0, cursorPosition);
      const after = content.substring(cursorPosition);
      const imgMdLink = '\n![image](' + url + ')\n';
      $('#body_area').val(before + imgMdLink + after);
    } else if (editor === 'tiny') {
      const imgHtmlLink = '<img src="' + url + '" />';
      tinymce.activeEditor.execCommand('mceInsertContent', false, imgHtmlLink);
    } else {
      alert('Error: could not find current editor!');
    }
  });

  // IMPORT BODY OF LINKED ITEM INTO EDITOR
  // this is here because here tinymce exists and is reachable
  // before this code was in steps-links.ts but it was not working
  const theEditor = tinymce.editors[0];
  function importBody(elem): void {
    const id = elem.data('linkid');
    const editor = $('#iHazEditor').data('editor');
    $.get('app/controllers/EntityAjaxController.php', {
      getBody : true,
      id : id,
      type : 'items',
      editor: editor
    }).done(function(json) {
      if (editor === 'tiny') {
        theEditor.insertContent(json.msg);

      } else if (editor === 'md') {
        const cursorPosition = $('#body_area').prop('selectionStart');
        const content = ($('#body_area').val() as string);
        const before = content.substring(0, cursorPosition);
        const after = content.substring(cursorPosition);
        $('#body_area').val(before + json.msg + after);

      } else {
        alert('Error: could not find current editor!');
      }
    });
  }
  $(document).on('click', '.linkImport', function() {
    importBody($(this));
  });
  // update title on blur
  $('#main_form').on('blur', '#title_input', function() {
    $.post('app/controllers/EntityAjaxController.php', {
      updateTitle: true,
      type : type,
      id : id,
      title : $(this).val(),
    }).done((json) => notif(json));
  });
});
