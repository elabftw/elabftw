/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import tinymce from 'tinymce/tinymce';
import { marked } from 'marked';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
import { Entity, Target } from './interfaces';
import {Api} from './Apiv2.class';
declare const MathJax: MathJaxObject;

interface EditorInterface {
  type: string;
  typeAsInt: number;
  init(): void;
  getContent(): string;
  setContent(content: string): void;
  switch(entity: Entity): Promise<Response>;
  replaceContent(content: string): void;
}

class Editor {
  type: string;
  typeAsInt: number;
  switch(entity: Entity): Promise<Response> {
    const params = {};
    params[Target.ContentType] = this.type === 'tiny' ? 2 : 1;
    return (new Api()).patch(`${entity.type}/${entity.id}`, params);
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
    tinymce.get(0).insertContent(content);
  }
  replaceContent(content: string): void {
    tinymce.get(0).setContent(content);
  }
}

export class MdEditor extends Editor implements EditorInterface {
  constructor() {
    super();
    this.type = 'md';
    this.typeAsInt = 2;
  }
  init(): void {
    /* eslint-disable-next-line */
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
  if (document.getElementById('entityBodyEditorDiv')) {
    return document.getElementById('entityBodyEditorDiv').dataset.contentType === '2' ? new MdEditor() : new TinyEditor();
  }
  return new TinyEditor();
}
