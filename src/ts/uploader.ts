/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Dropzone from '@deltablot/dropzone';
import { reloadElement } from './misc';
import i18next from 'i18next';

export class Uploader
{
  // holds the resolve function of tinymce image handler
  tinyImageSuccess: (value: string | PromiseLike<string>) => void;

  getOptions() {
    const maxsize = parseInt(document.getElementById('info').dataset.maxsize, 10); // MB
    /* eslint-disable-next-line @typescript-eslint/no-this-alias */
    const that = this;
    return {
      // i18n message to user
      dictDefaultMessage: `<i class='fas fa-upload'></i> ${i18next.t('dropzone-upload-area')}<br> ${i18next.t('dropzone-filesize-limit')} ${maxsize} MB`,
      maxFilesize: maxsize,
      timeout: 900000,
      init: function(): void {
        // once upload is finished
        this.on('complete', function() {
          if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
            reloadElement('uploadsDiv').then(() => {
              // Now grab the url of the image to give it to tinymce if needed
              // first make sure the success function is set by tinymce and we are dealing with an image drop and not a regular upload
              if (typeof that.tinyImageSuccess !== 'undefined' && that.tinyImageSuccess !== null) {
                // Uses the newly updated HTML element for the uploads section to find the last file uploaded and use that to get the remote url for the image.
                // This gives TinyMCE the actual url of the uploaded image. TinyMce updates its editor to link to this rather than the temp location it sets up initially.
                // fun fact: if the upload failed for some reason, the blob in the text will get replaced by the previous image. So if you're looking at this code wondering why from time to time dropping image B in the text makes image A appear, that's because image B failed to upload and the code looks for the last upload!
                that.tinyImageSuccess(document.getElementById('last-uploaded-link').dataset.url);
                // This is to make sure that we do not end up adding a file to TinyMCE if a previous file was pasted and a consecutive file was uploaded using Dropzone.
                // The 'undefined' check is not enough. That is just for before any file was pasted.
                that.tinyImageSuccess = null;
              }
              that.init();
            });
          }
        });
      },
    };
  }

  init(): Dropzone {
    const dropzoneEl = document.getElementById('elabftw-dropzone');
    if (dropzoneEl) {
      // Dropzone can be initialized in edit.ts and uploads.ts but we should only init it once
      if (Object.prototype.hasOwnProperty.call(dropzoneEl, 'dropzone')) {
        return dropzoneEl.dropzone;
      }
      return new Dropzone(dropzoneEl, this.getOptions());
    }
  }
}
