/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
declare let MathJax: any;
import { addDateOnCursor, displayMolFiles, insertParamAndReload, notif, quickSave } from './misc';
import 'jquery-ui/ui/widgets/datepicker';
import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autosave';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/hr';
import 'tinymce/plugins/image';
import 'tinymce/plugins/imagetools';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import '../../web/app/js/plugins/mention/plugin.js';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/paste';
import 'tinymce/plugins/save';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/template';
import 'tinymce/themes/silver';
import 'tinymce/themes/mobile';
import './doodle';
import '../js/tinymce-langs/ca_ES.js';
import '../js/tinymce-langs/de_DE.js';
import '../js/tinymce-langs/en_GB.js';
import '../js/tinymce-langs/es_ES.js';
import '../js/tinymce-langs/fr_FR.js';
import '../js/tinymce-langs/id_ID.js';
import '../js/tinymce-langs/it_IT.js';
import '../js/tinymce-langs/ja_JP.js';
import '../js/tinymce-langs/ko_KR.js';
import '../js/tinymce-langs/nl_BE.js';
import '../js/tinymce-langs/pl_PL.js';
import '../js/tinymce-langs/pt_BR.js';
import '../js/tinymce-langs/pt_PT.js';
import '../js/tinymce-langs/ru_RU.js';
import '../js/tinymce-langs/sk_SK.js';
import '../js/tinymce-langs/sl_SI.js';
import '../js/tinymce-langs/zh_CN.js';
import Dropzone from 'dropzone';
import i18next from 'i18next';

// the dropzone is created programmatically, disable autodiscover
Dropzone.autoDiscover = false;

$(document).ready(function() {
  if ($('#info').data('page') !== 'edit') {
    return;
  }

  // UPLOAD FORM
  const elabDropzone = new Dropzone('form#elabftw-dropzone', {
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
    $.get('app/controllers/AjaxController.php', {
      getFiles: true,
      type: type,
      id: id,
    }).done(function(uploadedFiles) {
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

    destroy() {
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

      update(rating: any) {
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

  // AUTOSAVE
  let typingTimer: any;                // timer identifier
  const doneTypingInterval = 7000;  // time in ms between end of typing and save

  function isOverCharLimit() {
    const body = tinymce.get(0).getBody(), text = tinymce.trim(body.innerText || body.textContent);
    return text.length > 1000000;
  }

  // user finished typing, save work
  function doneTyping() {
    if (isOverCharLimit()) {
      alert('Too many characters!!! Cannot save properly!!!');
      return;
    }
    quickSave(type, id);
  }

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

  // SHOW/HIDE THE DOODLE CANVAS/CHEM EDITOR
  $(document).on('click', '.plusMinusButton',  function() {
    if ($(this).html() === '+') {
      $(this).html('-').addClass('btn-neutral').removeClass('btn-primary');
    } else {
      $(this).html('+').removeClass('btn-neutral').addClass('btn-primary');
    }
  });

  // DATEPICKER
  $('#datepicker').datepicker({dateFormat: 'yymmdd'});
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
    img.onload = function(){
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

  /* eslint-disable */
  tinymce.init({
    mode: 'specific_textareas',
    editor_selector: 'mceditable',
    browser_spellcheck: true,
    skin_url: 'app/css/tinymce',
    plugins: 'autosave table searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak mention codesample hr template',
    pagebreak_separator: '<pagebreak>',
    toolbar1: 'undo redo | styleselect bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | link | save',
    removed_menuitems: 'newdocument, image',
    image_caption: true,
    images_reuse_filename: true,
    paste_data_images: true,
    images_upload_handler: function (blobInfo, success, failure) {
      let dropZone = Dropzone.forElement('#elabftw-dropzone');
      // If the blob has no filename, ask for one. (Firefox edgecase: Embedded image in Data URL)
      if (typeof blobInfo.blob().name=== 'undefined'){
        let fileOfBlob = new File([blobInfo.blob()], prompt('Enter filename with extension e.g. .jpeg'));
        dropZone.addFile(fileOfBlob);
      } else {
        dropZone.addFile(blobInfo.blob());
        dropZone.tinyImageSuccess = success;
      }
    },
    content_style: '.mce-content-body {font-size:10pt;}',
    codesample_languages: [
      {text: 'Bash', value: 'bash'},
      {text: 'C', value: 'c'},
      {text: 'C++', value: 'cpp'},
      {text: 'CSS', value: 'css'},
      {text: 'Fortran', value: 'fortran'},
      {text: 'Go', value: 'go'},
      {text: 'Java', value: 'java'},
      {text: 'JavaScript', value: 'javascript'},
      {text: 'Julia', value: 'julia'},
      {text: 'Latex', value: 'latex'},
      {text: 'Makefile', value: 'makefile'},
      {text: 'Matlab', value: 'matlab'},
      {text: 'Perl', value: 'perl'},
      {text: 'Python', value: 'python'},
      {text: 'R', value: 'r'},
      {text: 'Ruby', value: 'ruby'}
    ],
    language: $('#user-prefs').data('lang'),
    charmap_append: [
      [0x2640, 'female sign'],
      [0x2642, 'male sign']
    ],
    mentions: {
      // use # for autocompletion
      delimiter: '#',
      // get the source from json with get request
      source: function (query: string, process: any) {
        const url = 'app/controllers/EntityAjaxController.php';
        $.getJSON(url, {
          mention: 1,
          term: query,
        }).done(function(data) {
          process(data);
        });
      }
    },
    mobile: {
      theme: 'mobile',
      plugins: [ 'save', 'lists', 'link' ],
      toolbar: [ 'undo', 'redo', 'bold', 'italic', 'underline', 'bullist', 'numlist', 'link' ]
    },
    // save button :
    save_onsavecallback: function() {
      quickSave(type , id);
    },
    // keyboard shortcut to insert today's date at cursor in editor
    setup: function(editor: any) {
      editor.addShortcut('ctrl+shift+d', 'add date at cursor', function() { addDateOnCursor(); });
      editor.addShortcut('ctrl+=', 'subscript', function() {
        editor.execCommand('subscript');
      });
      editor.addShortcut('ctrl+shift+=', 'superscript', function() {
        editor.execCommand('superscript');
      });
      editor.on('keydown', function() {
        clearTimeout(typingTimer);
      });
      editor.on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(doneTyping, doneTypingInterval);
      });
    },
    style_formats_merge: true,
    style_formats: [
      {
        title: 'Image Left',
        selector: 'img',
        styles: {
          'float': 'left',
          'margin': '0 10px 0 10px'
        }
      }, {
        title: 'Image Right',
        selector: 'img',
        styles: {
          'float': 'right',
          'margin': '0 0 10px 10px'
        }
      }
    ],
    // this will GET templates from current user
    templates: 'app/controllers/AjaxController.php?getUserTpl'
  });
  /* eslint-enable */

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
});
