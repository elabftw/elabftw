/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// Keep the body of an entity that could not be saved (expired session, server or database down)
// in localStorage so it can be restored later. Everything happens in the browser on purpose:
// it must work even when the server is unreachable.

export interface RecoveryData {
  date: string;
  body: string;
}

function getKey(type: string, id: number): string {
  return `recovery_${type}_${id}`;
}

export function storeRecoveryData(type: string, id: number, body: string): void {
  const data: RecoveryData = { date: new Date().toISOString(), body };
  localStorage.setItem(getKey(type, id), JSON.stringify(data));
}

export function getRecoveryData(type: string, id: number): RecoveryData | null {
  const raw = localStorage.getItem(getKey(type, id));
  if (raw === null) {
    return null;
  }
  try {
    const parsed = JSON.parse(raw);
    if (typeof parsed?.date === 'string' && typeof parsed?.body === 'string') {
      return parsed;
    }
  } catch {
    // fall through: unusable entry
  }
  removeRecoveryData(type, id);
  return null;
}

export function removeRecoveryData(type: string, id: number): void {
  localStorage.removeItem(getKey(type, id));
}
