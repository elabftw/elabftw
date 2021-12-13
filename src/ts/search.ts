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

  function toogleSearchMode(): void {
    const searchMode = localStorage.getItem('isExtendedSearchMode');
    if (!searchMode || searchMode === '0') {
      localStorage.setItem('isExtendedSearchMode', '1');
    }
    if (searchMode === '1') {
      localStorage.setItem('isExtendedSearchMode', '0');
    }
    // Clear search input to avoid interference between modes
    window.location.href = 'search.php';
  }

  // Add click listener and do action based on which element is clicked
  document.getElementById('toggleSearchMode').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // Toggle search mode
    if (el.matches('[data-action="toggle-search-mode"]')) {
      toogleSearchMode();
      // Block button during transition
      el.style.pointerEvents = 'none';
    }
  });

  // Submit form with ctrl+enter from within textarea
  document.getElementById('extended').addEventListener('keydown', event => {
    if ((event.keyCode == 10 || event.keyCode == 13) && (event.ctrlKey || event.metaKey)) {
      (document.getElementById('searchButton') as HTMLButtonElement).click();
    }
  });

  // Unblock button after transition. Couldn't make it work without jQuery
  $('.collapseExtendedSearch').on('hidden.bs.collapse', () => {
    document.getElementById('toggleSearchMode').style.pointerEvents = 'auto';
  });

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
});
