<script lang='ts'>
  import { ApiC } from '../api';
  import { onMount } from 'svelte';
  import i18next from '../i18n';
  import type { Writable } from 'svelte/store';

  const t = i18next.t.bind(i18next);

  type EntityType = 'experiments' | 'items' | 'experiments_templates' | 'items_types';

  interface EntityTag {
  tag: string;
  id: number;
  is_favorite: boolean;
}

  interface EntityListItem {
    id: number;
    title: string | null;
    state?: number | string | null;
    category?: string | null;
    category_title?: string | null;
    category_color?: string | null;
    status?: string | null;
    status_title?: string | null;
    status_color?: string | null;
    rating?: number | null;
    custom_id?: string | null;
    userid?: number | null;
    fullname?: string | null;
    team?: number | null;
    team_name?: string | null;
    timestamped?: boolean;
    next_step?: string | null;
    locked?: boolean;
    modified_at?: string | null;
    date?: string | null;
    created_at?: string | null;
    is_pinned?: boolean;
    tags_decoded?: EntityTag[] | null;
  }

  let {
    entityType = 'experiments',
    toPage,
    limit = 15,
    searchQuery,
    currentUserId = null,
    currentTeam = null,
    isAdmin = false,
    isAnon = false,
    archivedStateValue = 2,
    deletedStateValue = 3,
  } = $props<{
    entityType?: EntityType;
    toPage?: string;
    limit?: number;
    searchQuery: Writable<string>;
    currentUserId?: number | null;
    currentTeam?: number | null;
    isAdmin?: boolean;
    isAnon?: boolean;
    archivedStateValue?: number | string;
    deletedStateValue?: number | string;
  }>();

  let entities = $state<EntityListItem[]>([]);
  let isLoading = $state(true);
  let error = $state('');
  let requestSeq = 0;

  const resolvedToPage = $derived(toPage ?? getDefaultPage(entityType));

  let urlVersion = $state(0);

  function bumpUrlVersion(): void {
  urlVersion += 1;
}

function getCurrentUrlTags(): string[] {
  return new URL(window.location.href).searchParams
    .getAll('tags[]')
    .map(tag => tag.trim())
    .filter(Boolean);
}

function setSingleTagInUrl(tag: string): void {
  const url = new URL(window.location.href);
  url.searchParams.delete('tags[]');
  url.searchParams.append('tags[]', tag);

  window.history.replaceState({}, '', url.toString());
  bumpUrlVersion();
}

function handleTagClick(event: MouseEvent, tag: string): void {
  if (
    event.button !== 0 ||
    event.metaKey ||
    event.ctrlKey ||
    event.shiftKey ||
    event.altKey
  ) {
    return;
  }

  event.preventDefault();
  setSingleTagInUrl(tag);
}

  $effect(() => {
    urlVersion;

    const currentType = entityType;
    const currentLimit = limit;
    const currentQ = $searchQuery.trim();
    const currentTags = getCurrentUrlTags();

    void loadEntities(currentType, currentLimit, currentQ, currentTags);
  });

  async function loadEntities(
    currentType: EntityType,
    currentLimit: number,
    currentQ: string,
    currentTags: string[],
  ): Promise<void> {
    const seq = ++requestSeq;
    isLoading = true;
    error = '';

    const previousNotifOnError = ApiC.notifOnError;
    ApiC.notifOnError = false;

    try {
      const params: Record<string, string | number | string[]> = {
        limit: currentLimit,
      };

      if (currentQ.length > 0) {
        params.q = currentQ;
      }

      if (currentTags.length > 0) {
        params['tags[]'] = currentTags;
      }

      const payload = await ApiC.getJson(currentType, params) as EntityListItem[] | { items?: EntityListItem[] };

      if (seq !== requestSeq) {
        return;
      }

      entities = Array.isArray(payload) ? payload : (payload.items ?? []);
    } catch (err) {
      if (seq !== requestSeq) {
        return;
      }

      const apiError = err as Error & { status?: number };

      if (apiError.status === 400) {
        error = '';
        return;
      }

      error = apiError.message || t('Failed to load entries');
    } finally {
      ApiC.notifOnError = previousNotifOnError;

      if (seq === requestSeq) {
        isLoading = false;
      }
    }
  }

  function getDefaultPage(type: EntityType): string {
    return type === 'items' || type === 'items_types' ? 'database.php' : 'experiments.php';
  }

  function isTemplateType(type: EntityType): boolean {
    return type === 'experiments_templates' || type === 'items_types';
  }

  function getCreateLabel(type: EntityType): string {
    return type === 'items_types'
      ? t('Create resource from template')
      : t('Create experiment from template');
  }

  function getCreateDataType(type: EntityType): 'experiments' | 'database' {
    return type === 'experiments_templates' ? 'experiments' : 'database';
  }

  function getStatusDateLabel(type: EntityType): string {
    return isTemplateType(type) ? t('Created on') : t('Started on');
  }

  function formatDate(value: string | null | undefined): string {
    if (!value) {
      return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return value.slice(0, 10);
    }

    return date.toISOString().slice(0, 10);
  }

  function firstNextStep(value: string | null | undefined): string {
    if (!value) {
      return '';
    }

    return value.split('|')[0]?.trim() ?? '';
  }

  function getBodyId(entity: EntityListItem, index: number): string {
    return `entity-body-${entity.id}-${index}`;
  }

  function getLeftColor(entity: EntityListItem): string {
    return entity.category_color || 'bdbdbd';
  }

  function isArchivedState(state: string | number | null | undefined): boolean {
    return state === archivedStateValue || state === 'archived';
  }

  function isDeletedState(state: string | number | null | undefined): boolean {
    return state === deletedStateValue || state === 'deleted';
  }

  function canEditEntity(entity: EntityListItem): boolean {
    return entity.userid === currentUserId || entityType === 'items' || isAdmin;
  }

  function getDisplayTitle(entity: EntityListItem): string {
    return entity.title || t('Untitled');
  }

  onMount(() => {
  const handlePopState = (): void => {
    bumpUrlVersion();
  };

  window.addEventListener('popstate', handlePopState);

  return () => {
    window.removeEventListener('popstate', handlePopState);
  };
});
</script>

