/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
/**
 * This file is a yarn plugin that hooks into the afterAllInstalled hook to execute. Its purpose is to extract css and js files from the tinymce folder.
 * But because of PnP this is much more difficult to do than previously, when a simple "cp" was enough.
 * doc: https://yarnpkg.com/advanced/pnpapi
 */
module.exports = {
  name: 'plugin-tinymce',
  factory: require => {
    const { PosixFS } = require('@yarnpkg/fslib');
    const { ZipOpenFS } = require('@yarnpkg/libzip');
    const libzip = require('@yarnpkg/libzip').getLibzipSync();
    const { structUtils, Cache } = require('@yarnpkg/core');

    return {
      default: {
        hooks: {
          async afterAllInstalled(project) {
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
            }
            const checksum = project.storedChecksums.get(tinymceLocator.locatorHash) ?? null;
            const locatorPath = cache.getLocatorPath(tinymceLocator, checksum);
            const pathToAssets = 'web/assets/';

            const extractFile = (nodeModulesPath, sourceName, targetName) => {
              targetName = typeof targetName === 'string' && targetName !== ''
                ? targetName
                : sourceName;
              const requestedFile = `${locatorPath}/node_modules/${nodeModulesPath}${sourceName}`;
              const fileContent = crossFs.readFileSync(requestedFile);
              crossFs.writeFileSync(pathToAssets + targetName, fileContent, 'utf8');
            };

            const appendFile = (sourceNameAndPath, targetName) => {
              const targetFileAndPath = pathToAssets + targetName;
              const sourceFileContent = crossFs.readFileSync(sourceNameAndPath);
              crossFs.appendFileSync(targetFileAndPath, sourceFileContent);
              };

            crossFs.mkdirSync(`${pathToAssets}tinymce_skins`, { recursive: true });
            extractFile('tinymce/skins/ui/oxide/', 'skin.min.css', 'tinymce_skins/skin.min.css');
            extractFile('tinymce/skins/content/default/', 'content.min.css', 'tinymce_skins/content.min.css');
            extractFile('tinymce/skins/ui/oxide/', 'content.min.css', 'tinymce_content.min.css');
            extractFile('tinymce/plugins/emoticons/js/', 'emojis.js', 'tinymce_emojis.js');
            appendFile('src/scss/_tinymce-custom.css', 'tinymce_content.min.css');
          },
        },
      },
    };
  },
};
