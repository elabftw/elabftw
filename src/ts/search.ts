/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any
import { SearchSyntaxHighlighting } from './SearchSyntaxHighlighting.class';
import { TomSelect } from './misc';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/search.php') {
    return;
  }

  // scroll to anchor if there is a search
  const params = new URLSearchParams(document.location.search.slice(1));
  if (params.has('type')) {
    document.getElementById('anchor').scrollIntoView();
  }

  const extendedArea = document.getElementById('extendedArea') as HTMLInputElement;

  SearchSyntaxHighlighting.init(extendedArea);

  if ((document.getElementById('searchin') as HTMLSelectElement).value === 'database') {
    document.getElementById('searchStatus').toggleAttribute('hidden', true);
    document.getElementById('searchCategory').toggleAttribute('hidden', false);
  }

  document.getElementById('searchin').addEventListener('change', event => {
    const value = (event.target as HTMLSelectElement).value;
    let isExp = false;
    if (value === 'experiments') {
      isExp = true;
    }
    document.getElementById('experimentsCategoriesDiv').toggleAttribute('hidden', !isExp);
    document.getElementById('resourcesCategoriesDiv').toggleAttribute('hidden', isExp);
    document.getElementById('experimentsStatusDiv').toggleAttribute('hidden', !isExp);
    document.getElementById('resourcesStatusDiv').toggleAttribute('hidden', isExp);
  });

  // always show the filters
  const filtersDiv = document.getElementById('filtersDiv');
  if (filtersDiv) {
    filtersDiv.toggleAttribute('hidden', false);
    const button = document.querySelector('[data-toggle-target="filtersDiv"]');
    button.firstElementChild.classList.add('fa-caret-down');
    button.setAttribute('aria-expanded', 'true');
  }

  // Submit form with ctrl+enter from within textarea
  extendedArea.addEventListener('keydown', event => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      (document.getElementById('searchButton') as HTMLButtonElement).click();
    }
  });

  // a filter helper can be a select or an input (for date and extrafield), so we need a function to get its value
  function getFilterValueFromElement(element: HTMLElement): string {
    const escapeDoubleQuotes = (string: string): string => {
      // escape double quotes if not already escaped
      return string.replace(/(?<!\\)"/g, '\\"');
    };
    const handleDate = (): string => {
      const date = (document.getElementById('date') as HTMLInputElement).value;
      const dateTo = (document.getElementById('dateTo') as HTMLInputElement).value;
      const dateOperatorEl = document.getElementById('dateOperator') as HTMLSelectElement;
      const dateOperator = dateOperatorEl.options[dateOperatorEl.selectedIndex].value;
      if (date === '') {
        return '';
      }
      if (dateTo === '') {
        return dateOperator + date;
      }
      return date + '..' + dateTo;
    };
    const handleMetadata = (): string => {
      const metakeyEl = document.getElementById('metakey') as HTMLSelectElement;
      const metakey = metakeyEl.options[metakeyEl.selectedIndex].value;
      const metavalue = (document.getElementById('metavalue') as HTMLInputElement).value;
      if (metakey === '' || metavalue === '') {
        return '';
      }
      const keyQuotes = getQuotes(metakey);
      const valueQuotes = getQuotes(metavalue);
      return keyQuotes + escapeDoubleQuotes(metakey) + keyQuotes + ':' + valueQuotes + escapeDoubleQuotes(metavalue) + valueQuotes;
    };
    if (element instanceof HTMLSelectElement) {
      // clear action
      if (element.options[element.selectedIndex].dataset.action === 'clear') {
        return '';
      }
      if (element.id === 'dateOperator') {
        return handleDate();
      }
      if (element.id === 'metakey') {
        return handleMetadata();
      }
      return escapeDoubleQuotes(element.options[element.selectedIndex].value);
    }
    if (element instanceof HTMLInputElement) {
      if (element.id === 'date') {
        return handleDate();
      }
      if (element.id === 'dateTo') {
        return handleDate();
      }
      if (element.id === 'metavalue') {
        return handleMetadata();
      }
    }
    return '😶';
  }

  // don't add quotes unless we need them (space or some special chars exist)
  function getQuotes(filterValue: string): string {
    let quotes = '';
    if ([' ', '&', '|', '!', ':', '(', ')', '\'', '"'].some(value => filterValue.includes(value))) {
      quotes = '"';
    }
    return quotes;
  }

  // add a change event listener to all elements that helps constructing the query string
  document.querySelectorAll('.filterHelper').forEach(el => {
    el.addEventListener('change', event => {
      const elem = event.currentTarget as HTMLElement;
      const curVal = extendedArea.value;

      const hasInput = curVal.length != 0;
      const hasSpace = curVal.endsWith(' ');
      const addSpace = hasInput ? (hasSpace ? '' : ' ') : '';
      let filterName = elem.dataset.filter;

      // look if the filter key already exists in the search input
      // paste the regex on regex101.com to understand it
      const baseRegex = '(?:(?:"((?:\\\\"|(?:(?!")).)+)")|(?:\'((?:\\\\\'|(?:(?!\')).)+)\')|([^\\s:\'"()&|!]+))';
      const operatorRegex = '(?:[<>]=?|!?=)?';
      let valueRegex = baseRegex;

      if (filterName === 'date') {
        // date can use operator
        valueRegex = operatorRegex + baseRegex;
      }
      if (filterName === 'extrafield') {
        // extrafield has key and value so we need the regex above twice
        valueRegex = baseRegex + ':' + baseRegex;
      }
      const regex = new RegExp(filterName + ':' + valueRegex + '\\s?');
      const found = curVal.match(regex);
      // default value is clearing everything
      let filter = '';
      let filterValue = getFilterValueFromElement(elem);
      let quotes = getQuotes(filterValue);
      // but if we have a correct value, we add the filter
      if (filterValue !== '') {
        if (filterName === 'date') {
          quotes = '';
        }

        if (filterName === '(?:author|group)') {
          filterName = filterValue.split(':')[0];
          filterValue = filterValue.substring(filterName.length + 1);
        }

        filter = filterName + ':' + quotes + filterValue + quotes;

        if (filterName === 'extrafield') {
          filter = `${filterName}:${filterValue}`;
        }
      }

      // add additional filter at cursor position
      if (key.ctrl || key.command) {
        const pos = extendedArea.selectionStart;
        const val = extendedArea.value;
        const start = val.substring(0, pos);
        const end = val.substring(pos, val.length);
        const hasSpaceBefore = val.substring(pos - 1, pos) === ' ';
        const hasSpaceAfter = val.substring(pos, pos + 1) === ' ';
        const insert = (hasSpaceBefore ? '' : pos === 0 ? '' : ' ') + filter + (hasSpaceAfter ? '' : ' ');
        extendedArea.value = start + insert + end;
        SearchSyntaxHighlighting.update(extendedArea.value);
        return;
      }

      if (found) {
        extendedArea.value = curVal.replace(regex, filter + (filter === '' ? '' : ' '));
      } else {
        extendedArea.value = extendedArea.value + addSpace + filter;
      }
      SearchSyntaxHighlighting.update(extendedArea.value);
    });
  });

  ['metakey', 'searchonly'].forEach(id => {
    new TomSelect(`#${id}`, {
      plugins: [
        'dropdown_input',
        'remove_button',
      ],
    });
  });
});
