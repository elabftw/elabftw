/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { entity } from './getEntity';
import { Action } from './interfaces';
import JsonEditorHelper from './JsonEditorHelper.class';
import { Metadata } from './Metadata.class';
import { ExtraFieldInputType, ValidMetadata } from './metadataInterfaces';
import { reloadElements } from './misc';

// GET list of collections
export async function getCollections(): Promise<DspaceCollection[]> {
  const res = await fetch(`/api/v2/dspace?action=${Action.GetCollections}`, { credentials: 'include' });
  if (!res.ok) throw new Error(`DSpace collections error: ${res.status}`);
  return await res.json() as DspaceCollection[];
}

// GET list of types
export async function getTypes(): Promise<DspaceVocabularyEntryList> {
  const res = await fetch(`/api/v2/dspace?action=${Action.GetTypes}`, { credentials: 'include' });
  if (!res.ok) throw new Error(`DSpace types error: ${res.status}`);
  return await res.json() as DspaceVocabularyEntryList;
}

export async function saveDspaceIdAsExtraField(itemUuid: string): Promise<void> {
  const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
  const raw = await MetadataC.read();
  const metadata = (raw || {}) as ValidMetadata;
  if (!metadata.extra_fields) {
    metadata.extra_fields = {};
  }

  metadata.extra_fields['DSpace Item Public URL'] = {
    type: ExtraFieldInputType.Text,
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
