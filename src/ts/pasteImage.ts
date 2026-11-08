/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { ApiC } from './api';
import { Model } from './interfaces';
import { entity } from './getEntity';
import { adjustHiddenState, getNewIdFromPostRequest, reloadElements, updateEntityBody } from './misc';

// blobs can have an empty type, and "image/svg+xml" must give "svg"
export function getExtFromMime(mime: string): string {
  return (mime.split('/')[1] || 'png').replace('jpeg', 'jpg').replace(/\+xml$/, '');
}

// clipboard images have no meaningful name (e.g. "image.png" or tinymce's "mceclip0"), so build one
// the random suffix keeps names unique when several images are pasted within the same second,
// as the name is also used for the markdown placeholder
export function getPastedImageName(mime: string): string {
  const now = new Date();
  const pad = (n: number): string => String(n).padStart(2, '0');
  const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
  const suffix = crypto.randomUUID().slice(0, 8);
  return `pasted-image-${stamp}-${suffix}.${getExtFromMime(mime)}`;
}

// upload a file as attachment of the current entity and resolve with the url to use in the body
// notifOnError: set to false when the caller reports the error itself, to avoid showing it twice
export async function uploadFileToEntity(file: Blob, filename: string, notifOnError = true): Promise<string> {
  const formData = new FormData();
  formData.set('file', file, filename);
  if (!notifOnError) {
    formData.set('notifOnError', '0');
  }
  const errorParams = notifOnError ? {} : { notifOnError: 0 };
  const resp = await ApiC.post(`${entity.type}/${entity.id}/${Model.Upload}`, formData);
  const upload = await ApiC.getJson(`${entity.type}/${entity.id}/${Model.Upload}/${getNewIdFromPostRequest(resp)}`, errorParams);
  // restore the collapsed state of upload groups, like the dropzone does
  reloadElements(['uploadsDiv']).then(() => adjustHiddenState());
  return `app/download.php?f=${encodeURIComponent(upload.long_name)}&storage=${encodeURIComponent(upload.storage)}`;
}

// office suites put a rendering of the copied cells/text as image next to the text: the text is what the user wants
// but "Copy Image" in a browser gives an html <img> pointing to the remote url (blocked by CSP) and/or that url as text: the image data is what we want
// only look at text/plain: parsing the clipboard html would feed untrusted markup to an html parser (XSS sink),
// and every source that puts html with real text in the clipboard also puts that text as text/plain
function hasRealText(data: DataTransfer): boolean {
  const text = data.getData('text/plain').trim();
  return text !== '' && !/^(https?|file|data):\S*$/.test(text);
}

export function getClipboardImages(data: DataTransfer | null): File[] {
  if (!data) {
    return [];
  }
  const images = Array.from(data.items)
    .filter(item => item.kind === 'file' && item.type.startsWith('image/'))
    .map(item => item.getAsFile())
    .filter((file): file is File => file !== null);
  if (images.length === 0 || hasRealText(data)) {
    return [];
  }
  return images;
}

// the user may have kept typing during the upload: edit in place so the cursor position is kept
function replacePlaceholder(textarea: HTMLTextAreaElement, placeholder: string, replacement: string): void {
  const start = textarea.value.indexOf(placeholder);
  if (start === -1) {
    // user removed it
    return;
  }
  textarea.setRangeText(replacement, start, start + placeholder.length, 'preserve');
  textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

export function enableMarkdownImagePaste(textarea: HTMLTextAreaElement): void {
  textarea.addEventListener('paste', (event: ClipboardEvent) => {
    const images = getClipboardImages(event.clipboardData);
    if (images.length === 0) {
      // let regular text paste happen
      return;
    }
    event.preventDefault();
    images.forEach(image => {
      const filename = getPastedImageName(image.type);
      // insert a placeholder at cursor so user can keep typing while the upload happens
      // it is unique thanks to the random part of the filename
      const placeholder = `![Uploading ${filename}…]()`;
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      textarea.setRangeText(`\n${placeholder}\n`, start, end, 'end');
      textarea.dispatchEvent(new Event('input', { bubbles: true }));

      uploadFileToEntity(image, filename).then(url => {
        replacePlaceholder(textarea, placeholder, `![${filename}](${url})`);
        // save so the upload is referenced by the body and not considered orphan
        updateEntityBody();
      }).catch(() => {
        // only remove the placeholder itself: the surrounding newlines may have been edited by the user meanwhile
        replacePlaceholder(textarea, placeholder, '');
      });
    });
  });
}
