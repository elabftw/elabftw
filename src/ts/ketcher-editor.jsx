/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { createRoot } from 'react-dom/client'
import KetcherEditor from './ketcher';

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('ketcher-root')) {
    const root = createRoot(document.getElementById('ketcher-root'));
    // Note: use <StrictMode> in dev to spot errors
    root.render(
      <KetcherEditor />
    );
  }
});
