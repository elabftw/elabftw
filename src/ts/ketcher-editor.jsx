/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { createRoot } from 'react-dom/client'

if (document.getElementById('ketcher-root')) {
  // only import ketcher if we really need it
  import(/* webpackChunkName: "ketcher" */ './ketcher.jsx')
  .then(({ default: KetcherEditor }) => {
    const root = createRoot(document.getElementById('ketcher-root'));
    // Note: use <StrictMode> in dev to spot errors
    root.render(
      <KetcherEditor />
    );
  });
}
