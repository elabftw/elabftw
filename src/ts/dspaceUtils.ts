/**
 * @author Nicolas CARPi - Deltablot
 * @author Mouss - Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { entity } from './getEntity';
import JsonEditorHelper from './JsonEditorHelper.class';
import { Metadata } from './Metadata.class';
import { ExtraFieldInputType, ValidMetadata } from './metadataInterfaces';
import { reloadElements } from './misc';

export async function saveDspaceIdAsExtraField(itemUuid: string): Promise<void> {
  const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
  const raw = await MetadataC.read();
  const metadata = (raw || {}) as ValidMetadata;
  if (!metadata.extra_fields) {
    metadata.extra_fields = {};
  }

  metadata.extra_fields['DSpace Item Public URL'] = {
    type: ExtraFieldInputType.Url,
    value: itemUuid,
    description: 'Link to item in DSpace repository',
    readonly: true,
  };

  const mode = new URLSearchParams(window.location.search).get('mode');
  await MetadataC.save(metadata).then(() => mode === 'edit'
    ? MetadataC.display('edit')
    : reloadElements(['extraFieldsDiv']));
}

export interface DspaceCollection {
  uuid: string;
  name: string;
  [key: string]: unknown;
}

export interface DspaceVocabularyEntry {
  value: string;
  display: string;
  [key: string]: unknown;
}

export interface DspaceVocabularyEntryList {
  _embedded: {
    entries: DspaceVocabularyEntry[];
  };
  [key: string]: unknown;
}
