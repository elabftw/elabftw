/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any
import { SearchSyntaxHighlighting } from './SearchSyntaxHighlighting.class';

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
  }

  // Submit form with ctrl+enter from within textarea
  extendedArea.addEventListener('keydown', event => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      (document.getElementById('searchButton') as HTMLButtonElement).click();
    }
  });

  function getOperator(): string {
    return (document.getElementById('dateOperator') as HTMLSelectElement).value;
  }

  // a filter helper can be a select or an input (for date and extrafield), so we need a function to get its value
  function getFilterValueFromElement(element: HTMLElement): string {
    if (element instanceof HTMLSelectElement) {
      // clear action
      if (element.options[element.selectedIndex].dataset.action === 'clear') {
        return '';
      }
      if (element.id === 'dateOperator') {
        const date = (document.getElementById('date') as HTMLInputElement).value;
        const dateTo = (document.getElementById('dateTo') as HTMLInputElement).value;
        if (date === '') {
          return '';
        }
        if (dateTo === '') {
          return getOperator() + date;
        }
        return date + '..' + dateTo;
      }
      return `${element.options[element.selectedIndex].value}`;
    }
    if (element instanceof HTMLInputElement) {
      if (element.id === 'date') {
        if (element.value === '') {
          return '';
        }
        const dateTo = (document.getElementById('dateTo') as HTMLInputElement).value;
        if (dateTo === '') {
          return getOperator() + element.value;
        }
        return element.value + '..' + dateTo;
      }
      if (element.id === 'dateTo') {
        const date = (document.getElementById('date') as HTMLInputElement).value;
        if (date === '') {
          return '';
        }
        if (element.value === '') {
          return getOperator() + date;
        }
        return date + '..' + element.value;
      }
      if (element.id === 'metakey') {
        const metavalue = (document.getElementById('metavalue') as HTMLInputElement).value;
        if (metavalue === '' || element.value === '') {
          return '';
        }
        const keyQuotes = getQuotes(element.value);
        const valueQuotes = getQuotes(metavalue);
        return keyQuotes + element.value + keyQuotes + ':' + valueQuotes + metavalue + valueQuotes;
      }
      if (element.id === 'metavalue') {
        const metakey = (document.getElementById('metakey') as HTMLInputElement).value;
        if (metakey === '' || element.value === '') {
          return '';
        }
        const keyQuotes = getQuotes(metakey);
        const valueQuotes = getQuotes(element.value);
        return keyQuotes + metakey + keyQuotes + ':' + valueQuotes + element.value + valueQuotes;
      }
    }
    return '😶';
  }

  function getQuotes(filterValue): string {
    // don't add quotes unless we need them (space or some special chars exist)
    let quotes = '';
    
    // TODO: fix single and double quotation marks behaviour: &#39; &#34; in db
    if ([' ', '&', '|', '!', ':', '(', ')', '\''].some(value => filterValue.includes(value))) {
      quotes = '"';
    }
    if (filterValue.includes('"')) {
      quotes = '\'';
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

      // look if the filter key already exists in the extendedArea
      // paste the regex on regex101.com to understand it
      let valueRegex = ':(?:(?:"((?:\\\\"|(?:(?!")).)+)")|(?:\'((?:\\\\\'|(?:(?!\')).)+)\')|([^\\s:\'"()&|!]+))';
      if (elem.dataset.filter === 'extrafield') {
        // extrafield has key and value so we need the regex above twice
        valueRegex = `${valueRegex}${valueRegex}`;
      }
      const regex = new RegExp(elem.dataset.filter + valueRegex + '\\s?');
      const found = curVal.match(regex);
      // default value is clearing everything
      let filter = '';
      let filterValue = getFilterValueFromElement(elem);
      const quotes = getQuotes(filterValue);
      // but if we have a correct value, we add the filter
      if (filterValue !== '') {
        let filterName = elem.dataset.filter;

        if (filterName === '(?:author|group)') {
          filterName = filterValue.split(':')[0];
          filterValue = filterValue.substring(filterName.length + 1);
        }

        filter = `${filterName}:${quotes}${filterValue}${quotes}`;

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
});
