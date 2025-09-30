/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * In footer.html there is a core script that is json that we use to get info about current user's state
 * This module is here to read/parse it once in the app
 */
type Core = {
  isAnon: boolean;
  isAuth: boolean;
  currentTeam: number;
};

const el = document.getElementById('core') as HTMLScriptElement | null;
if (!el) console.error('Could not find core script element!');
export const core: Core = JSON.parse(el!.textContent || '');
