/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import type { Ketcher } from 'ketcher-core';
import { ApiC } from './api';
import { loadChemDrawing } from './chem-drawing';
import { hideModal, onModalEvent, showModal } from './common';
import { BusyState, showFormError } from './form-state';
import { isCompatible, showLoadAttachmentModal } from './load-attachment';
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

export function initChemAttachments(ketcher: Ketcher): void {
  const saveForm = document.getElementById('chemSaveAttachmentForm') as HTMLFormElement;
  // only an entry in edit mode has attachments to save to or load from
  if (!saveForm) return;

  const saveButton = document.getElementById('chemSaveAttachment') as HTMLButtonElement;
  const loadButton = document.getElementById('chemLoadAttachment') as HTMLButtonElement;
  const filename = saveForm.elements.namedItem('filename') as HTMLInputElement;
  const formatSelect = saveForm.elements.namedItem('format') as HTMLSelectElement;
  const saveError = document.getElementById('chemSaveAttachmentError');
  const endpoint = saveForm.dataset.endpoint;
  const extensions: string[] = JSON.parse(saveForm.dataset.extensions);
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

  // Avoid dismissing the modal while its save operation is still running.
  onModalEvent(saveModalSelector, 'hide', event => {
    if (state.busy) event.preventDefault();
  });

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

  // replace the drawing with an attachment: false if the user keeps the current drawing
  const loadDrawing = async (uploadId: string, name: string): Promise<boolean> => {
    const response = await ApiC.get(`${endpoint}/${uploadId}`, { format: 'binary', notifOnError: 0 });
    const loaded = await loadChemDrawing(ketcher, await response.text(), { confirm: loadButton.dataset.confirm, error: loadButton.dataset.error });
    if (loaded) {
      filename.value = name.replace(/\.[^.]+$/, '');
      showTab('chem', { scroll: true });
    }
    return loaded;
  };

  loadButton.onclick = () => {
    if (state.busy) return;
    void showLoadAttachmentModal({ extensions, load: upload => loadDrawing(String(upload.id), upload.real_name) });
  };

  // from the attachments list, without the modal: the save form only serves to mark the editor as busy
  const loadAttachment = async (uploadId: string, name: string): Promise<void> => {
    if (state.busy || !isCompatible(name, extensions)) return;
    await state.run(saveForm, () => loadDrawing(uploadId, name), error => notify.error(errorMessage(error)));
  };

  resolveLoader(loadAttachment);
}
