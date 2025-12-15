/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';
import { Target } from './interfaces';
// import { TomSelect } from './misc';
// import $ from 'jquery';

on('transfer-ownership', () => {
  const value = (document.getElementById('target_owner') as HTMLInputElement).value;
  console.log(value);
  const params = {};
  params[Target.UserId] = parseInt(value.split(' ')[0], 10);
  ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => window.location.reload());
});

function filterUsersByTeam(
  selectEl: HTMLSelectElement,
  teamId: string,
): void {
  const options = Array.from(selectEl.options);
  let hasVisible = false;
  options.forEach(option => {
    const teams = option.dataset.teams?.split(',') ?? [];
    const matches = teams.includes(teamId);
    option.hidden = !matches;
    if (matches) hasVisible = true;
  });
  // reset selection if current one is hidden
  if (!hasVisible || selectEl.selectedOptions[0]?.hidden) {
    const firstVisible = options.find(o => !o.hidden);
    if (firstVisible) {
      selectEl.value = firstVisible.value;
    }
  }
}

on('change-team', (el) => {
  const selectedTeamId = (el as HTMLSelectElement).value;
  const userSelect = document.getElementById('target_owner') as HTMLSelectElement;
  filterUsersByTeam(userSelect, selectedTeamId);
});
