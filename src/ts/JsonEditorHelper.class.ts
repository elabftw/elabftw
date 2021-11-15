/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Metadata } from './Metadata.class';
import JSONEditor from 'jsoneditor';
import i18next from 'i18next';
import { notif, reloadElement } from './misc';
import { Entity } from './interfaces';

// This class is named helper because the jsoneditor lib already exports JSONEditor
export default class JsonEditorHelper {
  entity: Entity;
  editorDiv: HTMLDivElement;
  MetadataC: Metadata;
  editor: JSONEditor;
  currentUploadId: string;
  editorTitle: HTMLElement;

  constructor(entity: Entity) {
    this.entity = entity;
    // this is the div that will hold the editor
    this.editorDiv = document.getElementById('jsonEditorContainer') as HTMLDivElement;
    this.MetadataC = new Metadata(entity);
    this.editorTitle = document.getElementById('jsonEditorTitle');
  }

  // INIT
  init(editable = false): void {
    // JSONEditor has several modes, in edit mode we want more modes than in view mode
    let modes = ['view'];
    if (editable) {
      modes = modes.concat(['tree', 'code', 'form', 'text']);
    }
    const options = {
      modes: modes,
      onModeChange: (newMode): void => {
        if (newMode === 'code' || newMode === 'text') {
          (this.editorDiv.firstChild as HTMLDivElement).style.height = '500px';
        }
      },
    };

    // instanciate the editor
    this.editor = new JSONEditor(this.editorDiv, options);
    // default mode is tree if editable
    if (editable) {
      this.editor.setMode('tree');
    }
    if (this.editorDiv.dataset.preloadJson === '1') {
      this.loadMetadata();
    }
  }

  focus(): void {
    // show the editor (use jQuery selector here for collapse())
    ($('#jsonEditorDiv') as JQuery<HTMLDivElement>).collapse('show');
    // toggle the +/- button
    const plusMinusButton = document.querySelector('.jsonEditorPlusMinusButton') as HTMLButtonElement;
    if (plusMinusButton.innerText === '+') {
      plusMinusButton.innerText = '-';
      plusMinusButton.classList.add('btn-neutral');
      plusMinusButton.classList.remove('btn-primary');
    }
    // and scroll page into editor view
    document.getElementById('jsonEditorContainer').scrollIntoView();
  }

  loadFile(link: string, name: string, uploadid: string): void {
    const headers = new Headers();
    headers.append('cache-control', 'no-cache');
    fetch(`app/download.php?f=${link}`, { headers: headers })
      .then(response => {
        if (!response.ok) {
          throw new Error('An unexpected error occured!');
        }
        return response.json();
      })
      .then(json => {
        this.editor.set(json);
        this.focus();
      })
      .catch(e => {
        if (e instanceof SyntaxError) {
          notif({ 'res': false, 'msg': i18next.t('json-parse-error') });
        } else {
          notif({'res': false, 'msg':'JSON Editor: ' + e.message});
        }
      });
    // add the filename as a title
    this.editorTitle.innerText = `${i18next.t('filename')}: ${name}`;
    this.currentUploadId = uploadid;
    this.editorDiv.dataset.what = 'file';
  }

  loadMetadata(): void {
    // set the title
    this.editorTitle.innerText = i18next.t('editing-metadata');
    this.MetadataC.read().then(metadata => this.editor.set(metadata));
    this.editorDiv.dataset.what = 'metadata';
  }

  loadMetadataFromId(entity: Entity): void {
    const MetadataC = new Metadata(entity);
    MetadataC.read().then(metadata => {
      this.editor.set(metadata);
      this.focus();
    });
    this.editorDiv.dataset.what = 'metadata';
  }

  saveMetadata(): void {
    try {
      this.MetadataC.update(JSON.stringify(this.editor.get()));
    } catch (error) {
      notif({res: false, msg: 'Error parsing the JSON! Error logged in console.'});
      console.error(error);
    }
  }

  saveMetadataFromId(entity: Entity): void {
    const MetadataC = new Metadata(entity);
    MetadataC.update(JSON.stringify(this.editor.get()));
  }

  // save a file or metadata depending on what was loaded
  save(): void {
    if (this.editorDiv.dataset.what === 'file') {
      return this.saveFile();
    }

    if (this.editorDiv.dataset.what === 'metadata') {
      return this.saveMetadata();
    }

    // toggle save menu so user can select what to save: file or metadata
    if (this.editorDiv.dataset.what === '') {
      if ($('#json-save-dropdown').next('div').is(':hidden')) {
        $('#json-save-dropdown').dropdown('toggle');
      }
    }
  }

  // create a new file
  saveNewFile(): void {
    let realName = prompt(i18next.t('request-filename'));
    if (realName === null) {
      return;
    }
    // strip the filename of the .json extension from the name if available
    if (realName.slice(-5).includes('.json')) {
      realName = realName.slice(0, -5);
    }
    // add the new name for the file as a title
    this.editorTitle.innerText = i18next.t('filename') + ': ' + realName + '.json';
    $.post('app/controllers/EntityAjaxController.php', {
      addFromString: true,
      type: this.entity.type,
      id: this.entity.id,
      realName: realName,
      fileType: 'json',
      string: JSON.stringify(this.editor.get()),
    }).done(json => {
      reloadElement('filesdiv');
      this.currentUploadId = String(json.uploadId);
      notif(json);
    });
  }

  // edit an existing file
  saveFile(): void {
    const formData = new FormData();
    const blob = new Blob([JSON.stringify(this.editor.get())], { type: 'application/json' });
    formData.append('action', 'update');
    formData.append('target', 'file');
    formData.append('entity_id', this.entity.id.toString());
    formData.append('entity_type', this.entity.type);
    formData.append('id', this.currentUploadId);
    formData.append('model', 'upload');
    formData.append('csrf', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('content', blob);
    formData.append('extraParam', 'jsoneditor');

    $.post({
      url: 'app/controllers/RequestHandler.php',
      data: formData,
      processData: false,
      contentType: false,
      success: (json) => {
        notif(json);
      },
    });
  }

  clear(): void {
    this.editorTitle.innerText = '';
    this.currentUploadId = undefined;
    this.editor.set({});
    this.editorDiv.dataset.what = '';
  }
}
