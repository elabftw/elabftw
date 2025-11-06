/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// custom clear function so we keep the persistent keys
export function clearLocalStorage() {
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
    localStorage.setItem(`persistent_${elementId}_last`, value);
  };
}

// get back the value of last selected option from localStorage
export function selectLastSelected(elementId: string) {
  return function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('switch_team') === '1') {
      return;
    }
    const last = localStorage.getItem(`persistent_${elementId}_last`);
    if (last) {
      this.setValue(last);
    }
  };
}
