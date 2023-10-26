/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
/**
 * This file is a yarn plugin that hooks into the afterAllInstalled hook to execute. Its purpose is to extract two css files from the tinymce folder.
 * But because of PnP this is much more difficult to do than previously, when a simple "cp" was enough.
 * Currently I'm hardcoding the path because I need to move on to more interesting things, but ideally we use yarn tools to find the real path properly
 * doc: https://yarnpkg.com/advanced/pnpapi
 */
module.exports = {
  name: `plugin-tinymce`,
   factory: require => {
    const { PosixFS } = require(`@yarnpkg/fslib`);
    const { ZipOpenFS } = require(`@yarnpkg/libzip`);
    const libzip = require(`@yarnpkg/libzip`).getLibzipSync();

    return {
      default: {
        hooks: {
          afterAllInstalled() {
            const zipOpenFs = new ZipOpenFS({ libzip });
            const crossFs = new PosixFS(zipOpenFs);

            const extractFile = (filename) => {
              const requestedFile = `/root/.yarn/berry/cache/tinymce-npm-6.7.2-d952b8dbd3-10c0.zip/node_modules/tinymce/skins/ui/oxide/${filename}`;
              const fileContent = crossFs.readFileSync(requestedFile);
              const destinationPath = `web/assets/${filename}`;
              crossFs.writeFileSync(destinationPath, fileContent, `utf8`);
            };

            extractFile('skin.min.css');
            extractFile('content.min.css');
          }
        }
      }
    };
  }
};
