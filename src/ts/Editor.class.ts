/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { insertParamAndReload } from './misc';
import tinymce from 'tinymce/tinymce';

interface EditorInterface {
  type: string;
  getContent(): string;
  setContent(content: string): void;
  switch(): void;
}

class Editor {
  type: string;
  switch(): void {
    insertParamAndReload('editor', this.type === 'tiny' ? 'md' : 'tiny');
  }
}

class TinyEditor extends Editor implements EditorInterface {
  constructor() {
    super();
    this.type = 'tiny';
  }
  getContent(): string {
    return tinymce.activeEditor.getContent();
  }
  setContent(content: string): void {
    tinymce.editors[0].insertContent(content);
  }
}

class MdEditor extends Editor implements EditorInterface {
  constructor() {
    super();
    this.type = 'md';
  }
  getContent(): string {
    return (document.getElementById('body_area') as HTMLTextAreaElement).value;
  }
  setContent(content: string): void {
    const cursorPosition = $('#body_area').prop('selectionStart');
    const oldcontent = ($('#body_area').val() as string);
    const before = oldcontent.substring(0, cursorPosition);
    const after = oldcontent.substring(cursorPosition);
    $('#body_area').val(before + content + after);
  }
}

export function getEditor(): EditorInterface {
  return document.getElementById('iHazEditor').dataset.editor === 'md' ? new MdEditor() : new TinyEditor();
}
