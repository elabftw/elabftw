/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { get } from 'svelte/store';
import type { Writable } from 'svelte/store';
import $ from 'jquery';
import i18next from './i18n';
import { ApiC } from './api';
import { Model } from './interfaces';
import { TomSelect } from './misc';

type TomSelectOptionLike = Record<string, unknown> & {
  $option?: Element | null;
};

type ToggleableTomSelect = TomSelect & {
  [key: string]: unknown;
};

type TomSelectWithAllOptions = ToggleableTomSelect & {
  _allOptions?: TomSelectOptionLike[];
};

type TeamScopedTomSelect = TomSelectWithAllOptions & {
  _showOtherTeams?: boolean;
};

type OwnerTomSelect = TeamScopedTomSelect & {
  _showArchived?: boolean;
};

type ActiveFilterControl = {
  control: TeamScopedTomSelect;
  param: string;
  title: string;
  // its options can come from other teams, see syncOtherTeamsScope()
  teamScoped?: boolean;
};

type EntityFilterParam = 'owner' | 'category' | 'status';

type EntityFilterRequestedDetail = {
  param: EntityFilterParam;
  value: string;
  label?: string | null;
};

type UserSearchResult = {
  userid: number;
  fullname: string;
};

// what the filters need from the show page to read and write the URL parameters
export type EntityFilterStore = {
  entityFilters: Writable<URLSearchParams>;
  setEntityFilterParam: (param: string, value: string) => void;
  setEntityFilterParamValues: (param: string, values: string[]) => void;
  getEntityFilterParamValues: (param: string) => string[];
};

const bound = new WeakSet<Element>();

