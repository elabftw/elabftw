/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * TinyMCE-side wiring for the inline spreadsheet feature.
 *
 * Registered from tinymce.ts via registerInlineSpreadsheet(editor) on the edit page only.
 * The modal, attachment IO and snapshot building live in inline-spreadsheet.ts (a separate
 * bundle); here we only dispatch intents (insert/edit) and apply the rendered block to the body.
 */
import { Editor } from 'tinymce/tinymce';
import { updateEntityBody } from './misc';

// table grid icon used by both the toolbar and the context-toolbar buttons
const INLINE_SHEET_ICON = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="16" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 9h18M3 14h18M9 4v16M15 4v16" fill="none" stroke="currentColor" stroke-width="2"/></svg>';

export function registerInlineSpreadsheet(editor: Editor): void {
  editor.ui.registry.addIcon('inlineSheet', INLINE_SHEET_ICON);

  const dispatchEdit = (block: Element | null): void => {
    if (!block) {
      return;
    }
    const el = block as HTMLElement;
    document.dispatchEvent(new CustomEvent('inline-sheet-edit', {
      detail: { uploadId: Number(el.dataset.uploadId), sheetName: el.dataset.sheetName },
    }));
  };

  // toolbar button: open the insert dialog
  editor.ui.registry.addButton('inline-sheet', {
    icon: 'inlineSheet',
    tooltip: 'Insert spreadsheet',
    onAction: () => document.dispatchEvent(new CustomEvent('inline-sheet-insert')),
  });
  // context-toolbar button shown when a snapshot block is selected
  editor.ui.registry.addButton('inlinesheetedit', {
    icon: 'inlineSheet',
    text: 'Edit spreadsheet',
    onAction: () => dispatchEdit(editor.selection.getNode().closest('.elabftw-inline-sheet')),
  });
  editor.ui.registry.addContextToolbar('inlinesheet', {
    predicate: node => Boolean(node.classList && node.classList.contains('elabftw-inline-sheet')),
    items: 'inlinesheetedit',
    position: 'node',
    scope: 'node',
  });
  // double-click a snapshot block to edit it in the standalone editor
  editor.on('dblclick', event => {
    const target = event.target as HTMLElement;
    dispatchEdit(target.closest ? target.closest('.elabftw-inline-sheet') : null);
  });

  // apply a rendered snapshot to the body. On an explicit embed (insertIfMissing) always insert a
  // NEW block so the same upload can be embedded in several places; on a save-driven refresh, update
  // every existing copy of that upload so they stay in sync.
  const onInlineRender = (event: Event): void => {
    const detail = (event as CustomEvent).detail || {};
    const html = detail.html as string;
    const sheetName = detail.sheetName as string;
    const uploadId = detail.uploadId ? String(detail.uploadId) : '';
    const previousUploadId = detail.previousUploadId ? String(detail.previousUploadId) : uploadId;
    if (!html || !sheetName || !uploadId) {
      return;
    }
    const matching = (Array.from(editor.getBody().querySelectorAll('.elabftw-inline-sheet')) as HTMLElement[])
      .filter(block => block.dataset.uploadId === previousUploadId);
    if (detail.insertIfMissing) {
      // never nest a snapshot inside another snapshot: if the caret sits inside an existing
      // block, move it just after that block so the new one is inserted as a top-level sibling
      const node = editor.selection.getNode();
      const containing = node?.closest?.('.elabftw-inline-sheet');
      if (containing) {
        editor.selection.select(containing);
        editor.selection.collapse(false);
      }
      editor.insertContent(html);
    } else if (matching.length) {
      matching.forEach(block => {
        block.outerHTML = html;
      });
    } else {
      // nothing embedded for this file (e.g. a plain standalone-editor save): don't dirty the body
      return;
    }
    editor.undoManager.add();
    void updateEntityBody();
  };
  document.addEventListener('inline-sheet-render', onInlineRender);
  editor.on('remove', () => document.removeEventListener('inline-sheet-render', onInlineRender));

  // when an attachment is renamed, update every embedded snapshot of it (matched by upload id,
  // which is stable across a rename): retitle it and re-key it by the new filename
  const onInlineRename = (event: Event): void => {
    const detail = (event as CustomEvent).detail || {};
    const uploadId = detail.uploadId ? String(detail.uploadId) : '';
    const newName = detail.newName as string;
    if (!uploadId || !newName) {
      return;
    }
    const blocks = (Array.from(editor.getBody().querySelectorAll('.elabftw-inline-sheet')) as HTMLElement[])
      .filter(block => block.dataset.uploadId === uploadId);
    if (!blocks.length) {
      return;
    }
    blocks.forEach(block => {
      block.dataset.sheetName = newName;
      // .elabftw-inline-sheet-title for new blocks, caption for legacy ones
      const title = block.querySelector('.elabftw-inline-sheet-title, caption');
      if (title) {
        title.textContent = newName;
      }
    });
    editor.undoManager.add();
    void updateEntityBody();
  };
  document.addEventListener('inline-sheet-rename', onInlineRename);
  editor.on('remove', () => document.removeEventListener('inline-sheet-rename', onInlineRename));
}
