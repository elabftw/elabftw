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

  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);

    // CREATE EXPORT
    if (el.matches('[data-action="create-export"]')) {
      const params = collectForm(document.getElementById('exportForm'));
      const urlParams = new URLSearchParams(params as URLSearchParams);
      ApiC.post('exports', {
        experiments: urlParams.get('experiments'),
        experiments_templates: urlParams.get('experiments_templates'),
        items: urlParams.get('items'),
        items_types: urlParams.get('items_types'),
        format: urlParams.get('format'),
        changelog: urlParams.get('changelog'),
        pdfa: urlParams.get('pdfa'),
        json: urlParams.get('json'),
      }).then(() => reloadElements(['exportedFilesTable']).then(() => relativeMoment()));

    // DESTROY EXPORT
    } else if (el.matches('[data-action="destroy-export"]')) {
      ApiC.delete(`exports/${el.dataset.id}`).then(() => reloadElements(['exportedFilesTable']).then(() => relativeMoment()));
    }
  });
});
