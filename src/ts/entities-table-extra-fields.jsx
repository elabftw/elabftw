/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Columns built from custom fields (metadata.extra_fields) in the entities table of show mode
 */
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ApiC } from './api';
import i18next from './i18n';
import { readStorage, writeStorage } from './localStorage';

// keep in sync with DisplayParams::MAX_EXTRA_FIELDS_COLUMNS
export const MAX_EXTRA_FIELDS_COLUMNS = 20;

// prefix for the colId so these columns cannot collide with the regular ones in the stored column state
const COL_ID_PREFIX = 'extra_field:';

// the custom fields differ between entity types, so the choice is stored per entity type
const getStorageKey = entityType => `persistent_entities_table_extra_fields_v1_${entityType}`;

export const getStoredExtraFieldsColumns = entityType => {
  try {
    const stored = JSON.parse(readStorage(getStorageKey(entityType)) ?? '[]');
    if (!Array.isArray(stored)) {
      return [];
    }
    return [...new Set(stored.filter(name => typeof name === 'string' && name.length > 0))]
      .slice(0, MAX_EXTRA_FIELDS_COLUMNS);
  } catch {
    return [];
  }
};

export const storeExtraFieldsColumns = (entityType, names) => writeStorage(getStorageKey(entityType), JSON.stringify(names));

// custom field types holding the id of something else, their type is also the API endpoint to read it
const REFERENCE_TYPES = ['experiments', 'items', 'users', 'compounds'];
const REFERENCE_PAGES = { experiments: 'experiments.php', items: 'database.php' };

const getUserDisplayName = json => String(json.fullname ?? '').trim()
  || `${json.firstname ?? ''} ${json.lastname ?? ''}`.trim()
  || String(json.userid ?? '');

// labels are read through the API so permissions apply, like replaceWithTitle() does in the metadata view.
// The promises are shared by all the cells: an id is only requested once per page load, and only for rendered cells.
const referenceLabels = new Map();
const fetchReferenceLabel = (type, id) => {
  const path = `${type}/${id}`;
  if (!referenceLabels.has(path)) {
    referenceLabels.set(path, ApiC.getJson(path, { notifOnError: 0 })
      .then(json => {
        if (type === 'users') {
          return getUserDisplayName(json);
        }
        return type === 'compounds' ? json.name : json.title;
      })
      // not found or not readable: the id is displayed instead
      .catch(() => null));
  }
  return referenceLabels.get(path);
};

const ReferenceLabel = ({ type, id }) => {
  const [label, setLabel] = useState(null);
  useEffect(() => {
    // 0 is an entry that could not be found during an import
    if (id === '0') {
      return;
    }
    let active = true;
    fetchReferenceLabel(type, id).then(result => {
      if (active) {
        setLabel(result || null);
      }
    });
    return () => {
      active = false;
    };
  }, [type, id]);
  return <span title={id}>{label ?? id}</span>;
};

// render each value with the given function, separated by a comma
const joinRendered = (values, render) => (
  <span>
    {values.map((value, index) => (
      <React.Fragment key={index}>
        {index > 0 && ', '}
        {render(value)}
      </React.Fragment>
    ))}
  </span>
);

const isHttpUrl = value => /^https?:\/\//i.test(value);

const ExtraFieldRenderer = ({ data, colDef }) => {
  const field = data?.extra_fields_values?.[colDef.context.extraFieldName];
  if (!field || field.value === '') {
    return null;
  }
  // the joined value is used for sorting and filtering, the individual values for display
  const values = (Array.isArray(field.values) ? field.values : [field.value]).filter(value => value !== '');

  if (field.type === 'checkbox') {
    // the raw value is kept as text for the floating filter, only the display changes
    if (!values.includes('on')) {
      return null;
    }
    return joinRendered(values, value => (
      <>
        <i className={`${value === 'on' ? 'fas fa-square-check' : 'far fa-square'} fa-fw`} aria-hidden='true'></i>
        <span className='sr-only'>{value}</span>
      </>
    ));
  }

  // a reference holds an id: display its label, but sort and filter on the id as only rendered cells are resolved
  if (REFERENCE_TYPES.includes(field.type) && values.every(value => /^\d+$/.test(value))) {
    const page = REFERENCE_PAGES[field.type];
    return joinRendered(values, id => (page
      ? <a href={`${page}?mode=view&id=${id}`}><ReferenceLabel type={field.type} id={id} /></a>
      : <ReferenceLabel type={field.type} id={id} />));
  }

  if (field.type === 'email') {
    return joinRendered(values, value => <a href={`mailto:${value}`}>{value}</a>);
  }

  if (field.type === 'url') {
    return joinRendered(values, value => (isHttpUrl(value)
      ? <a href={value} target='_blank' rel='noopener noreferrer'>{value}</a>
      : value));
  }

  return <span>{field.value}{field.unit ? ` ${field.unit}` : ''}</span>;
};

