<script lang="ts">
  import { onMount, tick } from 'svelte';
  import { ApiC } from '../api';
  import i18next from '../i18n';
  import { Model } from '../interfaces';
  import { Malle } from '@deltablot/malle';
  import { makeSortableGreatAgain, toRelative } from '../misc';

  const t = i18next.t.bind(i18next);
  let locale = 'en-gb';

  let malleable: Malle | null = null;
  const relative = (date: string): string => toRelative(date, locale);

  function setupMalle(): void {
    malleable = new Malle({
      before: (original) => original.classList.contains('editable'),
      inputClasses: ['form-control'],
      fun: async (value, original) => {
        const id = original.dataset.id;
        if (!id) {
          throw new Error('Missing todo id on editable element');
        }

        const resp = await ApiC.patch(`${Model.Todolist}/${id}`, { content: value });
        const json = await resp.json();
        return json.body;
      },
      returnedValueIsTrustedHtml: false,
      listenOn: '.todoItem',
      tooltip: t('click-to-edit'),
    });

    malleable.listen();
  }

  type Todo = {
    id: number;
    body: string;
    creation_time: string;
  };

  let items: Todo[] = [];
  let draft = '';

  async function create(): Promise<void> {
    const content = draft.trim();
    if (!content) return;

    ApiC.notifOnSaved = false;
    await ApiC.post(Model.Todolist, { content });
    ApiC.notifOnSaved = true;
    draft = '';
    await load();
  }

  async function destroy(id: number): Promise<void> {
    await ApiC.delete(`${Model.Todolist}/${id}`);
    items = items.filter(item => item.id !== id);
  }

  async function load(): Promise<void> {
    items = await ApiC.getJson(Model.Todolist) as Todo[];
    await tick(); // wait until {#each} has rendered
    setupMalle();
    makeSortableGreatAgain();
  }

  onMount(() => {
    const prefs = document.getElementById('user-prefs');
    locale = prefs?.dataset?.jslang || 'en-gb';
    void load();
  });
</script>

<div class='input-group mb-2'>
  <input
    class='form-control'
    bind:value={draft}
    on:keydown={(e) => e.key === 'Enter' && create()}
    placeholder={t('add-task')}
  />
  <div class='input-group-append'>
    <button type='button' class='btn btn-primary' on:click={create} aria-label={t('add')}>
      <i class='fas fa-plus fa-fw' title={t('add')}></i>
    </button>
  </div>
</div>

{#if items.length === 0}
  <p class='mb-0'>{t('no-tasks-yet')}</p>
{:else}
  <ul class='list-group color-medium sortable' data-axis='y' data-table='todolist'>
    {#each items as item (item.id)}
      <li class='list-group-item d-flex justify-content-between align-items-center' id='todoItem_{item.id}'>
        <div class='d-flex align-items-start flex-grow-1 mr-3'>
          <span class='draggable sortableHandle mr-2'>
            <i class='fas fa-grip-vertical fa-fw'></i>
          </span>

          <div class='d-flex flex-column flex-grow-1'>
            <span class='editable todoItem' data-id={item.id}>{item.body}</span>
            <div class='relative-moment small text-muted' title={item.creation_time}>
              {relative(item.creation_time)}
            </div>
          </div>
        </div>
        <button
          type='button'
          class='btn btn-sm btn-ghost'
          on:click={() => destroy(item.id)}
        >
          {t('done')}
        </button>
      </li>
    {/each}
  </ul>
{/if}
