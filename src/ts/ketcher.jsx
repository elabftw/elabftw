/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Editor } from 'ketcher-react';
import 'ketcher-react/dist/index.css';

/**
 * The structServiceProvider is remote but using a proxied server on eLab main URL
 * Using the WASM Standalone version did not work well: either you include a 50 Mb base64'd wasm
 * or webpack has issue with including a working wasm or something. So for now the best approach is to require a separate Indigo service
 * but eventually, using the wasm standalone could be a good solution.
*/
import { RemoteStructServiceProvider } from 'ketcher-core';
const structServiceProvider = new RemoteStructServiceProvider(
  '/indigo/v2',
);

const KetcherEditor = () => {
  return (
    <div className="ketcher-editor-container">
      <Editor
        staticResourcesUrl={JSON.stringify('/')}
        structServiceProvider={structServiceProvider}
        onInit={(ketcher) => {window.ketcher = ketcher;}}
      />
    </div>
  );
};

export default KetcherEditor;
