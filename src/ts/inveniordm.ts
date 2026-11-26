/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Karl Krägelin
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * All logic related to InvenioRDM export modal. Located in toolbar on view/edit pages.
 */
import { ApiC } from './api';
import i18next from './i18n';
import { Action, Model } from './interfaces';
import { rememberLastSelected, selectLastSelected } from './localStorage';
import { notify } from './notify';
import { hideModal, showModal } from './common';
import { entity } from './getEntity';
import { TomSelect, collectForm, mkSpin, mkSpinStop, reloadElements } from './misc';
import { on } from './handlers';
import JsonEditorHelper from './JsonEditorHelper.class';
import { Metadata } from './Metadata.class';
import { ExtraFieldInputType, ValidMetadata } from './metadataInterfaces';

async function saveDraftUrlAsExtraField(draftUrl: string): Promise<void> {
  const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
  const raw = await MetadataC.read();
  const metadata = (raw || {}) as ValidMetadata;
  if (!metadata.extra_fields) {
    metadata.extra_fields = {};
  }
  metadata.extra_fields['InvenioRDM Draft URL'] = {
    type: ExtraFieldInputType.Url,
    value: draftUrl,
    description: 'Link to draft record in InvenioRDM repository',
    readonly: true,
  };
  const mode = new URLSearchParams(window.location.search).get('mode');
  await MetadataC.save(metadata).then(() => mode === 'edit'
    ? MetadataC.display('edit')
    : reloadElements(['extraFieldsSection']));
}

interface InvenioHit {
  id: string;
  title?: { en?: string };
  props?: { subtype_name?: string; type_name?: string; spdx_id?: string };
  metadata?: { title?: string };
}

interface InvenioHits {
  hits?: { hits?: InvenioHit[] };
}

on('export-to-inveniordm', async (el, event: Event) => {
  const btn = el as HTMLButtonElement;
  event.preventDefault();
  const form = document.getElementById('inveniordmExportForm') as HTMLFormElement;
  const params = collectForm(form);
  const oldHTML = mkSpin(btn);
  try {
    const res = await ApiC.patch('inveniordm', { notifOnSaved: 0, notifOnError: 0, ...params, entity });
    const data = await res.json();
    // the draft exists now: a failure below must not look like a failed export, or a retry creates a second draft
    notify.success('export-success');
    hideModal('#inveniordmExportModal');
    try {
      await saveDraftUrlAsExtraField(data.draftUrl);
    } catch (e) {
      notify.error(`${e} (${data.draftUrl})`);
    }
  } catch (e) {
    notify.error(e);
  } finally {
    mkSpinStop(btn, oldHTML);
  }
});

on('open-inveniordm-modal', async () => {
  showModal('#inveniordmExportModal');

  const communitySelect = document.getElementById('inveniordmCommunity') as HTMLSelectElement & { tomselect?: TomSelect };
  const resourceTypeSelect = document.getElementById('inveniordmResourceType') as HTMLSelectElement & { tomselect?: TomSelect };
  const licenseSelect = document.getElementById('inveniordmLicense') as HTMLSelectElement & { tomselect?: TomSelect };

  const loading = `<option disabled selected>${i18next.t('loading')}...</option>`;
  communitySelect.innerHTML = `<option value=''>${i18next.t('not-set')}</option>`;
  resourceTypeSelect.innerHTML = loading;
  licenseSelect.innerHTML = loading;

  try {
    const [communitiesJson, resourceTypesJson, licensesJson] = await Promise.all([
      ApiC.getJson<InvenioHits>('inveniordm', { action: Action.GetCommunities }),
      ApiC.getJson<InvenioHits>('inveniordm', { action: Action.GetResourceTypes }),
      ApiC.getJson<InvenioHits>('inveniordm', { action: Action.GetLicenses }),
    ]);

    if (communitySelect.tomselect) communitySelect.tomselect.destroy();
    if (resourceTypeSelect.tomselect) resourceTypeSelect.tomselect.destroy();
    if (licenseSelect.tomselect) licenseSelect.tomselect.destroy();

    // communities: hits.hits[]
    const communities = communitiesJson?.hits?.hits ?? [];
    communitySelect.innerHTML = `<option value=''>${i18next.t('not-set')}</option>`;
    communities.forEach((c: InvenioHit) => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = c.metadata?.title ?? c.id;
      communitySelect.appendChild(opt);
    });

    // resource types: hits.hits[]
    const resourceTypes = resourceTypesJson?.hits?.hits ?? [];
    resourceTypeSelect.innerHTML = '';
    resourceTypes.forEach((rt: InvenioHit) => {
      const opt = document.createElement('option');
      opt.value = rt.id;
      opt.textContent = rt.props?.subtype_name ?? rt.props?.type_name ?? rt.id;
      resourceTypeSelect.appendChild(opt);
    });

    // licenses: hits.hits[]
    const licenses = licensesJson?.hits?.hits ?? [];
    licenseSelect.innerHTML = '';
    licenses.forEach((lic: InvenioHit) => {
      const opt = document.createElement('option');
      opt.value = lic.id;
      opt.textContent = lic.props?.spdx_id ?? lic.title?.en ?? lic.id;
      licenseSelect.appendChild(opt);
    });

    ['inveniordmCommunity', 'inveniordmResourceType', 'inveniordmLicense'].forEach(id => {
      new TomSelect(`#${id}`, {
        plugins: ['dropdown_input', 'no_active_items'],
        onChange: rememberLastSelected(id),
        onInitialize: selectLastSelected(id),
      });
    });
  } catch (e) {
    resourceTypeSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Resource types' })}</option>`;
    licenseSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Licenses' })}</option>`;
    console.error(e);
  }
});

on('clear-inveniordm-token', () => {
  ApiC.patch(`${Model.User}/me`, { inveniordm_token: '' })
    .then(() => reloadElements(['ucp-account-form']));
});
