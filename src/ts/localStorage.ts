/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// storage can be unavailable (private browsing, blocked site data): reading then gives nothing and writing is skipped,
// so the page still works, only without remembering
export function readStorage(key: string): string | null {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}

export function writeStorage(key: string, value: string): void {
  try {
    localStorage.setItem(key, value);
  } catch {
    // nothing to remember it in
  }
}

// custom clear function so we keep the persistent keys
export function clearLocalStorage() {
  try {
    clearExceptPersistent();
  } catch {
    // nothing stored to clear
  }
}

function clearExceptPersistent(): void {
  const prefix = 'persistent_';
  const allKeys: string[] = [];
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    if (key) {
      allKeys.push(key);
    }
  }

  const backup: Record<string, string> = {};
  for (const key of allKeys) {
    if (key.startsWith(prefix)) {
      const value = localStorage.getItem(key);
      if (value !== null) {
        backup[key] = value;
      }
    }
  }

  localStorage.clear();

  for (const [key, value] of Object.entries(backup)) {
    localStorage.setItem(key, value);
  }
}

// store the value of selected option in localStorage
export function rememberLastSelected(elementId: string) {
  return function(value: string) {
    writeStorage(`persistent_${elementId}_last`, value);
  };
}

// get back the value of last selected option from localStorage
export function selectLastSelected(elementId: string) {
  return function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('switch_team') === '1') {
      return;
    }
    const last = readStorage(`persistent_${elementId}_last`);
    if (last) {
      this.setValue(last);
    }
  };
}
