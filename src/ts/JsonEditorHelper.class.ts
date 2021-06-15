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
import { notif } from './misc';
import { Entity } from './interfaces';

// This class is named helper because the jsoneditor lib already exports JSONEditor
export default class JsonEditorHelper {
  type: string;
  id: string;
  editorDiv: HTMLElement;
  MetadataC: Metadata;
  editor: JSONEditor;
  currentUploadId: string;

  constructor(entity: Entity) {
    // this is the div that will hold the editor
    this.editorDiv = document.getElementById('jsonEditorContainer');
    this.MetadataC = new Metadata(entity);
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
          this.editorDiv.style.height = '500px';
        }
      }
    };

    // instanciate the editor
    this.editor = new JSONEditor(this.editorDiv, options);
    // default mode is tree if editable
    if (editable) {
      this.editor.setMode('tree');
    }
  }

  load(json: Record<string, any>): void {
    // show the editor (use jQuery selector here for collapse())
    ($('#jsonEditorDiv') as any).collapse('show');
    // toggle the +/- button
    const plusMinusButton = document.querySelector('.jsonEditorPlusMinusButton') as HTMLButtonElement;
    if (plusMinusButton.innerText === '+') {
      plusMinusButton.innerText = '-';
      plusMinusButton.classList.add('btn-neutral');
      plusMinusButton.classList.remove('btn-primary');
    }
    // load the json content into the editor
    this.editor.set(json);
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
      .then(json => this.load(json))
      .catch(e => {
        if (e instanceof SyntaxError) {
          notif({ 'res': false, 'msg': i18next.t('json-parse-error') });
        } else {
          notif({'res': false, 'msg':'JSON Editor: ' + e.message});
        }
      });
    // add the filename as a title
    document.getElementById('jsonEditorTitle').innerText = `${i18next.t('filename')}: ${name}`;
    this.currentUploadId = uploadid;
    this.editorDiv.dataset.what = 'file';
  }

  loadMetadata(): void {
    // set the title
    document.getElementById('jsonEditorTitle').innerText = i18next.t('editing-metadata');
    this.MetadataC.read().then(metadata => this.load(metadata));
    this.editorDiv.dataset.what = 'metadata';
  }

  loadMetadataFromId(entity: Entity): void {
    const MetadataC = new Metadata(entity);
    MetadataC.read().then(metadata => this.load(metadata));
    this.editorDiv.dataset.what = 'metadata';
  }

  saveMetadata(): void {
    try {
      const metadata = this.editor.get();
      this.MetadataC.update(JSON.stringify(metadata));
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
    } else if (this.editorDiv.dataset.what === 'metadata') {
      return this.saveMetadata();
    }
  }

  saveFile(): void {
    if (typeof this.currentUploadId === 'undefined') {
      // we are creating a new file
      let realName = prompt(i18next.t('request-filename'));
      if (realName === null) {
        return;
      }
      // strip the filename of the .json extension from the name if available
      if (realName.slice(-5).includes('.json')) {
        realName = realName.slice(0, -5);
      }
      // add the new name for the file as a title
      $('#jsonEditorTitle').html(i18next.t('filename') + ': ' + realName + '.json');
      $.post('app/controllers/EntityAjaxController.php', {
        addFromString: true,
        type: this.type,
        id: this.id,
        realName: realName,
        fileType: 'json',
        string: JSON.stringify(this.editor.get())
      }).done(function(json) {
        $('#filesdiv').load(window.location.href + ' #filesdiv > *');
        this.currentUploadId = String(json.uploadId);
        notif(json);
      });
    } else {
      // we are editing an existing file
      const formData = new FormData();
      const blob = new Blob([JSON.stringify(this.editor.get())], { type: 'application/json' });
      formData.append('replace', 'true');
      formData.append('upload_id', this.currentUploadId);
      formData.append('id', this.id);
      formData.append('type', this.type);
      formData.append('file', blob);

      $.post({
        url: 'app/controllers/EntityAjaxController.php',
        data: formData,
        processData: false,
        contentType: false,
        success: (json) => {
          notif(json);
        }
      });
    }
    // Add support for 'Save as' by resetting the currentUploadId to undefined
    this.currentUploadId = undefined;
  }

  clear(): void {
    this.currentUploadId = undefined;
    this.editor.set({});
  }
}
