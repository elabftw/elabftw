/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Api } from './Apiv2.class';
import Tab from './Tab.class';
import { collectForm, relativeMoment, reloadElements } from './misc';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/profile.php') {
    return;
  }

  const ApiC = new Api();

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));
  // update state constantly for pending or processing requests
  function updateState(el: HTMLElement) {
    ApiC.getJson(`exports/${el.dataset.id}`).then(json => {
      if (json.state !== el.dataset.state) {
        reloadElements(['exportedFilesTable']).then(() => relativeMoment());
      }
    });
  }

  const interval = setInterval(updateState, 5000);

  document.querySelectorAll('.state-indicator').forEach((el: HTMLElement) => {
    if (el.dataset.state === '4') {
      setTimeout(updateState, 5000, el);
    }
  });
  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // CREATE ITEMS TYPES
    if (el.matches('[data-action="create-export"]')) {
      const params = collectForm(document.getElementById('exportForm'));
      const urlParams = new URLSearchParams(params as URLSearchParams);
      ApiC.post('exports', {source: urlParams.toString(), changelog: urlParams.get('changelog'), pdfa: urlParams.get('pdfa'), json: urlParams.get('json')}).then(() => reloadElements(['exportedFilesTable']).then(() => relativeMoment()));
    } else if (el.matches('[data-action="destroy-export"]')) {
      ApiC.delete(`exports/${el.dataset.id}`).then(() => reloadElements(['exportedFilesTable']).then(() => relativeMoment()));
    }
  });
});
