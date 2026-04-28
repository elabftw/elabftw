<script lang='ts'>
  /**
   * @author Nicolas CARPi / Deltablot
   * @copyright 2026 Nicolas CARPi
   * @see https://www.elabftw.net Official website
   * @license AGPL-3.0
   * @package elabftw
   */
  import { onMount } from 'svelte';
  import type { Writable } from 'svelte/store';
  import { ApiC } from '../api';
  import i18next from '../i18n';
  import { State, type StateValue } from '../state.auto';

  // this is only used in the template, so it is stripped by svelte during compilation
  // copy it so it stays available
  const EntityState = State;

  type EntityType = 'experiments' | 'items' | 'experiments_templates' | 'items_types';

  interface EntityTag {
    tag: string;
    id: number;
    is_favorite: boolean;
  }

  interface EntityListItem {
    id: number;
    title: string | null;
    state: StateValue;
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

  const t = i18next.t.bind(i18next);

  let {
    entityType = 'experiments',
    limit = 15,
    searchQuery,
    currentUserId = null,
    currentTeam = null,
    isAdmin = false,
    isAnon = false,
    onInitialLoadDone,
  } = $props<{
    entityType?: EntityType;
    limit?: number;
    searchQuery: Writable<string>;
    currentUserId?: number | null;
    currentTeam?: number | null;
    isAdmin?: boolean;
    isAnon?: boolean;
    onInitialLoadDone?: () => void;
  }>();

  let hasReportedInitialLoad = false;
  let entities = $state<EntityListItem[]>([]);
  let error = $state('');
  let requestSeq = 0;

  let urlVersion = $state(0);
  let isLoading = $state(false);
  let isLoadingMore = $state(false);
  let hasMore = $state(true);
  let offset = $state(0);
  let sentinelEl: HTMLDivElement | null = null;
  let currentQueryKey = '';

  function bumpUrlVersion(): void {
    urlVersion += 1;
  }


  function setOwnerInUrl(ownerId: number): void {
    const url = new URL(window.location.href);
    url.searchParams.set('owner', String(ownerId));

    window.history.replaceState({}, '', url.toString());
    bumpUrlVersion();
  }

  function setOwnerInActiveFilters(fullname: string): void {
    const filter = document.createElement('div');
    filter.dataset.param = 'owner';
    filter.classList.add('active-filter-badge');
    filter.textContent = `${t('owner')}: ${fullname}`;
    const activeFiltersDiv = document.getElementById('activeFiltersDiv');
    clearInActiveFilters('owner');
    activeFiltersDiv.appendChild(filter);
  }

  function handleOwnerClick(event: MouseEvent, ownerId: number, fullname: string): void {
    event.preventDefault();
    // allow unselecting a previously added filter
    if (getCurrentUrlParam('owner') === String(ownerId)) {
      clearInUrl('owner');
      clearInActiveFilters('owner');
      return;
    }
    setOwnerInUrl(ownerId);
    setOwnerInActiveFilters(fullname);
  }

    function getCurrentUrlParam(param: string): string {
      return new URL(window.location.href).searchParams.get(param)?.trim() ?? '';
    }

    function getCurrentUrlTags(): string[] {
    return new URL(window.location.href).searchParams
      .getAll('tags[]')
      .map(tag => tag.trim())
      .filter(Boolean);
  }

  function clearInUrl(param: string): void {
    const url = new URL(window.location.href);
    url.searchParams.delete(param);
    window.history.replaceState({}, '', url.toString());
    bumpUrlVersion();
  }

  function clearInActiveFilters(param: string): void {
    const activeFiltersDiv = document.getElementById('activeFiltersDiv');
    activeFiltersDiv.querySelector(`[data-param="${param}"]`)?.remove();
  }

  function setSingleTagInUrl(tag: string): void {
    const url = new URL(window.location.href);
    url.searchParams.delete('tags[]');
    url.searchParams.append('tags[]', tag);

    window.history.replaceState({}, '', url.toString());
    bumpUrlVersion();
  }

  function handleTagClick(event: MouseEvent, tag: string): void {
    event.preventDefault();

    if (getCurrentUrlTags().includes(tag)) {
      clearInUrl('tags[]');
      return;
    }

    setSingleTagInUrl(tag);
  }

  let reloadVersion = $state(0);

  function bumpReloadVersion(): void {
    reloadVersion += 1;
  }

  $effect(() => {
    urlVersion;
    reloadVersion;

    const currentType = entityType;
    const currentLimit = limit;
    const currentQ = $searchQuery.trim();
    const currentCategory = getCurrentUrlParam('category');
    const currentStatus = getCurrentUrlParam('status');
    const currentOwner = getCurrentUrlParam('owner');
    const currentTags = getCurrentUrlTags();

     const nextQueryKey = JSON.stringify([
      currentType,
      currentLimit,
      currentQ,
      currentStatus,
      currentCategory,
      currentOwner,
      currentTags,
      reloadVersion,
    ]);

    if (nextQueryKey === currentQueryKey) {
      console.log('aborting');
      return;
    }

    currentQueryKey = nextQueryKey;
    offset = 0;
    hasMore = true;

    void loadEntities(
      currentType,
      currentLimit,
      currentQ,
      currentCategory,
      currentStatus,
      currentOwner,
      currentTags,
      offset,
      true,
    );
  });

  async function loadEntities(
    currentType: EntityType,
    currentLimit: number,
    currentQ: string,
    currentCategory: string,
    currentStatus: string,
    currentOwner: string,
    currentTags: string[],
    currentOffset: number,
    replace: boolean,
  ): Promise<void> {
    const seq = ++requestSeq;
    error = '';

    if (replace) {
      isLoading = true;
    } else {
      isLoadingMore = true;
    }

    const previousNotifOnError = ApiC.notifOnError;
    ApiC.notifOnError = false;

    try {
      const params: Record<string, string | number | string[]> = {
        limit: currentLimit,
        offset: currentOffset,
      };

      if (currentQ.length > 0) {
        params.q = currentQ;
      }

      if (currentCategory.length > 0) {
        params['category'] = currentCategory;
      }
      if (currentStatus.length > 0) {
        params['status'] = currentStatus;
      }
      if (currentOwner.length > 0) {
        params['owner'] = currentOwner;
      }

      if (currentTags.length > 0) {
        params['tags[]'] = currentTags;
      }

      // fetch entries
      const payload = await ApiC.getJson(currentType, params) as EntityListItem[] | { items?: EntityListItem[] };

      if (seq !== requestSeq) {
        return;
      }

      const nextEntities = Array.isArray(payload) ? payload : (payload.items ?? []);

      if (replace) {
        entities = nextEntities;
      } else {
        const existingIds = new Set(entities.map(item => item.id));
        entities = [
          ...entities,
          ...nextEntities.filter(item => !existingIds.has(item.id)),
        ];
      }

      hasMore = nextEntities.length === currentLimit;
    } catch (err) {
      if (seq !== requestSeq) {
        return;
      }

      // example: type "title:" in search bar, it's incomplete, we ignore the error
      const apiError = err as Error & { status?: number };

      if (apiError.status === 400) {
        error = '';
        return;
      }

      error = apiError.message || t('error');
    } finally {
      ApiC.notifOnError = previousNotifOnError;

      if (seq === requestSeq) {
        if (replace) {
          isLoading = false;
        } else {
          isLoadingMore = false;
        }

        if (!hasReportedInitialLoad) {
          hasReportedInitialLoad = true;
          onInitialLoadDone?.();
        }
      }
    }
  }

  function isTemplateType(type: EntityType): boolean {
    return type === 'experiments_templates' || type === 'items_types';
  }

  function getCreateLabel(type: EntityType): string {
    return type === 'items_types'
      ? t('create-resource-from-template')
      : t('create-experiment-from-template');
  }

  function getCreateDataType(type: EntityType): 'experiments' | 'database' {
    return type === 'experiments_templates' ? 'experiments' : 'database';
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

  function getLeftColor(entity: EntityListItem): string {
    return entity.category_color || 'bdbdbd';
  }

  function canEditEntity(entity: EntityListItem): boolean {
    return entity.userid === currentUserId || entityType === 'items' || isAdmin;
  }

  $effect(() => {
    if (!sentinelEl) {
      return;
    }

    const observer = new IntersectionObserver(
      entries => {
        const entry = entries[0];

        if (!entry?.isIntersecting) {
          return;
        }

        if (isLoading || isLoadingMore || !hasMore || entities.length === 0) {
          return;
        }

        const currentType = entityType;
        const currentLimit = limit;
        const currentQ = $searchQuery.trim();
        const currentCategory = getCurrentUrlParam('category');
        const currentStatus = getCurrentUrlParam('status');
        const currentOwner = getCurrentUrlParam('owner');
        const currentTags = getCurrentUrlTags();
        const nextOffset = entities.length;

        void loadEntities(
          currentType,
          currentLimit,
          currentQ,
          currentCategory,
          currentStatus,
          currentOwner,
          currentTags,
          nextOffset,
          false,
        );
      },
      {
        rootMargin: '600px 0px',
      },
    );

    observer.observe(sentinelEl);

    return () => {
      observer.disconnect();
    };
  });

  onMount(() => {
    window.addEventListener('popstate', bumpUrlVersion);
    window.addEventListener('entity-filters-changed', bumpReloadVersion);

    return () => {
      window.removeEventListener('popstate', bumpUrlVersion);
      window.removeEventListener('entity-filters-changed', bumpReloadVersion);
    };
  });
</script>

{#if error && entities.length === 0}
  <div class='alert alert-danger'>{error}</div>
{:else}
  <div class='d-flex flex-column' id='itemList'>
    {#each entities as entity, index (`${entity.id}-${index}`)}
      {@const template = isTemplateType(entityType)}
      {@const bodyId = `entity-body-${entity.id}-${index}`}
      {@const statusDateLabel = isTemplateType(entityType) ? t('created-on') : t('started-on')}
      {@const dateValue = template ? entity.created_at : entity.date}
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

            {#if entity.state === EntityState.Archived}
              <i class='fas fa-box-archive fa-fw'></i>
            {/if}

            {#if entity.state === EntityState.Deleted}
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

            <a href={`?mode=view&id=${entity.id}`}>
              {entity.title || t('entity-default-title')}
            </a>
          </div>

          <div class='owner'>
            {#if entity.userid != null && currentUserId !== entity.userid && !isAnon}
              {t('by')}
              <a
                class={'owner'}
                href={`?owner=${entity.userid}`}
                onclick={event => handleOwnerClick(event, entity.userid, entity.fullname)}
              >
                {entity.fullname}
              </a>
            {/if}

            {#if entity.team != null && currentTeam !== entity.team && !isAnon && entity.team_name}
              <span class='badge badge-pill badge-light'>{entity.team_name}</span>
            {/if}
          </div>

          {#if entity.next_step}
            <p class='item-next my-2'>
              <span class='next-step-text'>{t('Next step')}:</span>
              <span class='item-next-step'> {entity.next_step}</span>
            </p>
          {/if}


          <p class='my-2'>
            {#if (entity.tags_decoded?.length ?? 0) > 0}
              <span class='d-inline-flex flex-wrap'>
                <i class='fas fa-tags mr-1 fa-fw'></i>

                {#each entity.tags_decoded ?? [] as tag, tagIndex (`${entity.id}-${tag.id}-${tagIndex}`)}
                  <a
                    class={`tag mathjax-ignore margin-1px ${tag.is_favorite ? 'favorite' : ''}`}
                    href={`?mode=show&tags%5B%5D=${encodeURIComponent(tag.tag)}`}
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
            {:else if entity.state === EntityState.Deleted}
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
                href={`?mode=edit&id=${entity.id}`}
                class='btn btn-secondary left-icon mr-1 lh-normal p-2 border-0'
                title={t('Edit')}
                aria-label={t('Edit')}
              >
                <i class='fas fa-pencil fa-fw'></i>
              </a>
            {:else}
              <a
                href={`?mode=view&id=${entity.id}`}
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
            <button
              type='button'
              title={t('toggle-pin')}
              aria-label={t('toggle-pin')}
              data-action='toggle-pin'
              data-id={entity.id}
              class={`btn ${entity.is_pinned ? 'bgnd-gray' : 'hl-hover-gray'} p-2 mr-2 lh-normal border-0`}
            >
              <i class={`fas fa-thumbtack ${!entity.is_pinned ? 'color-weak' : ''} fa-fw`}></i>
            </button>
          </div>
        </div>
      </section>
    {/each}
  {#if hasMore}
      <div bind:this={sentinelEl} class='py-3 text-center color-medium'>
        {#if isLoadingMore}
          {t('Loading...')}
        {/if}
      </div>
    {/if}
  </div>
{/if}
