/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const about = document.getElementById('info').dataset;
  // only run in deleted mode
  if (about.page !== 'deleted') {
    return;
  }
  console.log("deleted entity");
});
