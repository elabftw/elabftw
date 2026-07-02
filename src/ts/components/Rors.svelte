<script lang='ts'>
  /**
   * @author Nicolas CARPi / Deltablot
   * @copyright 2026 Nicolas CARPi
   * @see https://www.elabftw.net Official website
   * @license AGPL-3.0
   * @package elabftw
   */
  import { onMount, tick } from 'svelte';
  import { ApiC } from '../api';
  import { relativeMoment } from '../misc';
  import i18next from '../i18n';

  type RorAssociation = {
    teams_id?: number;
    ror: string;
    created_at: string;
  };

  let { endpoint: rorsEndpoint } = $props<{ endpoint: string }>();

  let rors = $state<RorAssociation[]>([]);
  let rorInput = $state('');
  let organizationNames = $state<Record<string, string>>({});
  let isInitialLoading = $state(true);
  let isSubmitting = $state(false);

  const uid = $props.id();
  const rorInputId = `${uid}-ror`;

  const rorPattern = /^0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}$/;
  const rorPatternHtml = '(?:https://ror\\.org/)?0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}';
  const t = i18next.t.bind(i18next);

  function normalizeRor(value: string): string {
    return value.trim().toLowerCase().replace(/^https:\/\/ror\.org\//, '');
  }

  async function loadRors(): Promise<void> {
    rors = await ApiC.getJson(rorsEndpoint);
    await loadOrganizationNames();
  }

  async function translateRor(ror: string): Promise<string> {
    const response = await fetch(`https://api.ror.org/v2/organizations/${encodeURIComponent(ror)}`, {
      headers: {
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      return ror;
    }

    const data = await response.json();

    return data.names?.find((name: { types: string[], value: string }) => name.types.includes('ror_display'))?.value
      ?? data.names?.[0]?.value
      ?? ror;
  }

  async function loadOrganizationNames(): Promise<void> {
    const entries = await Promise.all(
    rors
      .filter(({ ror }) => !organizationNames[ror])
      .map(async ({ ror }) => {
        return [ror, await translateRor(ror)] as const;
      }),
    );

    organizationNames = {
      ...organizationNames,
      ...Object.fromEntries(entries),
    };
  }

  $effect(() => {
    if (isInitialLoading) {
      return;
    }
    rors.length;

    void tick().then(() => {
      // remove the text so it is reloaded
      document.querySelectorAll('.relative-moment').forEach(el => {
        el.textContent = '';
      });

      relativeMoment();
    });
  });

  async function addRor(): Promise<void> {
    const ror = normalizeRor(rorInput);

    if (!rorPattern.test(ror)) {
      return;
    }

    isSubmitting = true;

    try {
      await ApiC.post(`${rorsEndpoint}/${encodeURIComponent(ror)}`);
      rorInput = '';
      await loadRors();
    } finally {
      isSubmitting = false;
    }
  }

  async function deleteRor(ror: string): Promise<void> {
    if (confirm(t('generic-delete-warning'))) {
      await ApiC.delete(`${rorsEndpoint}/${encodeURIComponent(ror)}`);
      await loadRors();
    }
  }

  onMount(async () => {
    try {
      await loadRors();
    } finally {
      isInitialLoading = false;
    }
  });
</script>

<div class='pl-3 mt-2'>

  {#if isInitialLoading}
    <p>{t('loading')}…</p>
  {:else if rors.length > 0}
    <h3>{t('existing-ror-associations')}</h3>
    <p class='text-muted'>
      {#if rorsEndpoint == 'instance/rors'}
        {t('ror-description')}
      {:else if rorsEndpoint.startsWith('teams')}
        {t('ror-description-team')}
      {:else}
        {t('ror-description-user')}
      {/if}
    </p>
    <table class='table' aria-describedby='existingRors' data-table-sort='true'>
      <thead>
        <tr>
          <th scope='col'>ROR</th>
          <th scope='col'>{t('organisation-name')}</th>
          <th scope='col'>{t('association-date')}</th>
          <th scope='col'><span class='sr-only'>{t('action')}</span></th>
        </tr>
      </thead>

      <tbody id='rorsTable'>
        {#each rors as rorAssociation (rorAssociation.ror)}
          <tr>
            <td data-label='ROR'>
              <span class='user-badge'>
                <a
                  href={`https://ror.org/${rorAssociation.ror}`}
                  rel='noopener'
                  target='_blank'
                  class='external-link'
                >
                  {rorAssociation.ror}
                </a>
              </span>
            </td>

            <td data-label={t('organisation-name')}>
              {organizationNames[rorAssociation.ror] ?? rorAssociation.ror}
            </td>

            <td data-label={t('association-date')}>
              <span class='relative-moment' title={rorAssociation.created_at}></span>
            </td>

            <td>
              <span class='sr-only'>{t('action')}</span>
              <button
                type='button'
                class='btn btn-danger-ghost'
                title={t('delete')}
                aria-label={t('delete')}
                on:click={() => deleteRor(rorAssociation.ror)}
              >
                <i class='fas fa-trash-alt fa-fw'></i>
              </button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  {:else}
    {t('no-rors')}
  {/if}

  <form on:submit|preventDefault={addRor}>
    <label for={rorInputId}>{t('ror-input-label')}</label>

    <div class='input-group'>
      <div class='input-group-prepend'>
        <span class='input-group-text'>ROR</span>
      </div>

      <input
        bind:value={rorInput}
        name='ror'
        id={rorInputId}
        class='form-control col-md-4'
        title={t('ror-input-title')}
        required
        pattern={rorPatternHtml}
        placeholder='04t0gwh46'
        autocomplete='off'
        spellcheck='false'
      />

      <div class='input-group-append'>
        <button
          class='btn btn-primary'
          type='submit'
          title={t('add')}
          disabled={isSubmitting}
        >
          {t('add')}
        </button>
      </div>
    </div>
  </form>
</div>
