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
import { collectForm } from './misc';

on('transfer-ownership', async () => {
  const payload = getOwnershipTransferPayload();
  ApiC.keepalive = true;
  await ApiC.patch(`${entity.type}/${entity.id}`, { userid: payload.target_owner, team: payload.team });
  ApiC.keepalive = false;
});
// when a team is selected, refresh the user input with users from that team.
function filterUsersByTeamSelected() {
  const teamSelectEl = document.getElementById('targetTeamSelect') as HTMLSelectElement;
  const userInput = document.getElementById('targetOwnerSelect') as HTMLInputElement;
  if (!teamSelectEl || !userInput) return;
  teamSelectEl.addEventListener('change', () => userInput.value = '');
}

on('toggle-modal', (el: HTMLElement) => {
  const target = el.dataset.target;
  if (target === 'ownerModal') {
    filterUsersByTeamSelected();
  }
});

type OwnershipTransferPayload = {
  target_owner: number;
  team: number;
};

export function getOwnershipTransferPayload(): OwnershipTransferPayload {
  const params = collectForm(document.getElementById('ownershipTransferForm')!);
  const targetOwner = parseInt(params['targetOwnerSelect'].split(' ')[0] ?? '', 10);
  const team = parseInt(params['targetTeamSelect'] ?? '', 10);
  return { target_owner: targetOwner, team };
}
