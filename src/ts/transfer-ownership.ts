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
import { TomSelect } from './misc';
import { notify } from './notify';

on('transfer-ownership', () => {
  const userId = (document.getElementById('targetOwnerSelect') as HTMLInputElement).value;
  const teamId = (document.getElementById('team') as HTMLSelectElement).value;
  const parsedUserId = parseInt(userId.split(' ')[0], 10);
  const parsedTeamId = parseInt(teamId.split(' ')[0], 10);
  if (isNaN(parsedUserId) || isNaN(parsedTeamId)) {
    notify.error('invalid-info');
    return;
  }
  const params = {};
  params[Target.UserId] = parsedUserId;
  params[Target.Team] = parsedTeamId;
  ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => {
    window.location.href = location.pathname;
  });
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

// when a user is selected, we prevent the team selection change in order to
// avoid discrepancy (.e.g, selecting Toto in team 1, then changing Team to 2)
function setupUserInputWatcher() {
  const userInput = document.getElementById('targetOwnerSelect') as HTMLInputElement;
  const teamSelectEl = document.getElementById('team') as HTMLSelectElement & { tomselect?: TomSelect };
  if (!userInput || !teamSelectEl || !teamSelectEl.tomselect) return;
  const teamTomSelect = teamSelectEl.tomselect;
  userInput.addEventListener('input', () => {
    const hasValue = userInput.value.trim().length > 0;
    if (hasValue) {
      teamTomSelect.disable();
    } else {
      teamTomSelect.enable();
    }
  });
}

on('toggle-modal', (el: HTMLElement) => {
  const target = el.dataset.target;
  if (target === 'ownerModal') {
    setupUserInputWatcher();
    const isBatch = el.dataset.isbatch === 'true';
    document.getElementById('current_owner_div')?.classList.toggle('d-none', isBatch);
  }
});
