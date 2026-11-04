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

// keep in sync with DisplayParams::MAX_EXTRA_FIELDS_COLUMNS
export const MAX_EXTRA_FIELDS_COLUMNS = 20;

// prefix for the colId so these columns cannot collide with the regular ones in the stored column state
const COL_ID_PREFIX = 'extra_field:';

// the custom fields differ between experiments and resources, so the choice is stored per entity type
const getStorageKey = entityType => `persistent_entities_table_extra_fields_v1_${entityType}`;

export const getStoredExtraFieldsColumns = entityType => {
  try {
    const stored = JSON.parse(localStorage.getItem(getStorageKey(entityType)) ?? '[]');
    if (!Array.isArray(stored)) {
      return [];
    }
    return [...new Set(stored.filter(name => typeof name === 'string' && name.length > 0))]
      .slice(0, MAX_EXTRA_FIELDS_COLUMNS);
  } catch {
    return [];
  }
};

export const storeExtraFieldsColumns = (entityType, names) => {
  try {
    localStorage.setItem(getStorageKey(entityType), JSON.stringify(names));
  } catch {
    // localStorage might be unavailable
  }
};

const getPageForType = type => {
  if (type === 'experiments') {
    return 'experiments.php';
  }
  if (type === 'items') {
    return 'database.php';
  }
  return null;
};

const ExtraFieldRenderer = ({ data, colDef }) => {
  const field = data?.extra_fields_values?.[colDef.context.extraFieldName];
  if (!field || field.value === '') {
    return null;
  }

  if (field.type === 'checkbox') {
    // the raw value is kept as text for the floating filter, only the display changes
    return field.value === 'on'
      ? <span><i className='fas fa-square-check fa-fw' aria-hidden='true'></i><span className='sr-only'>{field.value}</span></span>
      : null;
  }

  // a link to an experiment or a resource holds its id
  const page = getPageForType(field.type);
  if (page && /^\d+(, \d+)*$/.test(field.value)) {
    return (
      <span>
        {field.value.split(', ').map((id, index) => (
          <React.Fragment key={id}>
            {index > 0 && ', '}
            <a href={`${page}?mode=view&id=${id}`}>{id}</a>
          </React.Fragment>
        ))}
      </span>
    );
  }

  if (field.type === 'url' && /^https?:\/\//i.test(field.value)) {
    return <a href={field.value} target='_blank' rel='noopener noreferrer'>{field.value}</a>;
  }

  return <span>{field.value}{field.unit ? ` ${field.unit}` : ''}</span>;
};

const getExtraFieldValue = (data, name) => data?.extra_fields_values?.[name]?.value ?? '';

// sort numbers as numbers, everything else as text, and keep empty cells together
const extraFieldComparator = (valueA, valueB) => {
  const a = valueA ?? '';
  const b = valueB ?? '';
  if (a === '' || b === '') {
    return a === b ? 0 : (a === '' ? -1 : 1);
  }
  const numberA = Number(a);
  const numberB = Number(b);
  if (Number.isFinite(numberA) && Number.isFinite(numberB)) {
    return numberA - numberB;
  }
  return String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' });
};

export const makeExtraFieldColumnDef = name => ({
  colId: `${COL_ID_PREFIX}${name}`,
  headerName: name,
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
  const searchRef = useRef(null);

  // only fetch the keys the first time the picker is opened
  useEffect(() => {
    if (!isOpen || keys !== null) {
      return;
    }
    const query = { limit: -1 };
    if (entityType === 'experiments' || entityType === 'items') {
      query.type = entityType;
    }
    ApiC.getJson('extra_fields_keys', query)
      .then(response => {
        setKeys(response
          .filter(entry => typeof entry.extra_fields_key === 'string' && entry.extra_fields_key.length > 0)
          .map(entry => ({ name: entry.extra_fields_key, frequency: entry.frequency })));
      })
      .catch(() => {
        setLoadError(true);
        setKeys([]);
      });
  }, [isOpen, keys, entityType]);

  useEffect(() => {
    if (!isOpen) {
      return;
    }
    searchRef.current?.focus();

    const closeOnOutsideClick = event => {
      if (rootRef.current && !rootRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    const closeOnEscape = event => {
      if (event.key === 'Escape') {
        setIsOpen(false);
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
      <button
        type='button'
        className='btn btn-transparent'
        aria-haspopup='true'
        aria-expanded={isOpen}
        aria-label={label}
        title={label}
        onClick={() => setIsOpen(open => !open)}
      >
        <i className='fas fa-table-columns fa-fw' aria-hidden='true'></i>
        <span className='d-none d-xl-inline ml-1'>{label}</span>
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
            {keys === null && (
              <li className='dropdown-item-text text-muted'>
                <i className='fas fa-spinner fa-spin fa-fw mr-2' aria-hidden='true'></i>{i18next.t('loading')}
              </li>
            )}
            {keys !== null && availableKeys.length === 0 && (
              <li className='dropdown-item-text text-muted'>
                {loadError ? i18next.t('error-fetch-request', { error: i18next.t('custom-fields') }) : i18next.t('no-custom-field-found')}
              </li>
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
