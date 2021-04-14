/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;

import JsonEditorHelper from './JsonEditorHelper.class';
import { getEntity } from './misc';

// JSON editor related stuff
document.addEventListener('DOMContentLoaded', () => {
  // only run if the main json editor container exists on the page
  if (document.getElementById('jsonEditorContainer')) {

    // fix the keymaster shortcut library interfering with the editor
    key.filter = (event): boolean => {
      const tagName = (event.target || event.srcElement).tagName;
      return !(tagName == 'INPUT' || tagName == 'SELECT' || tagName == 'TEXTAREA' || (event.target || event.srcElement).hasAttribute('contenteditable'));
    };

    // holds info about the page through data attributes
    const about = document.getElementById('info').dataset;

    const entity = getEntity();
    const JsonEditorHelperC = new JsonEditorHelper(entity);
    JsonEditorHelperC.init((about.page === 'edit' || about.page === 'template-edit'));

    // LISTENERS
    document.querySelector('.real-container').addEventListener('click', (event) => {
      const el = (event.target as HTMLElement);
      if (el.matches('[data-action="json-load-metadata"]')) {
        JsonEditorHelperC.loadMetadata();
      } else if (el.matches('[data-action="json-load-metadata-from-id"]')) {
        JsonEditorHelperC.loadMetadataFromId(entity);
        // add the id of the currently edited item on the save button
        document.getElementById('itemsTypesJsonSave').dataset.id = el.dataset.id;
      } else if (el.matches('[data-action="json-load-file"]')) {
        JsonEditorHelperC.loadFile(el.dataset.link, el.dataset.name, el.dataset.uploadid);
      } else if (el.matches('[data-action="json-save-metadata"]')) {
        JsonEditorHelperC.saveMetadata();
      } else if (el.matches('[data-action="json-save-metadata-from-id"]')) {
        JsonEditorHelperC.saveMetadataFromId(entity);
      } else if (el.matches('[data-action="json-save-file"]')) {
        JsonEditorHelperC.saveFile();
      } else if (el.matches('[data-action="json-save"]')) {
        JsonEditorHelperC.save();
      } else if (el.matches('[data-action="json-clear"]')) {
        JsonEditorHelperC.clear();
      }
    });
  }
});
