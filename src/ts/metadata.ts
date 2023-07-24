/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity } from './misc';
import { Metadata } from './Metadata.class';
import { ValidMetadata } from './metadataInterfaces';
import JsonEditorHelper from './JsonEditorHelper.class';
import { JsonEditorActions } from './JsonEditorActions.class';


document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('metadataDiv')) {
    return;
  }
  const entity = getEntity();

  // add extra fields elements from metadata json
  const JsonEditorHelperC = new JsonEditorHelper(entity);
  const MetadataC = new Metadata(entity, JsonEditorHelperC);
  MetadataC.display('edit');
  // only run if there is the json-editor block
  if (document.getElementById('json-editor')) {
    const JsonEditorActionsC = new JsonEditorActions();
    JsonEditorActionsC.init(JsonEditorHelperC, true);
  }
  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // DELETE EXTRA FIELD
    if (el.matches('[data-action="metadata-rm-field"]')) {
      MetadataC.read().then(metadata => {
        console.log('here');
        const name = el.parentElement.closest('div').querySelector('label').innerText;
        delete metadata.extra_fields[name];
        MetadataC.update(metadata as ValidMetadata);
      });
    }
  });
});
