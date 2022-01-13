/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/search.php') {
    return;
  }
  // scroll to anchor if there is a search
  const params = new URLSearchParams(document.location.search.slice(1));
  if (params.has('type')) {
    window.location.hash = '#anchor';
  }

  const extendedArea = (document.getElementById('extended') as HTMLTextAreaElement);

  // Submit form with ctrl+enter from within textarea
  extendedArea.addEventListener('keydown', event => {
    if ((event.keyCode == 10 || event.keyCode == 13) && (event.ctrlKey || event.metaKey)) {
      (document.getElementById('searchButton') as HTMLButtonElement).click();
    }
  });

  // a filter helper can be a select or an input (for date), so we need a function to get its value
  function getFilterValueFromElement(element: HTMLElement): string {
    if (element instanceof HTMLSelectElement) {
      return `${element.options[element.selectedIndex].innerText}`;
    }
    if (element instanceof HTMLInputElement) {
      return element.value;
    }
    return '😶';
  }

  // add a change event listener to all elements that helps constructing the query string
  document.querySelectorAll('.filterHelper').forEach(el => {
    el.addEventListener('change', event => {
      const elem = event.currentTarget as HTMLElement;
      const curVal = extendedArea.value;

      // look if the filter key already exists
      const regex = new RegExp(elem.dataset.filter + ':"[\\w+\\s+?-]+"|[\\d+\\-]"');
      const found = curVal.match(regex);
      const filter = `${elem.dataset.filter}:"${getFilterValueFromElement(elem)}"`;
      // TODO have a way to have special options, for all team or yourself we want a different behavior

      if (found) {
        extendedArea.value = curVal.replace(regex, filter);
      } else {
        extendedArea.value = extendedArea.value + ' ' + filter;
      }
    });
  });

  /*
  if (localStorage.getItem('isExtendedSearchMode') === '1') {
    $('.collapseExtendedSearch').collapse('toggle');
    document.getElementById('toggleSearchMode').innerHTML = 'Switch to Default Search';

    // Owner has to be set to team in extended search
    (document.getElementById('searchonly') as HTMLSelectElement).value = '0';

    // Only keep Experiments and Database entries in 'searchin' select
    const searchin = document.getElementById('searchin') as HTMLSelectElement;
    const keep = Array.from(searchin.children).slice(0,3);
    searchin.replaceChildren(...keep);
    searchin.selectedIndex = 0;
  }
 */
});
