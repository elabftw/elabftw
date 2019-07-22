/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  // UPLOAD FORM
  // config for dropzone, id is camelCased.
  Dropzone.options.elabftwDropzone = {
    // i18n message to user
    dictDefaultMessage: $('#info').data('upmsg'),
    maxFilesize: $('#info').data('maxsize'), // MB
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    },
    init: function() {

      // add additionnal parameters (id and type)
      this.on('sending', function(file, xhr, formData) {
        formData.append('upload', true);
        formData.append('id', $('#info').data('id'));
        formData.append('type', $('#info').data('type'));
      });

      // once it is done
      this.on('complete', function(answer) {
        // check the answer we get back from app/controllers/EntityController.php
        const json = JSON.parse(answer.xhr.responseText);
        notif(json);
        // reload the #filesdiv once the file is uploaded
        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
          $('#filesdiv').load('?mode=edit&id=' + $('#info').data('id') + ' #filesdiv', function() {
            // make the comment zone editable (fix issue #54)
            makeEditableFileComment();
            displayMolFiles(); // eslint-disable-line no-undef
          });
        }
      });
    }
  };

  $(document).ready(function() {
    // add the title in the page name (see #324)
    document.title = $('#title_input').val() + ' - eLabFTW';

    let type = $('#info').data('type');
    let id = $('#info').data('id');
    let confirmText = $('#info').data('confirm');
    let location = 'experiments.php';
    if (type != 'experiments') {
      location = 'database.php';
    }

    // KEYBOARD SHORTCUT
    key($('#shortcuts').data('submit'), function() {
      document.forms.main_form.submit();
    });

    ////////////////
    // DATA RECOVERY

    // check if there is some local data with this id to recover
    if ((localStorage.getItem('id') == id) && (localStorage.getItem('type') == type)) {
      let bodyRecovery = $('<div></div>', {
        'class' : 'alert alert-warning',
        html: 'Recovery data found (saved on ' + localStorage.getItem('date') + '). It was probably saved because your session timed out and it could not be saved in the database. Do you want to recover it?<br><button class="button recover-yes">YES</button> <button class="button button-delete recover-no">NO</button><br><br>Here is what it looks like: ' + localStorage.getItem('body')
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
        title : document.getElementById('title_input').value,
        date : document.getElementById('datepicker').value,
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
    function getListFromMolFiles() {
      let mols = [];
      $.get('app/controllers/AjaxController.php', {
        getFiles: true,
        type: type,
        id: id,
      }).done(function(uploadedFiles) {
        uploadedFiles.forEach(function(upload) {
          if (upload.real_name.split('.').pop() === 'mol') {
            mols.push([upload.real_name, upload.long_name]);
          }
        });
        if (mols.length === 0) {
          notif({res: false, msg: 'No mol files found.'});
          return;
        }
        let listHtml = '<ul class="text-left">';
        mols.forEach(function(mol, index) {
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
        if (confirm(confirmText)) {
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

      constructor() {
        this.controller = 'database.php';
      }

      update(rating) {
        $.post(this.controller, {
          rating: rating,
          id: id
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


    // VISIBILITY SELECT
    $(document).on('change', '#visibility_select', function() {
      const visibility = $(this).val();
      $.post('app/controllers/EntityAjaxController.php', {
        updateVisibility: true,
        id: id,
        type: type,
        visibility: visibility
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
    let typingTimer;                // timer identifier
    const doneTypingInterval = 7000;  // time in ms between end of typing and save

    // user finished typing, save work
    function doneTyping() {
      quickSave(type, id);
    }

    // SWITCH EDITOR
    $(document).on('click', '.switchEditor', function() {
      let currentEditor = $(this).data('editor');
      if (currentEditor === 'md') {
        insertParamAndReload('editor', 'tiny');
      } else {
        insertParamAndReload('editor', 'md');
      }
    });

    // DISPLAY MARKDOWN EDITOR
    if ($('#body_area').hasClass('markdown-textarea')) {
      $('.markdown-textarea').markdown();
    }

    // INSERT IMAGE AT CURSOR POSITION IN TEXT
    $(document).on('click', '.inserter',  function() {
      // link to the image
      const url = 'app/download.php?f=' + $(this).data('link');
      // switch for markdown or tinymce editor
      const editor = $('#iHazEditor').data('editor');
      if (editor === 'md') {
        const cursorPosition = $('#body_area').prop('selectionStart');
        const content = $('#body_area').val();
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
    $(document).on('click', '.show-hide',  function() {
      let elem;

      if ($(this).data('type') === 'doodle') {
        elem = $('.canvasDiv');
      } else {
        elem = $('#chem_editor');
      }
      if (elem.is(':hidden')) {
        $(this).html('-');
        $(this).addClass('button-neutral');
      } else {
        $(this).html('+');
        $(this).removeClass('button-neutral');
      }
      elem.toggle();
    });

    // DATEPICKER
    $('#datepicker').datepicker({dateFormat: 'yymmdd'});
    // If the title is 'Untitled', clear it on focus
    $('#title_input').focus(function(){
      if ($(this).val() === $('#info').data('untitled')) {
        $('#title_input').val('');
      }
    });

    // ANNOTATE IMAGE
    $(document).on('click', '.annotateImg',  function() {
      $('.canvasDiv').show();
      $(document).scrollTop($('#doodle-anchor').offset().top);
      var context = document.getElementById('doodleCanvas').getContext('2d');
      var img = new Image();
      // set src attribute to image path
      img.src = 'app/download.php?f=' + $(this).data('path');
      img.onload = function(){
        // make canvas bigger than image
        context.canvas.width = this.width * 2;
        context.canvas.height = this.height * 2;
        // add image to canvas
        context.drawImage(img, this.width / 2, this.height / 2);
      };
    });
    // STAR RATING
    const StarC = new Star();
    $('.star').click(function() {
      StarC.update($(this).data('rating').current[0].innerText);
    });

    // EDITOR
    tinymce.init({
      mode: 'specific_textareas',
      editor_selector: 'mceditable',
      browser_spellcheck: true,
      skin_url: 'app/css/tinymce',
      plugins: 'table searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak mention codesample hr',
      pagebreak_separator: '<pagebreak>',
      toolbar1: 'undo redo | styleselect bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | link | save',
      removed_menuitems: 'newdocument, image',
      image_caption: true,
      content_style: '.mce-content-body {font-size:10pt;}',
      codesample_languages: [
        {text: 'Bash', value: 'bash'},
        {text: 'C', value: 'c'},
        {text: 'C++', value: 'cpp'},
        {text: 'CSS', value: 'css'},
        {text: 'Fortran', value: 'fortran'},
        {text: 'Go', value: 'go'},
        {text: 'HTML/XML', value: 'markup'},
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
      // save button :
      save_onsavecallback: function() {
        quickSave(type, id);
      },
      // keyboard shortcut to insert today's date at cursor in editor
      setup: function(editor) {
        editor.addShortcut('ctrl+shift+d', 'add date at cursor', function() { addDateOnCursor(); });
        editor.on('keydown', function() {
          clearTimeout(typingTimer);
        });
        editor.on('keyup', function() {
          clearTimeout(typingTimer);
          typingTimer = setTimeout(doneTyping, doneTypingInterval);
        });
      },
      mentions: {
        // use # for autocompletion
        delimiter: '#',
        // get the source from json with get request
        source: function (query, process) {
          const url = 'app/controllers/EntityAjaxController.php';
          $.getJSON(url, {
            mention: 1,
            term: query,
          }).done(function(data) {
            process(data);
          });
        }
      },
      language: $('#info').data('lang'),
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
      ]
    });
  });
}());
