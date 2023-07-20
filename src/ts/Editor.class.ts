/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { insertParamAndReload } from './misc';
import $ from 'jquery';
import tinymce from 'tinymce/tinymce';
import { marked } from 'marked';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
declare const MathJax: MathJaxObject;

interface EditorInterface {
  type: string;
  typeAsInt: number;
  init(): void;
  getContent(): string;
  setContent(content: string): void;
  switch(): string;
  replaceContent(content: string): void;
}

class Editor {
  type: string;
  typeAsInt: number;
  switch(): string {
    const target = this.type === 'tiny' ? 'md' : 'tiny';
    insertParamAndReload('editor', target);
    return target;
  }
}

class TinyEditor extends Editor implements EditorInterface {
  constructor() {
    super();
    this.type = 'tiny';
    this.typeAsInt = 1;
  }
  init(): void {
    return;
  }
  getContent(): string {
    return tinymce.activeEditor.getContent();
  }
  setContent(content: string): void {
    tinymce.editors[0].insertContent(content);
  }
  replaceContent(content: string): void {
    tinymce.editors[0].setContent(content);
  }
}

export class MdEditor extends Editor implements EditorInterface {
  constructor() {
    super();
    this.type = 'md';
    this.typeAsInt = 2;
  }
  init(): void {
    ($('.markdown-textarea') as any).markdown({
      onPreview: ed => {
        // ask mathjax to reparse the page
        // if we call typeset directly it doesn't work
        // so add a timeout
        setTimeout(() => {
          MathJax.typeset();
        }, 1);
        // parse with marked and return the html
        return marked(ed.$textarea.val());
      },
    });
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
  replaceContent(content: string): void {
    $('#body_area').val(content);
  }
}

export function getEditor(): EditorInterface {
  if (document.getElementById('iHazEditor')) {
    return document.getElementById('iHazEditor').dataset.editor === 'md' ? new MdEditor() : new TinyEditor();
  }
  return new TinyEditor();
}