{#if isLoading && entities.length === 0}
  <div>{t('Loading...')}</div>
{:else if error && entities.length === 0}
  <div class='alert alert-danger'>{error}</div>
{:else}
  <div class='d-flex flex-column' id='itemList'>
    {#each entities as entity, index (`${entity.id}-${index}`)}
      {@const template = isTemplateType(entityType)}
      {@const bodyId = getBodyId(entity, index)}
      {@const statusDateLabel = getStatusDateLabel(entityType)}
      {@const dateValue = template ? entity.created_at : entity.date}
      {@const nextStep = firstNextStep(entity.next_step)}
      {@const createLabel = getCreateLabel(entityType)}
      {@const createDataType = getCreateDataType(entityType)}

      <section
        class={`entity ${template ? 'entity-template' : ''} pl-3 py-3 d-flex`}
        id={`parent_${bodyId}`}
        style={`--left-color: #${getLeftColor(entity)};`}
        aria-label={`${t('Entry')}_${bodyId}`}
      >
        <div class='d-flex align-items-start mt-1'>
          <input
            autocomplete='off'
            type='checkbox'
            data-action='checkbox-entity'
            data-id={entity.id}
            data-randomid={bodyId}
            data-type={entityType}
            data-state={entity.state ?? ''}
            aria-label={t('Select')}
            class='mr-3'
          />
        </div>

        <div class='align-self-center'>
          <div>
            {#if entity.timestamped}
              <i
                style={`color:#${getLeftColor(entity)}`}
                class='far fa-calendar-check fa-fw'
              ></i>
            {/if}

            {#if isArchivedState(entity.state)}
              <i class='fas fa-box-archive fa-fw'></i>
            {/if}

            {#if isDeletedState(entity.state)}
              <i class='fas fa-ban fa-fw color-danger'></i>
            {/if}

            {#if entity.category}
              <button
                class='btn catstat-btn category-btn mr-1'
                type='button'
                style={`--bg: #${getLeftColor(entity)};line-height:normal;`}
                data-action='add-query-filter'
                data-key='category'
                data-value={entity.category}
              >
                {entity.category_title}
              </button>
            {/if}

            {#if entity.status}
              <button
                class='btn catstat-btn mr-1 status-btn bg-firstlevel'
                type='button'
                data-action='add-query-filter'
                data-key='status'
                data-value={entity.status}
                style='line-height:normal;'
              >
                <i
                  class='fas fa-circle fa-fw'
                  style={`--bg: #${entity.status_color || 'bdbdbd'}`}
                ></i>
                {entity.status_title}
              </button>
            {/if}

            {#if (entity.rating ?? 0) > 0}
              <span class='rating-show rounded p-1 font-weight-bold'>
                <i class='fas fa-star mr-1' title='☻'></i>{entity.rating}
              </span>
            {/if}
          </div>

          <div class='d-flex title flex-nowrap my-2'>
            {#if entity.custom_id}
              <span class='color-medium mr-1 text-nowrap' title={t('Custom ID')}>
                {entity.custom_id}
              </span>
            {/if}

            <a href={`${resolvedToPage}?mode=view&id=${entity.id}`}>
              {getDisplayTitle(entity)}
            </a>
          </div>

          <div class='owner'>
            {#if entity.userid != null && currentUserId !== entity.userid && !isAnon}
              {t('by')} <a href={`?owner=${entity.userid}`}>{entity.fullname}</a>
            {/if}

            {#if entity.team != null && currentTeam !== entity.team && !isAnon && entity.team_name}
              <span class='badge badge-pill badge-light'>{entity.team_name}</span>
            {/if}
          </div>

          {#if nextStep}
            <p class='item-next my-2'>
              <span class='next-step-text'>{t('Next step')}:</span>
              <span class='item-next-step'> {nextStep}</span>
            </p>
          {/if}


          <p class='my-2'>
            {#if entity.tags_decoded.length > 0}
              <span class='d-inline-flex flex-wrap'>
                <i class='fas fa-tags mr-1 fa-fw'></i>

                {#each entity.tags_decoded as tag, tagIndex (`${entity.id}-${tag.id ?? tag.tag}-${tagIndex}`)}
                  <a
                    class={`tag mathjax-ignore margin-1px ${tag.is_favorite === 1 ? 'favorite' : ''}`}
                    href={`${resolvedToPage}?mode=show&tags%5B%5D=${encodeURIComponent(tag.tag)}`}
                    onclick={event => handleTagClick(event, tag.tag)}
                  >
                    {tag.tag}
                  </a>
                {/each}
              </span>
            {/if}
          </p>

          <div class='d-flex flex-row'>
            {#if template}
              <a
                data-action='create-entity'
                data-type={createDataType}
                data-tplid={entity.id}
                href='#'
                class='btn btn-primary left-icon mr-1 lh-normal p-2 border-0 hl-hover-gray'
                title={createLabel}
                aria-label={createLabel}
              >
                <i class='fas fa-file-circle-plus fa-fw'></i>
              </a>
            {/if}

            {#if entity.locked}
              <div class='btn left-icon mr-1 lh-normal p-2 border-0 bgnd-gray disabled'>
                <i class='fas fa-lock fa-fw'></i>
              </div>
            {:else if isDeletedState(entity.state)}
              <div
                class='btn btn-secondary mr-1 lh-normal p-2 border-0'
                data-action='restore-entity-showmode'
                title={t('Restore entry')}
                data-endpoint={entityType}
                data-id={entity.id}
              >
                <i class='fas fa-trash-can-arrow-up fa-fw'></i>
              </div>
            {:else if canEditEntity(entity)}
              <a
                href={`${resolvedToPage}?mode=edit&id=${entity.id}`}
                class='btn btn-secondary left-icon mr-1 lh-normal p-2 border-0'
                title={t('Edit')}
                aria-label={t('Edit')}
              >
                <i class='fas fa-pencil fa-fw'></i>
              </a>
            {:else}
              <a
                href={`${resolvedToPage}?mode=view&id=${entity.id}`}
                class='btn btn-secondary left-icon mr-1 lh-normal p-2 border-0'
                title={t('View')}
                aria-label={t('View')}
              >
                <i class='fas fa-eye fa-fw'></i>
              </a>
            {/if}

            <button
              type='button'
              class='btn btn-neutral mr-2 lh-normal'
              data-type={entityType}
              data-id={entity.id}
              data-opened-icon='fa-caret-down'
              data-closed-icon='fa-caret-right'
              data-randid={bodyId}
              data-action='toggle-body'
              title={t('Toggle content')}
              aria-label={t('Toggle content')}
            >
              <i class='fas fa-caret-right color-medium'></i>
            </button>

            <div class='d-flex flex-column color-medium small'>
              <span class='item-date'>
                {statusDateLabel} {formatDate(dateValue)}
              </span>
              <span class='item-date'>
                {t('Last modified')}
                <span title={entity.modified_at ?? ''} class='relative-moment'></span>
              </span>
            </div>
          </div>

          <div hidden id={bodyId} style='overflow:auto;margin: 10px 0 0 20px'>
            <div></div>
          </div>
        </div>

        <div class='d-flex justify-content-end ml-auto text-nowrap'>
          <div>
            <!-- toggle-pin intentionally left out for now -->
          </div>
        </div>
      </section>
    {/each}
  </div>
{/if}
