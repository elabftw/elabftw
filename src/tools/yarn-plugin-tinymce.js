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
 * doc: https://yarnpkg.com/advanced/pnpapi
 */
module.exports = {
  name: `plugin-tinymce`,
   factory: require => {
    const { PosixFS } = require(`@yarnpkg/fslib`);
    const { ZipOpenFS } = require(`@yarnpkg/libzip`);
    const libzip = require(`@yarnpkg/libzip`).getLibzipSync();
    const { structUtils, Cache } = require(`@yarnpkg/core`);

    return {
      default: {
        hooks: {
          async afterAllInstalled (project) {
            const zipOpenFs = new ZipOpenFS({ libzip });
            const crossFs = new PosixFS(zipOpenFs);
            const cache = await Cache.find(project.configuration);

            let tinymce = structUtils.makeIdent(null, 'tinymce');
            project.storedPackages.forEach(pkg => {
              if (pkg.identHash === tinymce.identHash) {
                tinymce = pkg;
              }
            });
            const checksum = project.storedChecksums.get(tinymce) ?? null;
            const path = cache.getLocatorPath(tinymce, checksum);

            const extractFile = (filename) => {
              const requestedFile = `${path}/node_modules/tinymce/skins/ui/oxide/${filename}`;
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
