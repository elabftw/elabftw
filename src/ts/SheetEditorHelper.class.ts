/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Notification } from './Notifications.class';
import { read, utils } from 'xlsx';

const notify = new Notification();

export class SheetEditorHelper {
  currentUploadId = '';
  currentFilename = '';

  loadFile(link: string, name: string, uploadid: string): void {
    const headers = new Headers();
    headers.append('cache-control', 'no-cache');
    fetch(`app/download.php?f=${link}`, { headers })
      .then(response => {
        if (!response.ok) throw new Error('An unexpected error occurred!');
        return response.arrayBuffer();
      })
      .then(buffer => {
        const wb = read(buffer, { type: 'array' });
        const ws = wb.Sheets[wb.SheetNames[0]];
        // const aoa = utils.sheet_to_json(ws, { header: 1 });
        const aoa: (string | number | boolean | null)[][] = utils.sheet_to_json(ws, { header: 1 });

        if (!aoa.length) return notify.error('Invalid file');

        const rows = aoa.slice(1).map(r => {
          const row: Record<string, string> = {};
          headers.forEach((h, i) => {
            const key = String(h || `Column${i}`);
            row[key] = String(r[i] ?? '');
          });
          return row;
        });

        const cols = headers.map((h, i) => ({
          field: String(h || `Column${i}`),
          editable: true
        }));

        // Send to Sheet editor ag-grid, via event or context
        const ev = new CustomEvent('sheet-load-data', { detail: { cols, rows, name } });
        document.dispatchEvent(ev);

        this.currentUploadId = uploadid;
        this.currentFilename = name;
      })
      .catch(e => notify.error(e.message));
  }
}
