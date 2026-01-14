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
  const { userid, teamid } = getOwnershipTransferPayload();
  ApiC.keepalive = true;
  await ApiC.patch(`${entity.type}/${entity.id}`, { userid, teamid });
  ApiC.keepalive = false;
});
// when a team is selected, refresh the user input with users from that team.
function filterUsersByTeamSelected() {
  const teamSelectEl = document.getElementById('targetTeamId') as HTMLSelectElement;
  const userInput = document.getElementById('targetUserId') as HTMLInputElement;
  if (!teamSelectEl || !userInput) return;
  teamSelectEl.addEventListener('change', () => userInput.value = '');
}

on('toggle-modal', (el: HTMLElement) => {
  const target = el.dataset.target;
  if (target === 'ownerModal') {
    filterUsersByTeamSelected();
  }
});

export function getOwnershipTransferPayload(): { userid: number, teamid: number } {
  const params = collectForm(document.getElementById('ownershipTransferForm')!);
  return {
    userid: Number.parseInt(params['targetUserId']?.split(' ')[0] ?? '', 10),
    teamid: Number.parseInt(params['targetTeamId'] ?? '', 10),
  };
}
