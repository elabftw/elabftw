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
import { Action, Method, Model } from './interfaces';
import { rememberLastSelected, selectLastSelected } from './localStorage';
import { notify } from './notify';
import { entity } from './getEntity';
import { TomSelect, collectForm, mkSpin, mkSpinStop, reloadElements } from './misc';
import { on } from './handlers';
import $ from 'jquery';
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

on('export-to-inveniordm', async (el, event: Event) => {
  const btn = el as HTMLButtonElement;
  event.preventDefault();
  const form = document.getElementById('inveniordmExportForm') as HTMLFormElement;
  const params = collectForm(form);
  const oldHTML = mkSpin(btn);
  const prevNotifOnSaved = ApiC.notifOnSaved;
  const prevNotifOnError = ApiC.notifOnError;
  try {
    ApiC.notifOnSaved = false;
    ApiC.notifOnError = false;
    const res = await ApiC.send(Method.PATCH, 'inveniordm', { ...params, entity });
    const data = await res.json();
    await saveDraftUrlAsExtraField(data.draftUrl);
    notify.success('export-success');
    $('#inveniordmExportModal').modal('hide');
  } catch (e) {
    notify.error(e);
  } finally {
    ApiC.notifOnSaved = prevNotifOnSaved;
    ApiC.notifOnError = prevNotifOnError;
    mkSpinStop(btn, oldHTML);
  }
});

on('open-inveniordm-modal', async () => {
  $('#inveniordmExportModal').modal('toggle');

  const communitySelect = document.getElementById('inveniordmCommunity') as HTMLSelectElement & { tomselect?: TomSelect };
  const resourceTypeSelect = document.getElementById('inveniordmResourceType') as HTMLSelectElement & { tomselect?: TomSelect };
  const licenseSelect = document.getElementById('inveniordmLicense') as HTMLSelectElement & { tomselect?: TomSelect };

  const loading = `<option disabled selected>${i18next.t('loading')}...</option>`;
  communitySelect.innerHTML = `<option value=''>${i18next.t('none')}</option>`;
  resourceTypeSelect.innerHTML = loading;
  licenseSelect.innerHTML = loading;

  try {
    const [communitiesJson, resourceTypesJson, licensesJson] = await Promise.all([
      ApiC.getJson<Record<string, unknown>>('inveniordm', { action: Action.GetCommunities }),
      ApiC.getJson<Record<string, unknown>>('inveniordm', { action: Action.GetResourceTypes }),
      ApiC.getJson<Record<string, unknown>>('inveniordm', { action: Action.GetLicenses }),
    ]);

    if (communitySelect.tomselect) communitySelect.tomselect.destroy();
    if (resourceTypeSelect.tomselect) resourceTypeSelect.tomselect.destroy();
    if (licenseSelect.tomselect) licenseSelect.tomselect.destroy();

    // communities: hits.hits[]
    const communities = (communitiesJson as any)?.hits?.hits ?? [];
    communitySelect.innerHTML = `<option value=''>${i18next.t('none')}</option>`;
    communities.forEach((c: any) => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = c.metadata?.title ?? c.id;
      communitySelect.appendChild(opt);
    });

    // resource types: hits.hits[]
    const resourceTypes = (resourceTypesJson as any)?.hits?.hits ?? [];
    resourceTypeSelect.innerHTML = '';
    resourceTypes.forEach((rt: any) => {
      const opt = document.createElement('option');
      opt.value = rt.id;
      opt.textContent = rt.props?.subtype_name ?? rt.props?.type_name ?? rt.id;
      resourceTypeSelect.appendChild(opt);
    });

    // licenses: hits.hits[]
    const licenses = (licensesJson as any)?.hits?.hits ?? [];
    licenseSelect.innerHTML = '';
    licenses.forEach((lic: any) => {
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
