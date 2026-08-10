/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

if (document.getElementById('ketcher-root')) {
  void import(
    /* webpackChunkName: 'chem-editor' */
    './chem-editor'
  );
}

// only run on syc.php page
if (document.getElementById('syc-root')) {
  void import(
    /* webpackChunkName: 'opencloning' */
    './opencloning'
  );
}

// Profile page
if (window.location.pathname === '/ucp.php') {
  void import(
    /* webpackChunkName: 'ucp' */
    './ucp'
  );
}