// Owner, Category, Status and Tags filters of the show page
export function initTomSelectFilters({
  entityFilters,
  setEntityFilterParam,
  setEntityFilterParamValues,
  getEntityFilterParamValues,
}: EntityFilterStore): void {
  const activeFilters = document.getElementById('activeFiltersDiv');
  const filterControls: ActiveFilterControl[] = [];
  // scope that was set before a value from another team got selected in a filter, null when none is selected
  let scopeBeforeOtherTeams: string | null = null;

  if (document.getElementById('filterOwner')) {
    const control = new TomSelect('#filterOwner', {
      dropdownParent: '#filterOwnerMenu',
      maxOptions: 512,
      plugins: {
        dropdown_header: {
          title: i18next.t('users'),
          html: buildDropdownHeaderHtml([
            { checkboxClass: 'ts-toggle-archived', label: i18next.t('show-archived') },
          ]),
        },
        remove_button: {},
      },

      onInitialize(this: OwnerTomSelect) {
        initTeamOptions(this);
        this._showArchived = false;
        applyToggleFilter(this, '_showArchived', isArchivedOption);
        addOtherTeamsOption(this, i18next.t('show-other-teams-owners'), () => {
          this._showOtherTeams = true;
          // search right away with what is already typed
          const query = this.inputValue();
          if (this.settings.shouldLoad.call(this, query)) {
            this.load(query);
          }
        });
      },

      // users outside of the team are not rendered in the page, so search them on the instance with the API
      // only once other teams were requested, and only with a few characters, to avoid listing every user at once
      shouldLoad(this: OwnerTomSelect, query) {
        return !!this._showOtherTeams && query.trim().length >= 2;
      },

      load(this: OwnerTomSelect, query, callback) {
        // team members are already known (archived ones may just be hidden), don't add them twice
        const knownIds = new Set((this._allOptions ?? []).map(opt => String(opt.value)));
        ApiC.getJson<UserSearchResult[]>(Model.User, { q: query.trim() })
          .then(users => {
            callback(users
              .filter(user => !knownIds.has(String(user.userid)) && !this.options[String(user.userid)])
              .map(user => ({
                value: String(user.userid),
                text: user.fullname,
                isOtherTeam: true,
              })), []);
          })
          .catch(() => {
            // TomSelect marks the query as loaded before calling us, forget it so typing it again retries
            delete this.loadedSearches[query];
            callback([], []);
          });
      },
      clearAfterSelect: true,

      onChange(value: string | string[] | null | undefined) {
        onTeamScopedFilterChange('owner', value);
      },

      render: {
        option: renderOwner,
        item: renderOwner,
      },
    }) as OwnerTomSelect;

    registerFilterControl({
      control,
      param: 'owner',
      title: 'Owner',
      teamScoped: true,
      dropdownId: 'filterOwnerDropdown',
      menuId: 'filterOwnerMenu',
    });

    // opening the Bootstrap dropdown opens the control too, so this also covers it
    control.on('dropdown_open', () => {
      bindDropdownToggle(control, '.ts-toggle-archived', '_showArchived', () => {
        applyToggleFilter(control, '_showArchived', isArchivedOption);
        control.open();
      });
    });
  }

  function hydrateTomSelectFromUrl(
    control: TomSelectWithAllOptions,
    param: string,
  ): void {
    getEntityFilterParamValues(param).forEach(value => {
      ensureTomSelectOption(control, value, value);

      if (!control.items.map(String).includes(value)) {
        control.addItem(value, true);
      }
    });
  }

  function buildDropdownHeaderHtml(toggles: { checkboxClass: string; label: string }[] = []) {
    const togglesHtml = toggles.map(({ checkboxClass, label }, index) => `
          <label style="${index === 0 ? 'margin-left:auto; ' : ''}display:flex; align-items:center; gap:6px; font-weight:normal;">
            <input type="checkbox" class="${checkboxClass}">
            ${label}
          </label>`).join('');
    return (data: { title: string; headerClass: string; titleRowClass: string }) => `
      <div class="${data.headerClass}">
        <div class="${data.titleRowClass}" style="display:flex; align-items:center; gap:12px;">
          <div>${data.title}</div>${togglesHtml}
        </div>
      </div>
    `;
  }

  // keep a filter control in sync with the Bootstrap dropdown that holds it
  function bindFilterDropdown(control: TomSelect, dropdownId: string, menuId: string): void {
    const dropdownRoot = document.getElementById(dropdownId);
    const menu = document.getElementById(menuId);

    if (dropdownRoot) {
      $(dropdownRoot).on('shown.bs.dropdown', () => {
        control.open();
        window.requestAnimationFrame(() => {
          (menu?.querySelector('.ts-control input') as HTMLInputElement | null)?.focus();
        });
      });

      $(dropdownRoot).on('hide.bs.dropdown', () => {
        control.close();
        control.blur();
      });
    }

    // clicking inside the control must not close the Bootstrap dropdown
    if (menu) {
      $(menu).on('click', event => event.stopPropagation());
    }
  }

  // every filter control shows its selection as chips, starts from the URL and lives in a Bootstrap dropdown
  function registerFilterControl({ dropdownId, menuId, ...entry }: ActiveFilterControl & {
    dropdownId: string;
    menuId: string;
  }): void {
    filterControls.push(entry);
    hydrateTomSelectFromUrl(entry.control, entry.param);
    renderActiveFilters();
    bindFilterDropdown(entry.control, dropdownId, menuId);
  }

  function onTeamScopedFilterChange(param: EntityFilterParam, value: string | string[] | null | undefined): void {
    // Tom Select gives an array for multi-select; normalize just in case
    const selected = Array.isArray(value) ? value : value ? [value] : [];
    setEntityFilterParam(param, selected.join(','));
    syncOtherTeamsScope();
    renderActiveFilters();
  }

  function renderOwner(data: TomSelectOptionLike, escape: (s: string) => string): string {
    const icon = isArchivedOption(data) ? '<i class="fas fa-box-archive mr-1"></i>' : '';
    return `<div>${icon}${escape(String(data.text ?? data.name ?? ''))}</div>`;
  }

  // read data-current-team once, so options from the page and users found with the API carry the same isOtherTeam flag
  function initTeamOptions(control: TeamScopedTomSelect): void {
    control._allOptions = Object.values(control.options) as TomSelectOptionLike[];
    for (const opt of control._allOptions) {
      opt.isOtherTeam = !getOptionFlag(opt, 'data-current-team');
    }
    control._showOtherTeams = get(entityFilters).get('scope') === '3';
  }

  // highlighted entry after the options list, used once to bring in the options from other teams
  function addOtherTeamsOption(
    control: TeamScopedTomSelect,
    label: string,
    onActivate: () => void,
    hasOtherTeamsOptions = true,
  ): void {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ts-other-teams-option';
    btn.textContent = label;
    btn.hidden = !!control._showOtherTeams || !hasOtherTeamsOptions;
    // keep the focus in the search input so the dropdown stays open
    btn.addEventListener('mousedown', ev => ev.preventDefault());
    btn.addEventListener('click', ev => {
      ev.preventDefault();
      ev.stopPropagation();
      btn.hidden = true;
      onActivate();
      control.open();
    });
    control.dropdown.append(btn);
  }

  function getOptionFlag(opt: TomSelectOptionLike | undefined | null, attr: string): boolean {
    const el = opt?.$option as HTMLOptionElement | undefined;
    const raw = el?.getAttribute(attr) ?? '0';
    return raw === '1';
  }

  function isOtherTeamOption(opt: TomSelectOptionLike | undefined | null): boolean {
    return opt?.isOtherTeam === true;
  }

  // filtering with a value from another team only makes sense with the entries of all teams,
  // so switch to scope=3 while such a value is selected, and give back the previous scope after
  function syncOtherTeamsScope(): void {
    const hasOtherTeamsSelection = filterControls
      .filter(({ teamScoped }) => teamScoped)
      .some(({ control }) => control.items.some(value => isOtherTeamOption(control.options[value])));
    const scope = get(entityFilters).get('scope') ?? '';

    if (hasOtherTeamsSelection && scopeBeforeOtherTeams === null && scope !== '3') {
      scopeBeforeOtherTeams = scope;
      setEntityFilterParam('scope', '3');
      return;
    }
    if (!hasOtherTeamsSelection && scopeBeforeOtherTeams !== null) {
      // don't override a scope the user picked in the meantime
      if (scope === '3') {
        setEntityFilterParam('scope', scopeBeforeOtherTeams);
      }
      scopeBeforeOtherTeams = null;
    }
  }

  function isArchivedOption(opt: TomSelectOptionLike | undefined | null): boolean {
    return getOptionFlag(opt, 'data-is-archived');
  }

  function applyToggleFilter(
    control: TomSelectWithAllOptions,
    flagKey: string,
    hideWhenFlagFalse: (opt: TomSelectOptionLike) => boolean,
  ) {
    const selected = new Set(control.items.map(String));
    const flagOn = !!control[flagKey];

    if (flagOn) {
      for (const opt of control._allOptions ?? []) control.addOption(opt);
    } else {
      for (const opt of control._allOptions ?? []) {
        const id = String(opt[control.settings.valueField]);
        if (hideWhenFlagFalse(opt) && !selected.has(id)) {
          control.removeOption(id);
        }
      }
    }
    control.refreshOptions(false);
  }

  function applyTeamFilter(control: TeamScopedTomSelect) {
    applyToggleFilter(control, '_showOtherTeams', isOtherTeamOption);
  }

  // clicking an owner, category or status in the entities list toggles it in the matching filter
  window.addEventListener('entity-filter-requested', event => {
    const detail = (event as CustomEvent<EntityFilterRequestedDetail>).detail;
    if (!detail?.value) return;
    const entry = filterControls.find(({ param }) => param === detail.param);
    if (entry) {
      toggleTomSelectItem(entry.control, detail.value, detail.label ?? detail.value);
    }
  });

  function ensureTomSelectOption(
    control: TomSelectWithAllOptions,
    value: string,
    label: string,
  ): void {
    if (control.options[value]) {
      return;
    }

    const valueField = control.settings.valueField || 'value';
    const labelField = control.settings.labelField || 'text';

    const knownOption = (control._allOptions ?? []).find(option => (
      String(option[valueField]) === value
    ));

    if (knownOption) {
      control.addOption(knownOption);
    } else {
      control.addOption({
        [valueField]: value,
        [labelField]: label,
      });
    }

    control.refreshOptions(false);
  }

  function toggleTomSelectItem(
    control: TomSelectWithAllOptions,
    value: string,
    label: string,
  ): void {
    ensureTomSelectOption(control, value, label);

    if (control.items.map(String).includes(value)) {
      control.removeItem(value);
      return;
    }

    control.addItem(value);
  }

  function renderActiveFilters() {
    activeFilters.replaceChildren();

    for (const { control, title } of filterControls) {
      for (const value of control.items) {
        const option = control.options[value];
        const labelField = control.settings.labelField || 'text';
        const label = String(option?.[labelField] ?? value);

        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'btn btn-sm btn-ghost me-2 mb-2 mr-2';
        chip.setAttribute('aria-label', `Remove ${title}: ${label}`);

        const chipText = document.createElement('span');
        chipText.textContent = `${title}: ${label}`;

        const removeIcon = document.createElement('span');
        removeIcon.className = 'ms-1';
        removeIcon.setAttribute('aria-hidden', 'true');
        removeIcon.textContent = '×';

        chip.append(chipText, removeIcon);

        chip.addEventListener('click', () => {
          control.removeItem(value);
        });

        activeFilters.appendChild(chip);
      }
    }

    activeFilters.hidden = activeFilters.children.length === 0;
  }

  function bindDropdownToggle(
    control: ToggleableTomSelect,
    selector: string,
    flagKey: string,
    onToggle: (checked: boolean) => void,
  ) {
    const cb = control.dropdown?.querySelector(selector) as HTMLInputElement | null;
    if (!cb) return;

    // Always sync UI to current state when dropdown opens
    cb.checked = !!control[flagKey];

    // Bind once per checkbox element
    if (bound.has(cb)) return;
    bound.add(cb);

    cb.addEventListener('change', (ev) => {
      const checked = (ev.currentTarget as HTMLInputElement).checked;
      control[flagKey] = checked;
      onToggle(checked);
    });
  }

  function initTeamScopedFilter(cfg: {
    selectId: string;
    dropdownId: string;
    menuId: string;
    title: string;
    param: 'category' | 'status';
    otherTeamsLabel: string;
  }): void {
    const el = document.getElementById(cfg.selectId) as HTMLSelectElement | null;
    if (!el) return;

    const control = new TomSelect(el, {
      dropdownParent: `#${cfg.menuId}`,
      maxOptions: 512,
      plugins: {
        dropdown_header: {
          title: cfg.title,
          html: buildDropdownHeaderHtml(),
        },
        remove_button: {},
      },

      onInitialize(this: TeamScopedTomSelect) {
        initTeamOptions(this);
        applyTeamFilter(this);
        // all options are in the page already, so only offer the entry if some belong to another team
        const hasOtherTeamsOptions = (this._allOptions ?? []).some(isOtherTeamOption);
        addOtherTeamsOption(this, cfg.otherTeamsLabel, () => {
          this._showOtherTeams = true;
          applyTeamFilter(this);
        }, hasOtherTeamsOptions);
      },

      onChange(value: string | string[] | null | undefined) {
        onTeamScopedFilterChange(cfg.param, value);
      },
    }) as TeamScopedTomSelect;

    registerFilterControl({
      control,
      param: cfg.param,
      title: cfg.title,
      teamScoped: true,
      dropdownId: cfg.dropdownId,
      menuId: cfg.menuId,
    });
  }

  // category tomSelect
  initTeamScopedFilter({
    selectId: 'categoryFilter',
    dropdownId: 'categoryFilterDropdown',
    menuId: 'categoryFilterMenu',
    title: i18next.t('categories'),
    param: 'category',
    otherTeamsLabel: i18next.t('show-other-teams-categories'),
  });
  // status tomSelect
  initTeamScopedFilter({
    selectId: 'statusFilter',
    dropdownId: 'statusFilterDropdown',
    menuId: 'statusFilterMenu',
    title: i18next.t('status'),
    param: 'status',
    otherTeamsLabel: i18next.t('show-other-teams-status'),
  });


  // tags tomselect
  if (document.getElementById('tagFilter')) {
    const tsTagFilter = new TomSelect('#tagFilter', {
      dropdownParent: '#tagFilterMenu',

      onChange: (value: unknown) => {
        const selectedTags = Array.isArray(value) ? value as string[] : [];

        setEntityFilterParamValues('tags[]', selectedTags);
        renderActiveFilters();
      },

      clearAfterSelect: true,

      plugins: {
        clear_button: {},
        no_active_items: {},
        remove_button: {},
      },
    }) as TomSelectWithAllOptions;

    registerFilterControl({
      control: tsTagFilter,
      param: 'tags[]',
      title: i18next.t('tags'),
      dropdownId: 'tagFilterDropdown',
      menuId: 'tagFilterMenu',
    });
  }
}
