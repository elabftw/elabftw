/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Api } from './Apiv2.class';
import { Action } from './interfaces';
import { getEntity } from './misc';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const about = document.getElementById('info').dataset;
  // only run in deleted mode
  if (about.page !== 'deleted') {
    return;
  }
  const ApiC = new Api();
  const entity = getEntity();

  document.querySelector('.real-container').addEventListener('click', (event) => {
    if ((event.target as HTMLElement).matches('[data-action="restore-entity"]')) {
      ApiC.patch(`${entity.type}/${entity.id}`, { action: Action.Restore })
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
    }
  });
});
