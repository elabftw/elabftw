/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { FileType } from './interfaces';
import JsonEditorHelper from './JsonEditorHelper.class';
import { askFileName, saveStringAsFile } from './misc';
import 'jsoneditor/dist/jsoneditor.min.css';
import { notify } from './notify';

export class JsonEditorActions {

  init(JsonEditorHelperC: JsonEditorHelper, editable: boolean) {
    JsonEditorHelperC.init(editable);

    const displayMainTextSliderInput = document.getElementById('displayMainTextSliderInput') as HTMLInputElement;
    displayMainTextSliderInput?.addEventListener('change', () => {
      JsonEditorHelperC.toggleDisplayMainText();
    });

    // LISTENERS
    document.querySelector('.real-container').addEventListener('click', (event) => {
      const el = (event.target as HTMLElement);
      try {
        if (el.matches('[data-action="json-load-file"]')) {
          JsonEditorHelperC.loadFile(el.dataset.link, el.dataset.name, el.dataset.uploadid);
        } else if (el.matches('[data-action="json-save-metadata"]')) {
          JsonEditorHelperC.saveMetadata();
        } else if (el.matches('[data-action="json-save-file"]')) {
          JsonEditorHelperC.saveNewFile();
        } else if (el.matches('[data-action="json-saveas-file"]')) {
          const realName = askFileName(FileType.Json);
          if (!realName) return;
          saveStringAsFile(realName, JSON.stringify(JsonEditorHelperC.editor.get()));
        } else if (el.matches('[data-action="json-save"]')) {
          JsonEditorHelperC.save();
          // make the save button stand out if the content is changed
          document.querySelector('[data-action="json-save"]').classList.remove('border-danger');
          document.getElementById('jsonUnsavedChangesWarningDiv').hidden = true;
        } else if (el.matches('[data-action="json-import-file"]')) {
          const fileInput = document.getElementById('jsonImportFileInput') as HTMLInputElement;
          if (!fileInput) {
            notify.error('resource-not-found');
            return;
          }
          fileInput.click();
          fileInput.onchange = () => {
            const file = fileInput.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.readAsText(file);
            reader.onload = function() {
              // an error here will not bubble up, so add another try catch block
              // adding an onerror function doesn't seem to work
              try {
                JsonEditorHelperC.editor.set(JSON.parse(reader.result as string));
              } catch (error) {
                notify.error(error);
              }
            };
            reader.onerror = () => notify.error('import-error');
            // allow selecting the same file again
            fileInput.value = '';
          };
        } else if (el.matches('[data-action="json-clear"]')) {
          JsonEditorHelperC.clear();
        }
      } catch (error) {
        notify.error(error);
      }
    });
  }
}
