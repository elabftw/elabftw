/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Metadata } from './Metadata.class';
import JSONEditor from 'jsoneditor';
import $ from 'jquery';
import i18next from './i18n';
import { askFileName, ensureTogglableSectionIsOpen, reloadElements } from './misc';
import { Action, Entity, FileType, Model } from './interfaces';
import { ApiC } from './api';
import { ValidMetadata } from './metadataInterfaces';
import { notify } from './notify';

// This class is named helper because the jsoneditor lib already exports JSONEditor
export default class JsonEditorHelper {
  entity: Entity;
  editorDiv: HTMLDivElement;
  MetadataC: Metadata;
  editor: JSONEditor;
  currentUploadId: string;
  currentFilename: string;
  editorTitle: HTMLElement;

  constructor(entity: Entity) {
    this.entity = entity;
    // this is the div that will hold the editor
    this.editorDiv = document.getElementById('jsonEditorContainer') as HTMLDivElement;
    this.MetadataC = new Metadata(entity, this);
    this.editorTitle = document.getElementById('jsonEditorTitle');
  }

  // INIT
  init(editable = false): void {
    // JSONEditor has several modes, in edit mode we want more modes than in view mode
    let modes = ['view'];
    if (editable) {
      modes = modes.concat(['tree', 'code', 'form']);
    }
    const options = {
      modes: modes,
      onModeChange: (newMode: string): void => {
        if (newMode === 'code') {
          (this.editorDiv.firstChild as HTMLDivElement).style.height = '500px';
        }
      },
      onChange: (): void => {
        // make the save button stand out if the content is changed
        document.querySelector('[data-action="json-save"]').classList.add('border-danger');
        document.getElementById('jsonUnsavedChangesWarningDiv').removeAttribute('hidden');
      },
    };

    // instantiate the editor
    this.editor = new JSONEditor(this.editorDiv, options);
    // default mode is tree if editable
    if (editable) {
      this.editor.setMode('tree');
    }
    if (this.editorDiv.dataset.preloadJson === '1') {
      this.loadMetadata();
    }
  }

  refresh(metadata: ValidMetadata): void {
    if (this.editor instanceof JSONEditor) {
      this.editor.update(metadata);
    }
  }

  loadFile(link: string, name: string, uploadid: string): void {
    const headers = new Headers();
    headers.append('cache-control', 'no-cache');
    fetch(`app/download.php?f=${link}`, { headers: headers })
      .then(response => {
        if (!response.ok) {
          throw new Error('An unexpected error occurred!');
        }
        return response.json();
      })
      .then(json => {
        this.editor.set(json);
        ensureTogglableSectionIsOpen('jsonEditorIcon', 'jsonEditorDiv');
      })
      .catch(e => {
        if (e instanceof SyntaxError) {
          notify.error('invalid-info');
        } else {
          notify.error(e.message);
        }
      });
    // add the filename as a title
    this.editorTitle.innerText = `${i18next.t('filename')}: ${name}`;
    this.currentUploadId = uploadid;
    this.currentFilename = name;
    this.editorDiv.dataset.what = 'file';
    document.getElementById('jsonImportFileDiv')?.toggleAttribute('hidden', true);
  }

  loadMetadata(): void {
    // set the title
    this.editorTitle.innerText = i18next.t('editing-metadata');
    // Note: metadata is read two times one for the editor, one to display, a get to the entity should ideally only be made once
    this.MetadataC.read().then(metadata => this.editor.update(metadata));
    this.editorDiv.dataset.what = 'metadata';
  }

  saveMetadata(): void {
    try {
      this.MetadataC.update(this.editor.get());
    } catch (error) {
      notify.error(error);
    }
  }

  // save a file or metadata depending on what was loaded
  save(): void {
    if (this.editorDiv.dataset.what === 'file') {
      this.saveFile();
    }

    if (this.editorDiv.dataset.what === 'metadata') {
      this.saveMetadata();
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
    const realName = askFileName(FileType.Json);
    if (!realName) return;
    // add the new name for the file as a title
    this.editorTitle.innerText = i18next.t('filename') + ': ' + realName;
    const params = {
      'action': Action.CreateFromString,
      'file_type': FileType.Json,
      'real_name': realName,
      'content': JSON.stringify(this.editor.get()),
    };
    ApiC.post2location(`${this.entity.type}/${this.entity.id}/${Model.Upload}`, params)
      .then(id => this.currentUploadId = String(id))
      .then(() => reloadElements(['uploadsDiv']));
  }

  // edit an existing file
  saveFile(): void {
    const formData = new FormData();
    formData.set('file', new Blob([JSON.stringify(this.editor.get())], { type: 'application/json' }), this.currentFilename);
    // prevent the browser from redirecting us
    formData.set('extraParam', 'noRedirect');
    // because the upload id is set this will replace the file directly
    fetch(`api/v2/${this.entity.type}/${this.entity.id}/${Model.Upload}/${this.currentUploadId}`, {
      method: 'POST',
      body: formData,
    }).then(() => reloadElements(['uploadsDiv']));
    notify.success();
  }

  toggleDisplayMainText(): void {
    let json = {};
    // get the current metadata
    this.MetadataC.read().then(metadata => {
      if (metadata) {
        json = metadata;
      }
      // add the namespace object 'elabftw' if it's not there
      if (!Object.prototype.hasOwnProperty.call(json, 'elabftw')) {
        json['elabftw'] = {};
      }
      // if it's not present, set it to false
      if (!Object.prototype.hasOwnProperty.call(json['elabftw'], 'display_main_text')) {
        json['elabftw']['display_main_text'] = false;
      } else {
        json['elabftw']['display_main_text'] = !json['elabftw']['display_main_text'];
      }
      this.editor.set(json);
      this.saveMetadata();
    });
  }

  clear(): void {
    this.editorTitle.innerText = '';
    this.currentUploadId = undefined;
    this.editor.set({});
    this.editorDiv.dataset.what = '';
    document.getElementById('jsonEditorMetadataLoadButton')?.removeAttribute('disabled');
  }
}
