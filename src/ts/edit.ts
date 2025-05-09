/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  escapeRegExp,
  getEntity,
  getNewIdFromPostRequest,
  notifError,
  reloadElements,
  updateCatStat,
  updateEntityBody,
} from './misc';
import { Target, Model, Action } from './interfaces';
import './doodle';
import { getEditor } from './Editor.class';
import $ from 'jquery';
import i18next from 'i18next';
import { Api } from './Apiv2.class';
import { Uploader } from './uploader';

document.addEventListener('DOMContentLoaded', async () => {
  // only run in edit mode
  if (document.getElementById('info')?.dataset.page !== 'edit') {
    return;
  }

  const ApiC = new Api();

  const entity = getEntity();

  // remove exclusive edit mode when leaving the page
  window.onbeforeunload = function() {
    ApiC.notifOnSaved = false;
    ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.RemoveExclusiveEditMode});
  };
  // Which editor are we using? md or tiny
  const editor = getEditor();
  editor.init('edit');
  // initialize the file uploader
  (new Uploader()).init();

  ////////////////
  // DATA RECOVERY

  // check if there is some local data with this id to recover
  if ((localStorage.getItem('id') == String(entity.id)) && (localStorage.getItem('type') == entity.type)) {
    const bodyRecovery = $('<div></div>', {
      class : 'alert alert-warning',
      id: 'recoveryDiv',
      html: 'Recovery data found (saved on ' + localStorage.getItem('date') + '). It was probably saved because your session timed out and it could not be saved in the database. Do you want to recover it?<br><button type="button" class="btn btn-primary recover-yes">YES</button> <button type="button" class="button btn btn-danger recover-no">NO</button><br><br>Here is what it looks like: ' + localStorage.getItem('body'),
    });
    $('#main_section').before(bodyRecovery);
  }

  // RECOVER YES
  $(document).on('click', '.recover-yes', function() {
    const params = {};
    params[Target.Body] = localStorage.getItem('body');
    ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => {
      editor.replaceContent(localStorage.getItem('body'));
      localStorage.clear();
      document.getElementById('recoveryDiv').remove();
    });
  });

  // RECOVER NO
  $(document).on('click', '.recover-no', function() {
    localStorage.clear();
    document.getElementById('recoveryDiv').remove();
  });

  // END DATA RECOVERY
  ////////////////////

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // GET NEXT CUSTOM ID
    if (el.matches('[data-action="get-next-custom-id"]')) {
      // fetch the category from the current value of select, as it might be different from the one on page load
      const category = (document.getElementById('category_select') as HTMLSelectElement).value;
      if (category === '0') {
        notifError(new Error(i18next.t('error-no-category')));
        return;
      }
      const inputEl = document.getElementById('custom_id_input') as HTMLInputElement;
      inputEl.classList.remove('is-invalid');
      // lock the button
      const button = el as HTMLButtonElement;
      button.disabled = true;
      // make sure the current id is null or it will increment this one
      const params = {};
      params[Target.Customid] = null;
      ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => {
        // get the entity with highest custom_id
        return ApiC.getJson(`${el.dataset.endpoint}/?cat=${category}&order=customid&limit=1&sort=desc&scope=3&skip_pinned=1`);
      }).then(json => {
        const nextId = json[0].custom_id + 1;
        inputEl.value = nextId;
        const params = {};
        params[Target.Customid] = nextId;
        return ApiC.patch(`${entity.type}/${entity.id}`, params);
      }).finally(() => {
        // unlock the button
        button.disabled = false;
      });

    // ANNOTATE IMAGE
    } else if (el.matches('[data-action="annotate-image"]')) {
      // show doodle canvas
      const doodleDiv = document.getElementById('doodleDiv');
      doodleDiv.removeAttribute('hidden');
      doodleDiv.scrollIntoView();
      // adjust caret icon
      const doodleDivIcon = document.getElementById('doodleDivIcon');
      doodleDivIcon.classList.remove('fa-caret-right');
      doodleDivIcon.classList.add('fa-caret-down');

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
      img.src = `app/download.php?storage=${el.dataset.storage}&f=${el.dataset.path}`;

    // IMPORT BODY OF LINKED ITEM INTO EDITOR
    } else if (el.matches('[data-action="import-link-body"]')) {
      // this is in this file and not in steps-links-edit because here `editor`
      // exists and is reachable
      ApiC.getJson(`${el.dataset.endpoint}/${el.dataset.target}`).then(json => {
        editor.setContent(json.body);
      });

    // IMPORT STEP INTO BODY
    } else if (el.matches('[data-action="import-step-body"]')) {
      ApiC.getJson(`${entity.type}/${entity.id}/${Model.Step}/${el.dataset.stepid}`).then(json => {
        let content = `<a href='?mode=view&id=${entity.id}&highlightstep=${el.dataset.stepid}#step_view_${el.dataset.stepid}'>${json.body}</a>`;
        // markdown
        if (editor.type === 'md') {
          content = `[${json.body}](?mode=view&id=${entity.id}&highlightstep=${el.dataset.stepid}#step_view_${el.dataset.stepid})`;
        }
        return editor.setContent(content);
      });

    // INSERT IMAGE AT CURSOR POSITION IN TEXT
    } else if (el.matches('[data-action="insert-image-in-body"]')) {
      // link to the image
      const url = `app/download.php?name=${el.dataset.name}&f=${el.dataset.link}&storage=${el.dataset.storage}`;
      // switch for markdown or tinymce editor
      let content: string;
      if (editor.type === 'md') {
        content = '\n![image](' + url + ')\n';
      } else if (editor.type === 'tiny') {
        content = '<img src="' + url + '" />';
      }
      editor.setContent(content);

    // INSERT VIDEO AT CURSOR POSITION IN TEXT
    } else if (el.matches('[data-action="insert-video-in-body"]')) {
      // link to the video
      const url = `app/download.php?name=${encodeURIComponent(el.dataset.name)}&f=${encodeURIComponent(el.dataset.link)}&storage=${encodeURIComponent(el.dataset.storage)}`;
      // no syntax for video in markdown; use plain html in both cases
      const video = document.createElement('video');
      const source = document.createElement('source');
      source.src = url;
      video.width = 640;
      video.controls = true;
      video.appendChild(source);
      editor.setContent(video.outerHTML);

    // INSERT AUDIO AT CURSOR POSITION IN TEXT
    } else if (el.matches('[data-action="insert-audio-in-body"]')) {
      // link to the video
      const url = `app/download.php?name=${encodeURIComponent(el.dataset.name)}&f=${encodeURIComponent(el.dataset.link)}&storage=${encodeURIComponent(el.dataset.storage)}`;
      // no syntax for audio in markdown; use plain html in both cases
      const audio = document.createElement('audio');
      audio.src = url;
      audio.controls = true;
      editor.setContent(audio.outerHTML);

    // ADD CONTENT OF PLAIN TEXT FILES AT CURSOR POSITION IN TEXT
    } else if (el.matches('[data-action="insert-plain-text"]')) {
      fetch(`app/download.php?storage=${el.dataset.storage}&f=${el.dataset.path}`).then(response => {
        return response.text();
      }).then(fileContent => {
        const specialChars = {
          '<': '&lt;',
          '>': '&gt;',
        };

        // wrap in pre element to retain whitespace, html encode '<' and '>'
        editor.setContent('<pre>' + fileContent.replace(/[<>]/g, char => specialChars[char]) + '</pre>');
      });
    }
  });

  // CATEGORY SELECT
  $(document).on('change', '.catstatSelect', function() {
    updateCatStat($(this).data('target'), entity, String($(this).val()));
  });

  // REPLACE UPLOADED FILE
  // this should be in uploads but there is no good way so far to interact with the two editors there
  document.getElementById('filesDiv').addEventListener('submit', event => {
    const el = event.target as HTMLElement;
    if (el.matches('[data-action="replace-uploaded-file"]')) {
      event.preventDefault();

      // we can identify an image by the src attribute in this context
      const searchPrefixSrc = 'src="app/download.php?f=';
      const searchPrefixMd = '![image](app/download.php?f=';
      const formElement = el as HTMLFormElement;
      const editorCurrentContent = editor.getContent();
      const formData = new FormData(formElement);
      // prevent the browser from redirecting us
      formData.set('extraParam', 'noRedirect');
      fetch(`api/v2/${entity.type}/${entity.id}/${Model.Upload}/${el.dataset.uploadid}`, {
        method: 'POST',
        body: formData,
      }).then(resp => {
        reloadElements(['uploadsDiv']);
        // return early if longName is not found in body
        if ((editorCurrentContent.indexOf(searchPrefixSrc + formElement.dataset.longName) === -1)
          && (editorCurrentContent.indexOf(searchPrefixMd + formElement.dataset.longName) === -1)
        ) {
          return true;
        }
        // now replace all occurrence of the old file in the body with the long_name of the new file
        const newId = getNewIdFromPostRequest(resp);
        // fetch info about the newly created upload
        return ApiC.getJson(`${entity.type}/${entity.id}/${Model.Upload}/${newId}`);
      }).then(json => {
        // use regExp in replace to find all occurrence
        // images are identified by 'src="app/download.php?f=' (html) and '![image](app/download.php?f=' (md)
        // '.', '?', '[' and '(' need to be escaped in js regex
        const editorNewContent = editorCurrentContent.replace(
          new RegExp(escapeRegExp(searchPrefixSrc + formElement.dataset.longName), 'g'),
          searchPrefixSrc + json.long_name,
        ).replace(
          new RegExp(escapeRegExp(searchPrefixMd + formElement.dataset.longName), 'g'),
          searchPrefixMd + json.long_name,
        );
        editor.replaceContent(editorNewContent);

        // status of previous file is archived now
        // save because using the old file will not return an id from the db
        updateEntityBody();
      });
    }
  });
});
