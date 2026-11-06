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
import { getNewIdFromPostRequest, reloadElements, updateEntityBody } from './misc';

// clipboard images have no meaningful name (e.g. "image.png" or tinymce's "mceclip0"), so build one
export function getPastedImageName(mime: string): string {
  const ext = (mime.split('/')[1] || 'png').replace('jpeg', 'jpg').replace(/\+xml$/, '');
  const now = new Date();
  const pad = (n: number): string => String(n).padStart(2, '0');
  const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
  return `pasted-image-${stamp}.${ext}`;
}

// upload a file as attachment of the current entity and resolve with the url to use in the body
export async function uploadFileToEntity(file: Blob, filename: string): Promise<string> {
  const formData = new FormData();
  formData.set('file', file, filename);
  const resp = await ApiC.post(`${entity.type}/${entity.id}/${Model.Upload}`, formData);
  const upload = await ApiC.getJson(`${entity.type}/${entity.id}/${Model.Upload}/${getNewIdFromPostRequest(resp)}`);
  reloadElements(['uploadsDiv']);
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
      // insert a unique placeholder at cursor so user can keep typing while the upload happens
      const placeholder = `![Uploading ${filename}…]()`;
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      textarea.setRangeText(`\n${placeholder}\n`, start, end, 'end');
      textarea.dispatchEvent(new Event('input', { bubbles: true }));

      uploadFileToEntity(image, filename).then(url => {
        textarea.value = textarea.value.replace(placeholder, `![${filename}](${url})`);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        // save so the upload is referenced by the body and not considered orphan
        updateEntityBody();
      }).catch(() => {
        textarea.value = textarea.value.replace(`\n${placeholder}\n`, '');
      });
    });
  });
}