const getExtraFieldValue = (data, name) => data?.extra_fields_values?.[name]?.value ?? '';

const toFiniteNumber = value => {
  if (String(value).trim() === '') {
    return null;
  }
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
};

// empty cells first, then numbers sorted as numbers, then everything else sorted as text
// each group is ordered on its own so the ordering stays consistent (no cycle between numeric and text comparison)
export const extraFieldComparator = (valueA, valueB) => {
  const a = valueA ?? '';
  const b = valueB ?? '';
  if (a === '' || b === '') {
    return a === b ? 0 : (a === '' ? -1 : 1);
  }
  const numberA = toFiniteNumber(a);
  const numberB = toFiniteNumber(b);
  if (numberA !== null && numberB !== null) {
    return numberA === numberB ? 0 : (numberA < numberB ? -1 : 1);
  }
  if (numberA !== null || numberB !== null) {
    return numberA !== null ? -1 : 1;
  }
  return String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' });
};

export const makeExtraFieldColumnDef = name => ({
  colId: `${COL_ID_PREFIX}${name}`,
  headerName: name,
  // custom field names are often long and get truncated in the header
  headerTooltip: name,
  context: { extraFieldName: name },
  valueGetter: p => getExtraFieldValue(p.data, name),
  filterValueGetter: p => {
    const field = p.data?.extra_fields_values?.[name];
    return field ? `${field.value}${field.unit ? ` ${field.unit}` : ''}` : '';
  },
  comparator: extraFieldComparator,
  cellRenderer: ExtraFieldRenderer,
});

