/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getInput, reloadUploads } from './misc';
import i18next from './i18n';
import { Action, Model } from './interfaces';
import type { Upload } from './interfaces';
import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';
import { notify } from './notify';
import { getUploadUrl, uploadFileToEntity } from './pasteImage';
import { showTab } from './tabbed-sections';
import { hideModal, showModal } from './common';

const doodleCanvas = document.getElementById('doodleCanvas') as HTMLCanvasElement;
if (doodleCanvas) {

  const hasPointer = 'onpointerdown' in window;
  // store the clicks
  let clickX: number[] = [];
  let clickY: number[] = [];
  // bool to store the state of painting
  let isPainting = false;
  let wasPainting = false;
  // something was drawn or loaded since the last clear, so replacing the canvas needs a confirmation
  let hasContent = false;

  const doodleDiv = document.getElementById('doodleDiv');
  const extensions: string[] = JSON.parse(doodleDiv.dataset.extensions);
  const isCompatible = (name: string): boolean => extensions.some(extension => name.toLowerCase().endsWith('.' + extension));
  const confirmReplace = (): boolean => !hasContent || confirm(doodleDiv.dataset.confirmReplace);

  const context: CanvasRenderingContext2D = doodleCanvas.getContext('2d');

  // draw with the primary color of the user (or of the theme) by default
  // a color input only takes #rrggbb: let a canvas convert whatever the css variable holds
  const probe = document.createElement('canvas').getContext('2d');
  probe.fillStyle = 'rgba(0, 0, 0, 0)';
  probe.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
  // an invalid or transparent color leaves the rgba() value: keep the default of the input then
  if (/^#[0-9a-f]{6}$/i.test(probe.fillStyle)) {
    getInput('doodleStrokeStyle').value = probe.fillStyle;
  }

  const wrapper = document.getElementById('doodleCanvasWrapper');
  const sizeLabel = document.getElementById('doodleCanvasSize');
  // once the user resized the canvas, it no longer follows the width of the editor
  let userSized = false;
  const minSize = 100;

  function copyCanvas(): HTMLCanvasElement {
    const copy = document.createElement('canvas');
    copy.width = doodleCanvas.width;
    copy.height = doodleCanvas.height;
    copy.getContext('2d').drawImage(doodleCanvas, 0, 0);
    return copy;
  }

  // changing the size of a canvas clears it, so draw back what was there: the part outside of a smaller canvas is cropped
  function setSize(width: number, height: number, snapshot: HTMLCanvasElement = copyCanvas()): void {
    doodleCanvas.width = Math.round(width);
    doodleCanvas.height = Math.round(height);
    context.drawImage(snapshot, 0, 0);
    sizeLabel.textContent = `${doodleCanvas.width} × ${doodleCanvas.height} px`;
  }

  // the canvas fills the width of the editor through its width attribute, not css: stretching it with css would
  // scale the drawing and shift it away from the pointer. Once something is drawn it only grows, so it is never cropped.
  function fitWidth(): void {
    const width = wrapper.clientWidth;
    if (userSized || width === 0 || width === doodleCanvas.width || (width < doodleCanvas.width && hasContent)) {
      return;
    }
    setSize(width, doodleCanvas.height);
  }
  // inactive tabs keep their width (see tabbed-sections.ts), so this works even before the image tab is shown
  new ResizeObserver(fitWidth).observe(wrapper);
  sizeLabel.textContent = `${doodleCanvas.width} × ${doodleCanvas.height} px`;

  // resize handle in the bottom right corner of the canvas
  const handle = document.getElementById('doodleResizeHandle');
  const resizeTo = (width: number, height: number, snapshot?: HTMLCanvasElement): void => {
    userSized = true;
    setSize(Math.max(minSize, width), Math.max(minSize, height), snapshot);
  };
  handle.addEventListener('pointerdown', (event: PointerEvent) => {
    event.preventDefault();
    // always redraw from the canvas as it was when the drag started, so shrinking then growing again loses nothing
    const snapshot = copyCanvas();
    const start = { x: event.clientX, y: event.clientY, width: doodleCanvas.width, height: doodleCanvas.height };
    const onMove = (move: PointerEvent): void => {
      resizeTo(start.width + move.clientX - start.x, start.height + move.clientY - start.y, snapshot);
    };
    // listen on the document so the drag continues when the pointer goes past the handle
    const onUp = (): void => {
      document.removeEventListener('pointermove', onMove);
      document.removeEventListener('pointerup', onUp);
      document.removeEventListener('pointercancel', onUp);
    };
    document.addEventListener('pointermove', onMove);
    document.addEventListener('pointerup', onUp);
    document.addEventListener('pointercancel', onUp);
  });
  handle.addEventListener('keydown', (event: KeyboardEvent) => {
    const step = event.shiftKey ? 50 : 10;
    const delta = {
      ArrowLeft: [-step, 0],
      ArrowRight: [step, 0],
      ArrowUp: [0, -step],
      ArrowDown: [0, step],
    }[event.key];
    if (!delta) {
      return;
    }
    event.preventDefault();
    resizeTo(doodleCanvas.width + delta[0], doodleCanvas.height + delta[1]);
  });

  // margins around a loaded image, in px
  const doodleMinMargin = 40;
  const doodleMaxMargin = 120;

  // draw the image in the middle of the canvas
  function loadImage(url: string): Promise<void> {
    return new Promise<void>((resolve, reject) => {
      const img = new Image();
      img.addEventListener('load', () => {
        // leave room around the image for annotations: a tenth of its largest side, but within bounds so a big image
        // does not end up lost in empty space, and a small one still has room around it
        const margin = Math.round(Math.min(Math.max(Math.max(img.width, img.height) * 0.1, doodleMinMargin), doodleMaxMargin));
        // keep the canvas at least as wide as the editor, and follow it again until the user resizes it
        userSized = false;
        // an empty snapshot: the previous drawing is replaced, not kept
        setSize(Math.max(img.width + 2 * margin, wrapper.clientWidth), img.height + 2 * margin, document.createElement('canvas'));
        context.drawImage(img, (doodleCanvas.width - img.width) / 2, margin);
        clickX = [];
        clickY = [];
        hasContent = true;
        showTab('image', { scroll: true });
        resolve();
      });
      img.addEventListener('error', () => reject(new Error(doodleDiv.dataset.error)));
      img.src = url;
    }).catch(error => notify.error(error));
  }

  // from the menu of an upload
  on('annotate-image', (el: HTMLElement) => {
    if (!confirmReplace()) {
      return;
    }
    void loadImage(getUploadUrl({ long_name: el.dataset.path, storage: Number(el.dataset.storage) }));
  });

  // pick one of the image attachments of the entry
  const loadModal = '#doodleLoadAttachmentModal';
  const loadForm = document.getElementById('doodleLoadAttachmentForm') as HTMLFormElement;
  const attachmentSelect = document.getElementById('doodleAttachmentSelect') as HTMLSelectElement;
  const loadSubmit = loadForm.querySelector<HTMLButtonElement>('[type="submit"]');
  const loading = document.getElementById('doodleAttachmentsLoading');
  const empty = document.getElementById('doodleAttachmentsEmpty');
  // the api returns the storage, which the shared Upload interface does not declare
  type StoredUpload = Upload & { id: number; storage: number };
  let compatible: StoredUpload[] = [];

  on('doodle-load-attachment', async () => {
    empty.hidden = true;
    loading.hidden = false;
    attachmentSelect.replaceChildren();
    attachmentSelect.disabled = true;
    loadSubmit.disabled = true;
    showModal(loadModal);
    try {
      const uploads = await ApiC.getJson<StoredUpload[]>(`${entity.type}/${entity.id}/${Model.Upload}`);
      compatible = uploads.filter(upload => isCompatible(upload.real_name));
      attachmentSelect.replaceChildren(...compatible.map(upload => new Option(upload.real_name, String(upload.id))));
      empty.hidden = compatible.length > 0;
    } catch (error) {
      notify.error(error);
    } finally {
      loading.hidden = true;
      attachmentSelect.disabled = attachmentSelect.options.length === 0;
      loadSubmit.disabled = attachmentSelect.disabled;
    }
  });

  loadForm.addEventListener('submit', event => {
    event.preventDefault();
    const upload = compatible.find(candidate => String(candidate.id) === attachmentSelect.value);
    if (!upload || !confirmReplace()) {
      return;
    }
    hideModal(loadModal);
    void loadImage(getUploadUrl(upload));
  });

  // upload a new image as attachment and load it right away
  const uploadInput = document.getElementById('doodleUploadInput') as HTMLInputElement;
  on('doodle-upload-image', () => uploadInput.click());
  uploadInput.addEventListener('change', async () => {
    const file = uploadInput.files?.[0];
    // reset so selecting the same file again still triggers a change
    uploadInput.value = '';
    if (!file) {
      return;
    }
    if (!isCompatible(file.name)) {
      notify.error(doodleDiv.dataset.error);
      return;
    }
    if (!confirmReplace()) {
      return;
    }
    try {
      await loadImage(await uploadFileToEntity(file, file.name));
    } catch {
      // the api client already displayed the error
    }
  });

  function draw(dragging: boolean): void {
    // get last items in arrays
    const x = clickX[clickX.length - 1];
    const y = clickY[clickY.length - 1];

    const path = new Path2D();

    if (dragging) {
      path.moveTo(clickX[clickX.length - 2], clickY[clickY.length - 2]);
    } else {
      // if it's just a point click, draw from close location
      path.moveTo(x - 1, y);
    }
    path.lineTo(x, y);
    path.closePath();

    context.globalCompositeOperation = 'source-over';
    context.strokeStyle = getInput('doodleStrokeStyle').value;
    if (getInput('doodleEraser').checked) {
      context.globalCompositeOperation = 'destination-out';
      context.strokeStyle = 'rgba(0,0,0,1)';
    }

    context.lineJoin = 'round';
    context.lineWidth = Number(getInput('doodleStrokeWidth').value);

    context.stroke(path);
  }

  function addText(x: number, y: number, text: string): void {
    hasContent = true;
    context.font = '18px Arial';
    context.fillStyle = getInput('doodleStrokeStyle').value;
    context.fillText(text, x, y);
  }

  function addClick(x: number, y: number, dragging: boolean): void {
    hasContent = true;
    clickX.push(x);
    clickY.push(y);
    draw(dragging);
  }

  document.getElementById('clearCanvas').addEventListener('click', () => {
    context.clearRect(0, 0, context.canvas.width, context.canvas.height);
    clickX = [];
    clickY = [];
    hasContent = false;
  });

  document.getElementById('saveCanvas').addEventListener('click', () => {
    const image = doodleCanvas.toDataURL();
    const realName = prompt(i18next.t('request-filename'));
    if (realName === null || realName === '') {
      return;
    }
    const params = {
      'action': Action.CreateFromString,
      'file_type': 'png',
      'real_name': realName,
      'content': image,
    };
    // sent as a data url: the server adds the png extension when the name has none
    ApiC.post(`${entity.type}/${entity.id}/${Model.Upload}`, params).then(() => reloadUploads());
  });

  /**
   * Pointer Events (mouse/pen)
   */
  if (hasPointer) {
    doodleCanvas.addEventListener('pointerdown', (e) => {
      e.preventDefault();
      const rect = (e.target as HTMLCanvasElement).getBoundingClientRect();

      // if ctrl key is pressed, we ask for text to insert
      if (e.ctrlKey) {
        const text = prompt('Text to insert:');
        if (text === null) {
          return;
        }
        addText(e.clientX - rect.left, e.clientY - rect.top, text);
      } else {
        // ignore non-primary mouse buttons
        if (e.pointerType === 'mouse' && e.button !== 0) {
          return;
        }
        isPainting = true;
        addClick(e.clientX - rect.left, e.clientY - rect.top, false);
        // keep receiving moves even when the pointer leaves the canvas
        doodleCanvas.setPointerCapture(e.pointerId);
      }
    }, {passive: false});

    doodleCanvas.addEventListener('pointermove', (e) => {
      e.preventDefault();
      if (isPainting) {
        const rect = (e.target as HTMLCanvasElement).getBoundingClientRect();
        addClick(e.clientX - rect.left, e.clientY - rect.top, true);
      }
    }, {passive: false});

    doodleCanvas.addEventListener('pointerleave', (e) => {
      e.preventDefault();
      if (isPainting) {
        const rect = (e.target as HTMLCanvasElement).getBoundingClientRect();
        addClick(e.clientX - rect.left, e.clientY - rect.top, true);
        isPainting = false;
        if (e.buttons !== 0 || e.pressure > 0) {
          wasPainting = true;
        }
      }
    }, {passive: false});

    doodleCanvas.addEventListener('pointerenter', (e) => {
      e.preventDefault();
      if ((e.buttons !== 0 || e.pressure > 0) && wasPainting) {
        isPainting = true;
        wasPainting = false;
        const rect = (e.target as HTMLCanvasElement).getBoundingClientRect();
        addClick(e.clientX - rect.left, e.clientY - rect.top, false);
        doodleCanvas.setPointerCapture?.(e.pointerId);
      }
    }, {passive: false});

    doodleCanvas.addEventListener('pointerup', (e) => {
      isPainting = false;
      wasPainting = false;
      // Release capture if it was set
      doodleCanvas.releasePointerCapture?.(e.pointerId);
    }, {passive: true});

    doodleCanvas.addEventListener('pointercancel', (e) => {
      isPainting = false;
      wasPainting = false;
      doodleCanvas.releasePointerCapture?.(e.pointerId);
    }, {passive: true});

  /*
   * Touch events
   */
  } else {
    doodleCanvas.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) {
        e.preventDefault();
        const rect = (e.target as HTMLCanvasElement).getBoundingClientRect();
        const touch = e.touches[0];
        isPainting = true;
        addClick(touch.clientX - rect.left, touch.clientY - rect.top, false);
      }
    }, {capture: false, passive: false});

    doodleCanvas.addEventListener('touchmove', (e) => {
      if (isPainting) {
        e.preventDefault();
        const rect = (e.target as HTMLCanvasElement).getBoundingClientRect();
        const touch = e.touches[0];
        addClick(touch.clientX - rect.left, touch.clientY - rect.top, true);
      }
    }, {capture: false, passive: false});

    doodleCanvas.addEventListener('touchend', (e) => {
      e.preventDefault();
      isPainting = false;
    }, false);

    doodleCanvas.addEventListener('touchcancel', () => {
      isPainting = false;
    }, false);
  }
}
