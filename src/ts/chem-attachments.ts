/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import type { Ketcher } from 'ketcher-core';
import { KetcherAsyncEvents } from 'ketcher-core';
import type { Upload } from './interfaces';
import $ from 'jquery';
import { ApiC } from './api';
import { adjustHiddenState, ensureTogglableSectionIsOpen, reloadElements } from './misc';
import { notify } from './notify';
import { on } from './handlers';

type AttachmentLoader = (uploadId: string, name: string) => Promise<void>;
let resolveLoader: (load: AttachmentLoader) => void;
const loaderReady = new Promise<AttachmentLoader>(resolve => { resolveLoader = resolve; });
// An attachment can be clicked before Ketcher has finished initializing.
on('chem-load-file', async el => {
  const load = await loaderReady;
  await load(el.dataset.uploadid, el.dataset.name);
});

export function initChemAttachments(ketcher: Ketcher): void {
  const saveForm = document.getElementById('chemSaveAttachmentForm') as HTMLFormElement;
  // The standalone editor has no entry to attach files to.
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
  const saveModal = $('#chemSaveAttachmentModal');
  const loadModal = $('#chemLoadAttachmentModal');
  const endpoint = saveForm.dataset.endpoint;
  const extensions: string[] = JSON.parse(saveForm.dataset.extensions);
  const isCompatible = (name: string): boolean => extensions.some(extension => name.toLowerCase().endsWith('.' + extension));
  let busy = false;

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

  const showError = (element: HTMLElement, error: unknown): void => {
    element.textContent = error instanceof Error ? error.message : String(error);
    element.hidden = false;
  };

  const setBusy = (form: HTMLFormElement, value: boolean): void => {
    busy = value;
    saveButton.disabled = value;
    loadButton.disabled = value;
    form.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLButtonElement>('input, select, button')
      .forEach(control => control.disabled = value);
  };
  // Avoid dismissing a modal while its save/load operation is still running.
  saveModal.add(loadModal).on('hide.bs.modal', event => {
    if (busy) event.preventDefault();
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
    saveModal.modal('show');
  };
  saveModal.on('shown.bs.modal', () => filename.focus());

  saveForm.onsubmit = async event => {
    event.preventDefault();
    if (busy) return;
    filename.value = filename.value.trim();
    if (!saveForm.reportValidity()) return;
    const format = formats.find(format => format.value === formatSelect.value);
    if (!format) return;

    saveError.hidden = true;
    setBusy(saveForm, true);
    try {
      const content = await format.content();
      const name = filename.value.replace(/\.(ket|mol|rxn|smi|smiles|png|svg)$/i, '') + '.' + format.extension;
      const file = new File([content], name, { type: content instanceof Blob ? content.type : 'text/plain' });
      const data = new FormData();
      data.append('file', file);
      await ApiC.post(endpoint, data);
      busy = false;
      saveModal.modal('hide');
      // The file is already saved, even if refreshing the attachment list fails.
      await reloadElements(['uploadsDiv']).then(() => adjustHiddenState()).catch(error => notify.error(error));
    } catch (error) {
      showError(saveError, error);
    } finally {
      setBusy(saveForm, false);
    }
  };

  loadButton.onclick = async () => {
    loadError.hidden = true;
    empty.hidden = true;
    loading.hidden = false;
    attachmentSelect.replaceChildren();
    attachmentSelect.disabled = true;
    loadSubmit.disabled = true;
    loadButton.disabled = true;
    loadModal.modal('show');
    try {
      const uploads = await ApiC.getJson<Upload[]>(endpoint);
      const compatible = uploads.filter(upload => isCompatible(upload.real_name));
      attachmentSelect.replaceChildren(...compatible.map(upload => new Option(upload.real_name, String(upload.id))));
      empty.hidden = compatible.length > 0;
    } catch (error) {
      showError(loadError, error);
    } finally {
      loading.hidden = true;
      attachmentSelect.disabled = attachmentSelect.options.length === 0;
      loadSubmit.disabled = attachmentSelect.disabled;
      loadButton.disabled = false;
    }
  };

  const loadAttachment = async (uploadId: string, name: string): Promise<void> => {
    if (busy || !isCompatible(name)) return;
    loadError.hidden = true;
    setBusy(loadForm, true);
    try {
      const response = await ApiC.get(`${endpoint}/${uploadId}`, { format: 'binary', notifOnError: 0 });
      const content = await response.text();
      // KET covers both molecules and macromolecules, including drawing objects.
      const previousDrawing = await ketcher.getKet();
      const drawing = JSON.parse(previousDrawing);
      if (drawing.root.nodes.length > 0 && !confirm(loadForm.dataset.confirm)) return;
      // setMolecule swallows import errors; listen for its failure event instead.
      let failed = false;
      const onFailure = () => failed = true;
      ketcher.eventBus.once(KetcherAsyncEvents.FAILURE, onFailure);
      try {
        await ketcher.setMolecule(content);
      } finally {
        ketcher.eventBus.off(KetcherAsyncEvents.FAILURE, onFailure);
      }
      if (failed) {
        // In macromolecule mode Ketcher clears the canvas before parsing.
        await ketcher.setMolecule(previousDrawing);
        throw new Error(loadForm.dataset.error);
      }
      filename.value = name.replace(/\.[^.]+$/, '');
      busy = false;
      loadModal.modal('hide');
      localStorage.setItem('chemEditorDiv-isHidden', '0');
      document.getElementById('chemEditorIcon').closest('[aria-expanded]').setAttribute('aria-expanded', 'true');
      ensureTogglableSectionIsOpen('chemEditorIcon', 'chemEditorDiv');
    } catch (error) {
      showError(loadError, error);
      if (!loadModal.hasClass('show')) notify.error(error instanceof Error ? error.message : String(error));
    } finally {
      setBusy(loadForm, false);
    }
  };

  loadForm.onsubmit = event => {
    event.preventDefault();
    if (!attachmentSelect.value) return;
    void loadAttachment(attachmentSelect.value, attachmentSelect.selectedOptions[0].text);
  };
  resolveLoader(loadAttachment);
}
