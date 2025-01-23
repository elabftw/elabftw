/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Dropzone from '@deltablot/dropzone';
import { reloadElements, sizeToMb } from './misc';
import i18next from 'i18next';
import { Api } from './Apiv2.class';

export class Uploader
{
  async getOptions() {
    const importInfo = await (new Api()).getJson('import');
    const sizeInMb = sizeToMb(importInfo.max_upload_size);
    return {
      // i18n message to user
      dictDefaultMessage: `<i class='fas fa-upload'></i> ${i18next.t('dropzone-upload-area')}<br> ${i18next.t('dropzone-filesize-limit')} ${sizeInMb} MB`,
      maxFilesize: sizeInMb,
      timeout: importInfo.max_upload_time,
      init: function(): void {
        // once all files are uploaded
        this.on('queuecomplete', function() {
          if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
            reloadElements(['uploadsDiv']);
          }
        });
      },
    };
  }

  async init(): Promise<Dropzone> {
    // FIXME just added "as any" for now
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const dropzoneEl = document.getElementById('elabftw-dropzone') as any;
    if (dropzoneEl) {
      // Dropzone can be initialized in edit.ts and uploads.ts but we should only init it once
      if (Object.prototype.hasOwnProperty.call(dropzoneEl, 'dropzone')) {
        return dropzoneEl.dropzone;
      }
      return new Dropzone(dropzoneEl, await this.getOptions());
    }
  }
}
