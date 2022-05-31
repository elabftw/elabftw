/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any

import JsonEditorHelper from './JsonEditorHelper.class';
import { getEntity } from './misc';
import 'jsoneditor/dist/jsoneditor.min.css';

// JSON editor related stuff
document.addEventListener('DOMContentLoaded', () => {
  // only run if there is the json-editor block
  if (document.getElementById('json-editor')) {

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

    if (about.type == 'experiments_templates') {
      const entityWithId = {
        type: entity.type,
        id: parseInt(about.id, 10),
      };
      JsonEditorHelperC.loadMetadataFromId(entityWithId);
      document.getElementById('templateJsonSave').dataset.id = about.id;
    }

    // LISTENERS
    document.querySelector('.real-container').addEventListener('click', (event) => {
      const el = (event.target as HTMLElement);
      if (el.matches('[data-action="json-load-metadata"]')) {
        JsonEditorHelperC.loadMetadata();
      } else if (el.matches('[data-action="json-load-file"]')) {
        JsonEditorHelperC.loadFile(el.dataset.link, el.dataset.name, el.dataset.uploadid);
      } else if (el.matches('[data-action="json-save-metadata"]')) {
        JsonEditorHelperC.saveMetadata();
      } else if (el.matches('[data-action="json-save-metadata-from-id"]')) {
        const entityWithId = {
          type: entity.type,
          id: parseInt(document.getElementById('templateJsonSave').dataset.id, 10),
        };
        JsonEditorHelperC.saveMetadataFromId(entityWithId);
      } else if (el.matches('[data-action="json-save-file"]')) {
        JsonEditorHelperC.saveNewFile();
      } else if (el.matches('[data-action="json-save"]')) {
        // need the stopPropagation here to toggle #json-save-dropdown when save button is pressed
        event.stopPropagation();
        JsonEditorHelperC.save();
      } else if (el.matches('[data-action="json-clear"]')) {
        JsonEditorHelperC.clear();
      }
    });
  }
});
