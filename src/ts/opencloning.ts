/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { notify } from './notify';

async function checkOpenCloningVersion(): Promise<void>
{
  const ocVersion = await fetch('/opencloning/version')
    .then(res => res.json())
    .catch(err => notify.error(err));
  const ocVersionInt = ocVersion.opencloning_version_int;
  // < v0.7.4 won't have a value for this, and eLab 5.3 starts being compatible with this version
  if (typeof ocVersionInt === 'undefined') {
    notify.warning(i18next.t('oc-version-warning'));
  }
}

// only run on syc.php page
if (document.getElementById('syc-root')) {
  checkOpenCloningVersion();
}
