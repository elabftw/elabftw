/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import DiffMatchPatch from 'diff-match-patch';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/revisions.php') {
    return;
  }
  const dmp = new DiffMatchPatch();
  const inputs = document.querySelectorAll('.revisionBody');

  const diff = dmp.diff_main((inputs[0] as HTMLElement).innerHTML, (inputs[1] as HTMLElement).innerHTML);
  const output: Array<string> = [];
  diff.forEach((line: Array<number | string>) => {
    let cssClass = '';
    if (line[0] === -1) {
      cssClass = 'diff-added';
    }
    if (line[0] === 1) {
      cssClass = 'diff-removed';
    }
    output.push(`<span class='${cssClass}'> ${(line[1] as string)}</span>`);
  });
  console.log(output);
  (inputs[0] as HTMLElement).innerHTML = output.join('').replace('\\n', '');

});
