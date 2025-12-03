/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  escapeRegExp,
  getNewIdFromPostRequest,
  reloadElements,
  updateEntityBody,
} from './misc';
import { Target, Model, Action } from './interfaces';
import './doodle';
import { getEditor } from './Editor.class';
import $ from 'jquery';
import { ApiC } from './api';
import { Uploader } from './uploader';
import { clearLocalStorage } from './localStorage';
import { entity } from './getEntity';
import { on } from './handlers';

const mode = new URLSearchParams(window.location.search).get('mode');
if (mode === 'edit') {
  // remove exclusive edit mode when leaving the page
  window.onbeforeunload = function() {
    ApiC.notifOnSaved = false;
    ApiC.keepalive = true;
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
      clearLocalStorage();
      document.getElementById('recoveryDiv').remove();
    });
  });

  // RECOVER NO
  $(document).on('click', '.recover-no', function() {
    clearLocalStorage();
    document.getElementById('recoveryDiv').remove();
  });

  // END DATA RECOVERY
  ////////////////////

  on('get-next-custom-id', (el: HTMLElement) => {
    const inputEl = document.getElementById('custom_id_input') as HTMLInputElement;
    inputEl.classList.remove('is-invalid');
    // lock the button
    const button = el as HTMLButtonElement;
    button.disabled = true;
    ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.SetNextCustomId}).then(res => res.json()).then(json => {
      inputEl.value = String(json.custom_id);
    }).finally(() => button.disabled = false);
  });

  on('annotate-image', (el: HTMLElement) => {
    // show doodle canvas
    const doodleDiv = document.getElementById('doodleDiv');
    doodleDiv.removeAttribute('hidden');
    doodleDiv.scrollIntoView({behavior: 'smooth'});
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
  });

  on('import-link-body', (el: HTMLElement) => {
    // this is in this file and not in steps-links-edit because here `editor`
    // exists and is reachable
    ApiC.getJson(`${el.dataset.endpoint}/${el.dataset.target}`).then(json => {
      editor.setContent(json.body);
    });
  });
  on('import-step-body', (el: HTMLElement) => {
    ApiC.getJson(`${entity.type}/${entity.id}/${Model.Step}/${el.dataset.stepid}`).then(json => {
      let content = `<a href='?mode=view&id=${entity.id}&highlightstep=${el.dataset.stepid}#step_view_${el.dataset.stepid}'>${json.body}</a>`;
      // markdown
      if (editor.type === 'md') {
        content = `[${json.body}](?mode=view&id=${entity.id}&highlightstep=${el.dataset.stepid}#step_view_${el.dataset.stepid})`;
      }
      return editor.setContent(content);
    });
  });
  on('insert-image-in-body', (el: HTMLElement) => {
    // link to the image file
    const url = `app/download.php?name=${el.dataset.name}&f=${el.dataset.link}&storage=${el.dataset.storage}`;
    // switch for markdown or tinymce editor
    let content: string;
    if (editor.type === 'md') {
      content = '\n![image](' + url + ')\n';
    } else if (editor.type === 'tiny') {
      content = '<img src="' + url + '" />';
    }
    editor.setContent(content);
  });
  on('insert-video-in-body', (el: HTMLElement) => {
    // link to the video file
    const url = `app/download.php?name=${encodeURIComponent(el.dataset.name)}&f=${encodeURIComponent(el.dataset.link)}&storage=${encodeURIComponent(el.dataset.storage)}`;
    // no syntax for video in markdown; use plain html in both cases
    const video = document.createElement('video');
    const source = document.createElement('source');
    source.src = url;
    video.width = 640;
    video.controls = true;
    video.appendChild(source);
    editor.setContent(video.outerHTML);
  });
  on('insert-audio-in-body', (el: HTMLElement) => {
    // link to the audio file
    const url = `app/download.php?name=${encodeURIComponent(el.dataset.name)}&f=${encodeURIComponent(el.dataset.link)}&storage=${encodeURIComponent(el.dataset.storage)}`;
    // no syntax for audio in markdown; use plain html in both cases
    const audio = document.createElement('audio');
    audio.src = url;
    audio.controls = true;
    editor.setContent(audio.outerHTML);
  });

  on('insert-plain-text', (el: HTMLElement) => {
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
}
