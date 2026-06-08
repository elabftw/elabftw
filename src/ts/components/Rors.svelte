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

  type RorAssociation = {
    teams_id: number;
    ror: string;
    created_at: string;
  };

  let rors = $state<RorAssociation[]>([]);
  let rorInput = $state('');
  let organizationNames = $state<Record<string, string>>({});
  let isInitialLoading = $state(true);
  let isSubmitting = $state(false);

  const rorPattern = /^0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}$/;
  const rorPatternHtml = '(?:https://ror\\.org/)?0[a-hj-km-np-tv-z0-9]{6}[0-9]{2}';

  function normalizeRor(value: string): string {
    return value.trim().toLowerCase().replace(/^https:\/\/ror\.org\//, '');
  }

  async function loadRors(): Promise<void> {
    rors = await ApiC.getJson('teams/current/rors');
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
      await ApiC.post(`teams/current/rors/${encodeURIComponent(ror)}`);
      rorInput = '';
      await loadRors();
    } finally {
      isSubmitting = false;
    }
  }

  async function deleteRor(ror: string): Promise<void> {
    await ApiC.delete(`teams/current/rors/${encodeURIComponent(ror)}`);
    await loadRors();
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
  <h3>Existing ROR associations</h3>

  {#if isInitialLoading}
    <p>Loading…</p>
  {:else}
    <table class='table' aria-describedby='existingRors' data-table-sort='true'>
      <thead>
        <tr>
          <th scope='col'>ROR</th>
          <th scope='col'>Organization name</th>
          <th scope='col'>Association date</th>
          <th scope='col'><span class='sr-only'>Action</span></th>
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

            <td data-label='Organization name'>
              {organizationNames[rorAssociation.ror] ?? rorAssociation.ror}
            </td>

            <td data-label='Association date'>
              <span class='relative-moment' title={rorAssociation.created_at}></span>
            </td>

            <td>
              <span class='sr-only'>Action</span>
              <button
                type='button'
                class='btn btn-danger-ghost'
                title='Delete'
                aria-label='Delete'
                on:click={() => deleteRor(rorAssociation.ror)}
              >
                <i class='fas fa-trash-alt fa-fw'></i>
              </button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  {/if}

  <form on:submit|preventDefault={addRor}>
    <label for='ror_input'>Add Research Organization Registry (ROR) identifier</label>

    <div class='input-group'>
      <div class='input-group-prepend'>
        <span class='input-group-text'>ROR</span>
      </div>

      <input
        bind:value={rorInput}
        name='ror'
        id='ror_input'
        class='form-control col-md-4'
        title='Enter a valid 9-character ROR ID, for example 04t0gwh46 or https://ror.org/04t0gwh46'
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
          title='Add'
          disabled={isSubmitting}
        >
          Add
        </button>
      </div>
    </div>
  </form>
</div>
