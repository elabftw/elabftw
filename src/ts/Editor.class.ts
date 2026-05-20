/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import { marked } from 'marked';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
import { Entity, Target } from './interfaces';
import { ApiC } from './api';
declare const MathJax: MathJaxObject;

const DISPLAY_MATH_REGEXES = [
  /(^|\r\n|\r|\n)([ \t]*\$\$[ \t]*(?:\r\n|\r|\n)[\s\S]*?(?:\r\n|\r|\n)[ \t]*\$\$[ \t]*(?=\r\n|\r|\n|$))/g,
  /(^|\r\n|\r|\n)([ \t]*\\\[[ \t]*(?:\r\n|\r|\n)[\s\S]*?(?:\r\n|\r|\n)[ \t]*\\\][ \t]*(?=\r\n|\r|\n|$))/g,
];
const MATH_BLOCK_PLACEHOLDER = 'ELABFTW_MATH_BLOCK_';

interface EditorInterface {
  type: string;
  typeAsInt: number;
  init(page: string): void;
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
    return ApiC.patch(`${entity.type}/${entity.id}`, params);
  }
}

interface ProtectedMath {
  markdown: string;
  mathBlocks: Record<string, string>;
}

function protectDisplayMathBlocks(markdown: string): ProtectedMath {
  const mathBlocks: Record<string, string> = {};
  let index = 0;
  let protectedMarkdown = markdown;
  DISPLAY_MATH_REGEXES.forEach(displayMathRegex => {
    protectedMarkdown = protectedMarkdown.replace(displayMathRegex, (_match, lineBreak, math) => {
      const placeholder = `${MATH_BLOCK_PLACEHOLDER}${index}__`;
      mathBlocks[placeholder] = escapeMathForHtml(math);
      index++;
      return lineBreak + placeholder;
    });
  });

  return {
    markdown: protectedMarkdown,
    mathBlocks,
  };
}

function restoreDisplayMathBlocks(html: string, mathBlocks: Record<string, string>): string {
  Object.entries(mathBlocks).forEach(([placeholder, math]) => {
    html = html.split(placeholder).join(math);
  });
  return html;
}

function escapeMathForHtml(math: string): string {
  return math
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

class TinyEditor extends Editor implements EditorInterface {
  constructor() {
    super();
    this.type = 'tiny';
    this.typeAsInt = 1;
  }
  init(page: string = 'edit'): void {
    tinymce.init(getTinymceBaseConfig(page));
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
        const protectedMath = protectDisplayMathBlocks(ed.$textarea.val() as string);
        return restoreDisplayMathBlocks(marked(protectedMath.markdown) as string, protectedMath.mathBlocks);
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
