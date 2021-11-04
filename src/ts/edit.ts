/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
import { notif } from './misc';
import { getTinymceBaseConfig, quickSave } from './tinymce';
import { EntityType, Target, Upload, Payload, Method, Action } from './interfaces';
import './doodle';
import tinymce from 'tinymce/tinymce';
import { getEditor } from './Editor.class';
import { getEntity } from './misc';
import Dropzone from 'dropzone';
import i18next from 'i18next';
import { Metadata } from './Metadata.class';
import { Ajax } from './Ajax.class';
import UploadClass from './Upload.class';
import EntityClass from './Entity.class';

// the dropzone is created programmatically, disable autodiscover
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }

  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in edit mode
  if (about.page !== 'edit') {
    return;
  }

  const entity = getEntity();
  const EntityC = new EntityClass(entity.type);

  // add extra fields elements from metadata json
  const MetadataC = new Metadata(entity);
  MetadataC.display('edit');

  // Which editor are we using? md or tiny
  const editor = getEditor();
  editor.init();

  // UPLOAD FORM
  new Dropzone('form#elabftw-dropzone', {
    // i18n message to user
    dictDefaultMessage: i18next.t('dropzone-upload-area'),
    maxFilesize: $('#info').data('maxsize'), // MB
    timeout: 900000,
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
    },
    init: function(): void {

      // add additional parameters (id and type)
      this.on('sending', function(file: string, xhr: string, formData: FormData) {
        formData.append('upload', '1');
        formData.append('type', entity.type);
        formData.append('id', String(entity.id));
      });

      // once it is done
      this.on('complete', function(answer: any) {
        // check the answer we get back from the controller
        const json = JSON.parse(answer.xhr.responseText);
        notif(json);
        // reload the #filesdiv once the file is uploaded
        if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
          $('#filesdiv').load(`?mode=edit&id=${String(entity.id)} #filesdiv > *`, function() {
            const dropZone = Dropzone.forElement('#elabftw-dropzone');
            // Check to make sure the success function is set by tinymce and we are dealing with an image drop and not a regular upload
            if (typeof dropZone.tinyImageSuccess !== 'undefined' && dropZone.tinyImageSuccess !== null) {
              // Uses the newly updated HTML element for the uploads section to find the last file uploaded and use that to get the remote url for the image.
              let url = $('#uploadsDiv').children().last().find('[id^=upload-filename]').attr('href');
              // Slices out the url by finding the &name query param from the download link. This does not care about extensions or thumbnails.
              url = url.slice(0, url.indexOf('&name='));
              // This gives tinyMce the actual url of the uploaded image. TinyMce updates its editor to link to this rather than the temp location it sets up initially.
              dropZone.tinyImageSuccess(url);
              // This is to make sure that we do not end up adding a file to tinymce if a previous file was pasted and a consecutive file was uploaded using Dropzone.
              // The 'undefined' check is not enough. That is just for before any file was pasted.
              dropZone.tinyImageSuccess = null;
            }
          });
        }
      });
    },
  });

  ////////////////
  // DATA RECOVERY

  // check if there is some local data with this id to recover
  if ((localStorage.getItem('id') == String(entity.id)) && (localStorage.getItem('type') == entity.type)) {
    const bodyRecovery = $('<div></div>', {
      'class' : 'alert alert-warning',
      html: 'Recovery data found (saved on ' + localStorage.getItem('date') + '). It was probably saved because your session timed out and it could not be saved in the database. Do you want to recover it?<br><button class="button btn btn-primary recover-yes">YES</button> <button class="button btn btn-danger recover-no">NO</button><br><br>Here is what it looks like: ' + localStorage.getItem('body'),
    });
    $('#main_section').before(bodyRecovery);
  }

  // RECOVER YES
  $(document).on('click', '.recover-yes', function() {
    EntityC.update(entity.id, Target.Body, localStorage.getItem('body')).then(() => {
      localStorage.clear();
      document.location.reload();
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
    const UploadC = new UploadClass(entity);
    UploadC.read().then(json => {
      for (const upload of json.value as Array<Upload>) {
        if (upload.real_name.split('.').pop() === 'mol') {
          mols.push([upload.real_name, upload.long_name]);
        }
      }
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

  // Shared function to UPDATE ENTITY BODY via save shortcut and/or save button
  function updateEntity(el?: HTMLElement): void {
    EntityC.update(entity.id, Target.Body, editor.getContent()).then(json => {
      if (json.res && editor.type === 'tiny') {
        // set the editor as non dirty so we can navigate out without a warning to clear
        tinymce.activeEditor.setDirty(false);
      }
    }).then(() => {
      if (el && el.matches('[data-redirect="view"]')) {
        window.location.replace('?mode=view&id=' + entity.id);
      }
    });
  }

  // KEYBOARD SHORTCUT
  key(about.scsubmit, () => updateEntity());

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // UPDATE ENTITY BODY
    if (el.matches('[data-action="update-entity-body"]')) {
      updateEntity(el);

    // SWITCH EDITOR
    } else if (el.matches('[data-action="switch-editor"]')) {
      editor.switch();

    // IMPORT BODY OF LINKED ITEM INTO EDITOR
    } else if (el.matches('[data-action="import-link"]')) {
      // this is here because here tinymce exists and is reachable
      // before this code was in steps-links.ts but it was not working
      const id = el.dataset.target;
      $.get('app/controllers/EntityAjaxController.php', {
        getBody : true,
        id : id,
        type : 'items',
        editor: editor.type,
      }).done(json => editor.setContent(json.msg));

    // DESTROY ENTITY
    } else if (el.matches('[data-action="destroy"]')) {
      if (confirm(i18next.t('entity-delete-warning'))) {
        const path = window.location.pathname;
        EntityC.destroy(entity.id).then(json => {
          if (json.res) {
            window.location.replace(path.split('/').pop());
          }
        });
      }
    }
  });

  // CAN READ/WRITE SELECT
  $(document).on('change', '.permissionSelect', function() {
    const value = $(this).val();
    const rw = $(this).data('rw');
    $.post('app/controllers/EntityAjaxController.php', {
      updatePermissions: true,
      rw: rw,
      id: entity.id,
      type: entity.type,
      value: value,
    }).done(function(json) {
      notif(json);
    });
  });

  // TRANSFER OWNERSHIP
  document.getElementById('new_owner').addEventListener('change', () => {
    const value = (document.getElementById('new_owner') as HTMLInputElement).value;
    EntityC.update(entity.id, Target.UserId, value).then(json => {
      if (json.res) {
        window.location.reload();
      }
    });
  });

  // STATUS SELECT
  $(document).on('change', '#category_select', function() {
    const categoryId = $(this).val();
    $.post('app/controllers/EntityAjaxController.php', {
      updateCategory: true,
      id: entity.id,
      type: entity.type,
      categoryId : categoryId,
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

  // TITLE STUFF
  const titleInput = document.getElementById('title_input') as HTMLInputElement;
  // add the title in the page name (see #324)
  document.title = titleInput.value + ' - eLabFTW';

  // If the title is 'Untitled', clear it on focus
  titleInput.addEventListener('focus', event => {
    const el = event.target as HTMLInputElement;
    if (el.value === i18next.t('entity-default-title')) {
      el.value = '';
    }
  });

  titleInput.addEventListener('blur', () => {
    const content = titleInput.value;
    EntityC.update(entity.id, Target.Title, content);
    // update the page's title
    document.title = content + ' - eLabFTW';
  });

  // ANNOTATE IMAGE
  $(document).on('click', '.annotateImg',  function() {
    $('#doodleDiv').show();
    $(document).scrollTop($('#doodle-anchor').offset().top);
    const context: CanvasRenderingContext2D = (document.getElementById('doodleCanvas') as HTMLCanvasElement).getContext('2d');
    const img = new Image();
    // set src attribute to image path
    img.addEventListener('load', function() {
      // make canvas bigger than image
      context.canvas.width = (this as HTMLImageElement).width * 2;
      context.canvas.height = (this as HTMLImageElement).height * 2;
      // add image to canvas
      context.drawImage(img, (this as HTMLImageElement).width / 2, (this as HTMLImageElement).height / 2);
    });
    img.src = 'app/download.php?f=' + $(this).data('path');
  });

  // STAR RATING
  $(document).on('click', '.rating-cancel', function() {
    EntityC.update(entity.id, Target.Rating, '0');
  });

  $(document).on('click', '.star', function() {
    EntityC.update(entity.id, Target.Rating, $(this).data('rating').current[0].innerText);
  });

  // no tinymce stuff when md editor is selected
  if (editor.type === 'tiny') {
    // Object to hold control data for selected image
    const tinymceEditImage = {
      selected: false,
      uploadId: 0,
      url: '',
      reset: function(): void {
        this.selected = false;
        this.uploadId = 0;
        this.url = '';
      },
    };

    const tinyConfig = getTinymceBaseConfig('edit');

    const tinyConfigForEdit = {
      images_upload_handler: (blobInfo, success): void => { // eslint-disable-line @typescript-eslint/camelcase
        const dropZone = Dropzone.forElement('#elabftw-dropzone');
        // Edgecase for editing an image using tinymce ImageTools
        // Check if it was selected. This is set by an event hook below
        if (tinymceEditImage.selected == true && confirm(i18next.t('replace-edited-file'))) {
          // Replace the file on the server
          const formData = new FormData();
          formData.append('action', 'update');
          formData.append('target', 'file');
          formData.append('replace', 'true');
          formData.append('id', String(tinymceEditImage.uploadId));
          formData.append('entity_id', String(entity.id));
          formData.append('entity_type', entity.type);
          formData.append('model', 'upload');
          formData.append('csrf', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
          formData.append('content', blobInfo.blob());

          $.post({
            url: 'app/controllers/RequestHandler.php',
            data: formData,
            processData: false,
            contentType: false,
          }).done(function() {
            // Send the same url we stored before the edit menu was clicked to tinymce
            success(tinymceEditImage.url);
            tinymceEditImage.reset();
          });
        // If the blob has no filename, ask for one. (Firefox edgecase: Embedded image in Data URL)
        } else if (typeof blobInfo.blob().name === 'undefined') {
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
      // use undocumented callback function to asynchronously get the templates
      // see https://github.com/tinymce/tinymce/issues/5637#issuecomment-624982699
      templates: (callback): void => {
        const payload: Payload = {
          method: Method.GET,
          action: Action.Read,
          model: EntityType.Template,
          entity: {
            type: EntityType.Template,
            id: null,
          },
          target: Target.List,
        };
        (new Ajax()).send(payload).then(json => callback(json.value));
      },
      // use a custom function for the save button in toolbar
      save_onsavecallback: (): void => quickSave(), // eslint-disable-line @typescript-eslint/camelcase
    };

    tinymce.init(Object.assign(tinyConfig, tinyConfigForEdit));
    // Hook into the SelectionChange event - This is to make sure we reset our control variable correctly
    tinymce.activeEditor.on('SelectionChange', () => {
      // Check if the user has selected an image
      if (tinymce.activeEditor.selection.getNode().tagName === 'IMG') {
        // Save all the details needed for replacing upload
        // Then check for and get those details when you are handling file uploads
        const selectedImage = (tinymce.activeEditor.selection.getNode() as HTMLImageElement);
        // the uploadid is added as a data-uploadid attribute when inserted in the text
        // this allows us to know which corresponding upload is selected so we can replace it if needed (after a crop for instance)
        const uploadId = parseInt(selectedImage.dataset.uploadid);
        let url = selectedImage.src;
        url = url.slice(url.lastIndexOf('app/'));
        // Sometimes tinymce adds an identifier on modification
        // This checks for and removes it
        if (url.lastIndexOf('&') != -1){
          url = url.slice(0, url.lastIndexOf('&'));
        }
        tinymceEditImage.selected = true;
        tinymceEditImage.uploadId = uploadId;
        tinymceEditImage.url = url;
      } else {
        tinymceEditImage.reset();
      }
    });
  }


  // INSERT IMAGE AT CURSOR POSITION IN TEXT
  $(document).on('click', '.inserter',  function() {
    // link to the image
    const url = 'app/download.php?f=' + $(this).data('link');
    // switch for markdown or tinymce editor
    let content;
    if (editor.type === 'md') {
      content = '\n![image](' + url + ')\n';
    } else if (editor.type === 'tiny') {
      content = '<img src="' + url + '" data-uploadid="' + $(this).data('uploadid') + '" />';
    }
    editor.setContent(content);
  });

  $(document).on('blur', '#date_input', function() {
    const content = (document.getElementById('date_input') as HTMLInputElement).value;
    EntityC.update(entity.id, Target.Date, content);
  });
});
