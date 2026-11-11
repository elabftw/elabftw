/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import type { Ketcher } from 'ketcher-core';
import type { Upload } from './interfaces';
import { ApiC } from './api';
import { loadChemDrawing } from './chem-drawing';
import { hideModal, isModalShown, onModalEvent, showModal } from './common';
import { BusyState, showFormError } from './form-state';
import { errorMessage, reloadUploads } from './misc';
import { showTab } from './tabbed-sections';
import { notify } from './notify';

type AttachmentLoader = (uploadId: string, name: string) => Promise<void>;
let resolveLoader: (load: AttachmentLoader) => void;
const loaderReady = new Promise<AttachmentLoader>(resolve => { resolveLoader = resolve; });

// from the attachments list, see chem-loader.ts: an attachment can be clicked before Ketcher has finished initializing
export async function loadAttachmentFromList(uploadId: string, name: string): Promise<void> {
  const load = await loaderReady;
  await load(uploadId, name);
}

const saveModalSelector = '#chemSaveAttachmentModal';
const loadModalSelector = '#chemLoadAttachmentModal';

export function initChemAttachments(ketcher: Ketcher): void {
  const saveForm = document.getElementById('chemSaveAttachmentForm') as HTMLFormElement;
  // only an entry in edit mode has attachments to save to or load from
  if (!saveForm) return;

  const loadForm = document.getElementById('chemLoadAttachmentForm') as HTMLFormElement;
  const saveButton = document.getElementById('chemSaveAttachment') as HTMLButtonElement;
  const loadButton = document.getElementById('chemLoadAttachment') as HTMLButtonElement;
  const filename = saveForm.elements.namedItem('filename') as HTMLInputElement;
  const formatSelect = saveForm.elements.namedItem('format') as HTMLSelectElement;
  const attachmentSelect = loadForm.elements.namedItem('attachment') as HTMLSelectElement;
  const loadSubmit = loadForm.querySelector<HTMLButtonElement>('[type="submit"]');
  const loading = document.getElementById('chemAttachmentsLoading');
  const empty = document.getElementById('chemAttachmentsEmpty');
  const saveError = document.getElementById('chemSaveAttachmentError');
  const loadError = document.getElementById('chemLoadAttachmentError');
  const endpoint = saveForm.dataset.endpoint;
  const extensions: string[] = JSON.parse(saveForm.dataset.extensions);
  const isCompatible = (name: string): boolean => extensions.some(extension => name.toLowerCase().endsWith('.' + extension));
  const state = new BusyState([saveButton, loadButton]);

  const formats = [
    { value: 'ket', label: 'KET', extension: 'ket', content: () => ketcher.getKet() },
    { value: 'mol', label: 'MOL V2000', extension: 'mol', content: () => ketcher.getMolfile('v2000') },
    { value: 'mol-v3000', label: 'MOL V3000', extension: 'mol', content: () => ketcher.getMolfile('v3000') },
    { value: 'rxn', label: 'RXN V2000', extension: 'rxn', content: () => ketcher.getRxn('v2000') },
    { value: 'rxn-v3000', label: 'RXN V3000', extension: 'rxn', content: () => ketcher.getRxn('v3000') },
    { value: 'smi', label: 'SMILES', extension: 'smi', content: () => ketcher.getSmiles() },
    { value: 'png', label: 'PNG', extension: 'png', content: async () => ketcher.generateImage(await ketcher.getKet(), { outputFormat: 'png' }) },
    { value: 'svg', label: 'SVG', extension: 'svg', content: async () => ketcher.generateImage(await ketcher.getKet(), { outputFormat: 'svg' }) },
  ];
  formatSelect.replaceChildren(...formats.map(format => new Option(format.label, format.value)));

  // Avoid dismissing a modal while its save/load operation is still running.
  for (const selector of [saveModalSelector, loadModalSelector]) {
    onModalEvent(selector, 'hide', event => {
      if (state.busy) event.preventDefault();
    });
  }

  saveButton.disabled = false;
  loadButton.disabled = false;
  saveButton.onclick = () => {
    saveError.hidden = true;
    const isReaction = ketcher.containsReaction();
    Array.from(formatSelect.options).forEach(option => {
      option.disabled = (option.value.startsWith('mol') && isReaction)
        || (option.value.startsWith('rxn') && !isReaction);
    });
    if (formatSelect.selectedOptions[0].disabled) formatSelect.value = 'ket';
    showModal(saveModalSelector);
  };
  onModalEvent(saveModalSelector, 'shown', () => filename.focus());

  saveForm.onsubmit = async event => {
    event.preventDefault();
    if (state.busy) return;
    filename.value = filename.value.trim();
    if (!saveForm.reportValidity()) return;
    const format = formats.find(format => format.value === formatSelect.value);
    if (!format) return;

    saveError.hidden = true;
    const saved = await state.run(saveForm, async () => {
      const content = await format.content();
      const name = filename.value.replace(/\.(ket|mol|rxn|smi|smiles|png|svg)$/i, '') + '.' + format.extension;
      const file = new File([content], name, { type: content instanceof Blob ? content.type : 'text/plain' });
      const data = new FormData();
      data.append('file', file);
      await ApiC.post(endpoint, data);
    }, error => showFormError(saveError, error));
    if (!saved) return;
    hideModal(saveModalSelector);
    // The file is already saved, even if refreshing the attachment list fails.
    await reloadUploads().catch(error => notify.error(error));
  };

  loadButton.onclick = async () => {
    loadError.hidden = true;
    empty.hidden = true;
    loading.hidden = false;
    attachmentSelect.replaceChildren();
    attachmentSelect.disabled = true;
    loadSubmit.disabled = true;
    loadButton.disabled = true;
    showModal(loadModalSelector);
    try {
      const uploads = await ApiC.getJson<Upload[]>(endpoint);
      const compatible = uploads.filter(upload => isCompatible(upload.real_name));
      attachmentSelect.replaceChildren(...compatible.map(upload => new Option(upload.real_name, String(upload.id))));
      empty.hidden = compatible.length > 0;
    } catch (error) {
      showFormError(loadError, error);
    } finally {
      loading.hidden = true;
      attachmentSelect.disabled = attachmentSelect.options.length === 0;
      loadSubmit.disabled = attachmentSelect.disabled;
      loadButton.disabled = false;
    }
  };

  const loadAttachment = async (uploadId: string, name: string): Promise<void> => {
    if (state.busy || !isCompatible(name)) return;
    loadError.hidden = true;
    const loaded = await state.run(loadForm, async () => {
      const response = await ApiC.get(`${endpoint}/${uploadId}`, { format: 'binary', notifOnError: 0 });
      const content = await response.text();
      return loadChemDrawing(ketcher, content, { confirm: loadForm.dataset.confirm, error: loadForm.dataset.error });
    }, error => {
      showFormError(loadError, error);
      // Loading from the attachments list happens with the modal closed, where the form error is not visible.
      if (!isModalShown(loadModalSelector)) notify.error(errorMessage(error));
    });
    if (!loaded) return;
    filename.value = name.replace(/\.[^.]+$/, '');
    hideModal(loadModalSelector);
    showTab('chem', { scroll: true });
  };

  loadForm.onsubmit = event => {
    event.preventDefault();
    if (!attachmentSelect.value) return;
    void loadAttachment(attachmentSelect.value, attachmentSelect.selectedOptions[0].text);
  };
  resolveLoader(loadAttachment);
}
