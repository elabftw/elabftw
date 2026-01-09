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
import { TomSelect } from './misc';

on('transfer-ownership', () => {
  const payload = getOwnershipTransferPayload();
  ApiC.patch(`${entity.type}/${entity.id}`, { userid: payload.target_owner, team: payload.team }).then(() => {
    window.location.href = location.pathname;
  });
});

// when a user is selected, we prevent the team selection change in order to
// avoid discrepancy (.e.g, selecting Toto in team 1, then changing Team to 2)
function setupUserInputWatcher() {
  const userInput = document.getElementById('targetOwnerSelect') as HTMLInputElement;
  const teamSelectEl = document.getElementById('targetTeamSelect') as HTMLSelectElement & { tomselect?: TomSelect };
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

type OwnershipTransferPayload = {
  target_owner: number;
  team: number;
};

export function getOwnershipTransferPayload(): OwnershipTransferPayload {
  const userValue = (document.getElementById('targetOwnerSelect') as HTMLInputElement | null)?.value;
  const teamValue = (document.getElementById('targetTeamSelect') as HTMLSelectElement | null)?.value;
  console.log(document.getElementById('targetTeamSelect'));
  console.log('user value', userValue, 'team value',teamValue);

  const targetOwner = parseInt(userValue?.split(' ')[0] ?? '', 10);
  const team = parseInt(teamValue ?? '', 10);
  console.log('target owner', targetOwner, 'team', team);
  if (!Number.isInteger(targetOwner) || targetOwner <= 0) return null;
  if (!Number.isInteger(team) || team <= 0) return null;
  return { target_owner: targetOwner, team };
}
