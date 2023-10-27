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

            // get path to tinymce files in yarn cache
            const cache = await Cache.find(project.configuration);
            const tinymceIdent = structUtils.makeIdent(null, 'tinymce');
            let tinymceLocator;
            for (const pkg of project.storedPackages.values()) {
              if (pkg.identHash === tinymceIdent.identHash) {
                tinymceLocator = pkg;
                break;
              }
            };
            const checksum = project.storedChecksums.get(tinymceLocator) ?? null;
            const path = cache.getLocatorPath(tinymceLocator, checksum);

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
