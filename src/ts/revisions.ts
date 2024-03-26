/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Api } from './Apiv2.class';
import { Action } from './interfaces';
import { notifError } from './misc';
import DiffMatchPatch from 'diff-match-patch';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }

  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in edit mode
  if (about.page !== 'revisions') {
    return;
  }

  interface CheckedRevision {
    id: number;
    revid: number;
  }

  interface DiffArr {
    0: DiffMatchPatch.DIFF_DELETE | DiffMatchPatch.DIFF_INSERT | DiffMatchPatch.DIFF_EQUAL;
    1: string;
  }

  // count number of checked revisions
  function getCheckedBoxes(): Array<CheckedRevision> {
    const checkedBoxes = [];
    document.querySelectorAll('input[type=checkbox]:checked').forEach((box: HTMLInputElement) => {
      checkedBoxes.push({
        id: parseInt(box.dataset.id),
        revid: parseInt(box.dataset.revid),
      });
    });
    // sort the array so we get correct colors for newest vs oldest
    return checkedBoxes.sort((a, b) => a.revid - b.revid);
  }

  const ApiC = new Api();

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', async (event) => {
    const el = (event.target as HTMLElement);
    // CHECKBOX REVISION
    if (el.matches('[data-action="checkbox-revision"]')) {
      // background color for selected entities
      const bgColor = '#c4f9ff';
      document.getElementById('compareRevisionsDiv').removeAttribute('hidden');
      if ((el as HTMLInputElement).checked) {
        (el.closest('.list-group-item') as HTMLElement).style.backgroundColor = bgColor;
      } else {
        (el.closest('.list-group-item') as HTMLElement).style.backgroundColor = '';
      }
      const checkedBoxes = getCheckedBoxes();
      if (checkedBoxes.length === 2) {
        document.getElementById('compareRevisionsButton').removeAttribute('disabled');
      } else {
        document.getElementById('compareRevisionsButton').setAttribute('disabled', 'disabled');
      }


    } else if (el.matches('[data-action="compare-revisions"]')) {
      const checkedBoxes = getCheckedBoxes();
      if (checkedBoxes.length !== 2) {
        notifError(new Error('Select two revisions to compare them.'));
        return;
      }
      const dmp = new DiffMatchPatch();

      const json0 = await ApiC.getJson(`${el.dataset.type}/${checkedBoxes[0].id}/revisions/${checkedBoxes[0].revid}`);
      const json1 = await ApiC.getJson(`${el.dataset.type}/${checkedBoxes[1].id}/revisions/${checkedBoxes[1].revid}`);
      const diff = dmp.diff_main(json0.body, json1.body);
      const diffDiv = document.getElementById('compareRevisionsDiffDiv');
      diffDiv.replaceChildren();
      diff.forEach((part: DiffArr) => {
        let color = '';
        const res = part[0];
        if (res === DiffMatchPatch.DIFF_DELETE) {
          color = '#dd1e00';
        }
        if (res === DiffMatchPatch.DIFF_INSERT) {
          color = '#54aa08';
        }
        const span = document.createElement('span');
        span.style.color = color;
        span.innerHTML = part[1];
        diffDiv.appendChild(span);
      });
    } else if (el.matches('[data-action="restore-revision"]')) {
      ApiC.patch(`${el.dataset.type}/${el.dataset.id}/revisions/${el.dataset.revid}`, {'action': Action.Replace});
    }
  });
});
