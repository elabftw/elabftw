/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import type { Upload } from './interfaces';
import { ApiC } from './api';
import { hideModal, onModalEvent, showModal } from './common';
import { BusyState, showFormError } from './form-state';

// the api returns the id and storage, which the shared Upload interface does not declare
export type StoredUpload = Upload & { id: number; storage: number };

export interface AttachmentLoader {
  // the extensions the editor can read, lowercase
  extensions: string[];
  // replace the content of the editor with the attachment: false keeps the modal open (for instance the user cancelled
  // a confirmation), and an error is shown in the modal
  load: (upload: StoredUpload) => Promise<boolean | void>;
}

export function isCompatible(name: string, extensions: string[]): boolean {
  return extensions.some(extension => name.toLowerCase().endsWith('.' + extension));
}

const modalSelector = '#loadAttachmentModal';
const state = new BusyState();
// what the modal was opened for, and the attachments it lists
let current: AttachmentLoader | null = null;
let compatible: StoredUpload[] = [];

/**
 * One modal to pick an attachment of the entry for all the editors of the edit page (chemical editor, image editor),
 * see load-attachment-modal.html: each opens it with what it can read, and what to do with the attachment picked
 */
export async function showLoadAttachmentModal(loader: AttachmentLoader): Promise<void> {
  const form = document.getElementById('loadAttachmentForm') as HTMLFormElement;
  const select = form.elements.namedItem('attachment') as HTMLSelectElement;
  const submit = form.querySelector<HTMLButtonElement>('[type="submit"]');
  const loading = document.getElementById('loadAttachmentLoading');
  const empty = document.getElementById('loadAttachmentEmpty');
  const error = document.getElementById('loadAttachmentError');
  if (state.busy) {
    return;
  }
  init(form, select, error);
  current = loader;
  compatible = [];
  error.hidden = true;
  empty.hidden = true;
  loading.hidden = false;
  select.replaceChildren();
  select.disabled = true;
  submit.disabled = true;
  showModal(modalSelector);
  try {
    const uploads = await ApiC.getJson<StoredUpload[]>(form.dataset.endpoint);
    compatible = uploads.filter(upload => isCompatible(upload.real_name, loader.extensions));
    select.replaceChildren(...compatible.map(upload => new Option(upload.real_name, String(upload.id))));
    empty.hidden = compatible.length > 0;
  } catch (err) {
    showFormError(error, err);
  } finally {
    loading.hidden = true;
    select.disabled = select.options.length === 0;
    submit.disabled = select.disabled;
  }
}

// the listeners of the form, added once
function init(form: HTMLFormElement, select: HTMLSelectElement, error: HTMLElement): void {
  if (form.dataset.initialized) {
    return;
  }
  form.dataset.initialized = '1';
  // the modal cannot be dismissed while an attachment is loading
  onModalEvent(modalSelector, 'hide', event => {
    if (state.busy) {
      event.preventDefault();
    }
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const upload = compatible.find(candidate => String(candidate.id) === select.value);
    if (!upload || !current) {
      return;
    }
    error.hidden = true;
    const loader = current;
    if (await state.run(form, () => loader.load(upload), err => showFormError(error, err))) {
      hideModal(modalSelector);
    }
  });
}
