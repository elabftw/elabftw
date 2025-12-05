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

// GET list of collections
export async function listCollections(): Promise<DspaceCollectionList> {
  const res = await fetch(`/api/v2/dspace?dspace_action=${Action.ListCollections}`, { credentials: 'include' });
  if (!res.ok) throw new Error(`DSpace collections error: ${res.status}`);
  return await res.json() as DspaceCollectionList;
}

// GET list of types
export async function listTypes(): Promise<DspaceVocabularyEntryList> {
  const res = await fetch(`/api/v2/dspace?dspace_action=${Action.ListTypes}`, { credentials: 'include' });
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

  metadata.extra_fields['DSpace id'] = {
    type: ExtraFieldInputType.Text,
    value: itemUuid,
    description: 'Uuid handle from DSpace',
    readonly: true,
  };

  const mode = new URLSearchParams(window.location.search).get('mode');
  await MetadataC.save(metadata).then(() => MetadataC.display(mode === 'edit' ? 'edit' : 'view'));
}


export interface DspaceCollection {
  uuid: string;
  name: string;
  [key: string]: unknown;
}

export interface DspaceCollectionList {
  _embedded: {
    collections: DspaceCollection[];
  };
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