export const ExtraFieldsColumnsPicker = ({ entityType, columns, onAdd, onRemove }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [keys, setKeys] = useState(null);
  const [loadError, setLoadError] = useState(false);
  const rootRef = useRef(null);
  const toggleRef = useRef(null);
  const searchRef = useRef(null);

  // fetch the keys the first time the picker is opened, and again after a failure (on retry or when reopened).
  // A request still running when the picker closes is ignored: keys stay null and the next opening starts a new one.
  useEffect(() => {
    if (!isOpen || keys !== null || loadError) {
      return;
    }
    let active = true;
    ApiC.getJson('extra_fields_keys', { limit: -1, type: entityType })
      .then(response => {
        if (!active) {
          return;
        }
        setKeys(response
          .filter(entry => typeof entry.extra_fields_key === 'string' && entry.extra_fields_key.length > 0)
          .map(entry => ({ name: entry.extra_fields_key, frequency: entry.frequency })));
      })
      .catch(() => {
        if (active) {
          setLoadError(true);
        }
      });
    return () => {
      active = false;
    };
  }, [isOpen, keys, loadError, entityType]);

  const open = () => {
    setLoadError(false);
    setIsOpen(true);
  };

  // focus goes back to the button when closing with the keyboard, not when clicking somewhere else
  const close = restoreFocus => {
    setIsOpen(false);
    if (restoreFocus) {
      toggleRef.current?.focus();
    }
  };

  useEffect(() => {
    if (!isOpen) {
      return;
    }
    searchRef.current?.focus();

    const closeOnOutsideClick = event => {
      if (rootRef.current && !rootRef.current.contains(event.target)) {
        close(false);
      }
    };
    const closeOnEscape = event => {
      if (event.key === 'Escape') {
        close(true);
      }
    };
    document.addEventListener('mousedown', closeOnOutsideClick);
    document.addEventListener('keydown', closeOnEscape);
    return () => {
      document.removeEventListener('mousedown', closeOnOutsideClick);
      document.removeEventListener('keydown', closeOnEscape);
    };
  }, [isOpen]);

  const isFull = columns.length >= MAX_EXTRA_FIELDS_COLUMNS;

  const availableKeys = useMemo(() => {
    const needle = search.trim().toLocaleLowerCase();
    return (keys ?? [])
      .filter(key => !columns.includes(key.name))
      .filter(key => needle === '' || key.name.toLocaleLowerCase().includes(needle));
  }, [keys, columns, search]);

  const add = name => {
    if (isFull || columns.includes(name)) {
      return;
    }
    onAdd(name);
    setSearch('');
    searchRef.current?.focus();
  };

  const searchKeyDown = event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      if (availableKeys.length > 0) {
        add(availableKeys[0].name);
      }
    }
  };

  const label = i18next.t('add-column-from-custom-field');

  return (
    <div className='entities-table-extra-fields dropup' ref={rootRef}>
      {/* icon only, so its width doesn't depend on the language: the label is in the tooltip */}
      <button
        ref={toggleRef}
        type='button'
        className='btn btn-transparent'
        aria-haspopup='true'
        aria-expanded={isOpen}
        aria-label={label}
        title={label}
        onClick={() => (isOpen ? close(false) : open())}
      >
        <i className='fas fa-table-columns fa-fw' aria-hidden='true'></i>
        {columns.length > 0 && <span className='badge badge-pill badge-secondary ml-1'>{columns.length}</span>}
      </button>

      <div className={`dropdown-menu entities-table-extra-fields-menu${isOpen ? ' show' : ''}`} role='dialog' aria-label={label}>
        <h6 className='dropdown-header'>{label}</h6>

        <div className='px-3 pb-2'>
          <input
            ref={searchRef}
            type='search'
            className='form-control form-control-sm'
            placeholder={i18next.t('search-custom-fields')}
            aria-label={i18next.t('search-custom-fields')}
            value={search}
            disabled={isFull}
            onChange={event => setSearch(event.target.value)}
            onKeyDown={searchKeyDown}
          />
          {isFull && <small className='form-text text-muted'>{i18next.t('max-custom-field-columns', { max: MAX_EXTRA_FIELDS_COLUMNS })}</small>}
        </div>

        {!isFull && (
          <ul className='list-unstyled mb-0 entities-table-extra-fields-list'>
            {keys === null && !loadError && (
              <li className='dropdown-item-text text-muted'>
                <i className='fas fa-spinner fa-spin fa-fw mr-2' aria-hidden='true'></i>{i18next.t('loading')}
              </li>
            )}
            {keys === null && loadError && (
              <li className='dropdown-item-text text-muted d-flex justify-content-between align-items-center'>
                <span>{i18next.t('error-fetch-request', { error: i18next.t('custom-fields') })}</span>
                <button type='button' className='btn btn-sm btn-ghost ml-2' onClick={() => setLoadError(false)}>
                  <i className='fas fa-rotate-right fa-fw mr-1' aria-hidden='true'></i>{i18next.t('refresh')}
                </button>
              </li>
            )}
            {keys !== null && availableKeys.length === 0 && (
              <li className='dropdown-item-text text-muted'>{i18next.t('no-custom-field-found')}</li>
            )}
            {availableKeys.map(key => (
              <li key={key.name}>
                <button
                  type='button'
                  className='btn btn-dropdown-item dropdown-item d-flex justify-content-between align-items-center'
                  title={key.name}
                  onClick={() => add(key.name)}
                >
                  <span className='text-truncate'>
                    <i className='fas fa-plus fa-fw mr-2' aria-hidden='true'></i>{key.name}
                  </span>
                  <span className='badge badge-pill badge-light ml-2' title={i18next.t('number-of-entries')}>{key.frequency}</span>
                </button>
              </li>
            ))}
          </ul>
        )}

        {columns.length > 0 && (
          <>
            <div className='dropdown-divider'></div>
            <h6 className='dropdown-header'>{i18next.t('displayed-custom-fields')}</h6>
            <ul className='list-unstyled mb-0'>
              {columns.map(name => (
                <li key={name} className='d-flex justify-content-between align-items-center px-3 py-1'>
                  <span className='text-truncate' title={name}>{name}</span>
                  <button
                    type='button'
                    className='btn btn-sm btn-ghost ml-2'
                    title={`${i18next.t('remove-column')}: ${name}`}
                    aria-label={`${i18next.t('remove-column')}: ${name}`}
                    onClick={() => onRemove(name)}
                  >
                    <i className='fas fa-xmark fa-fw' aria-hidden='true'></i>
                  </button>
                </li>
              ))}
            </ul>
          </>
        )}
      </div>
    </div>
  );
};
