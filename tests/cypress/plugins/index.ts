import * as https from 'https';
/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

/**
 * @type {Cypress.PluginConfig}
 */
module.exports = (on: Cypress.PluginEvents, config: Cypress.PluginConfigOptions) => {
  // `on` is used to hook into various events Cypress emits
  // `config` is the resolved Cypress config
  on('before:browser:launch', (browser, launchOptions) => {
    console.log(
      'Launching browser %s. Is headless: %s',
      browser.name,
      browser.isHeadless,
    );

    // the browser width and height we want to get
    // our screenshots and videos will be of that resolution
    const width = 1920;
    const height = 1080;

    console.log('Setting the browser window size to %d x %d', width, height);

    if (browser.name === 'chrome' && browser.isHeadless) {
      launchOptions.args.push(`--window-size=${width},${height}`);

      // force screen to be non-retina and just use our given resolution
      launchOptions.args.push('--force-device-scale-factor=1');
    }

    if (browser.name === 'electron' && browser.isHeadless) {
      // might not work on CI for some reason
      launchOptions.preferences.width = width;
      launchOptions.preferences.height = height;
    }

    if (browser.name === 'firefox' && browser.isHeadless) {
      launchOptions.args.push(`--width=${width}`);
      launchOptions.args.push(`--height=${height}`);
    }

    // IMPORTANT: return the updated browser launch options
    return launchOptions;
  });

  on('after:run', async () => {
    function createReport(reportType: string): Promise<void> {
      const reportUrl = config.baseUrl + `/c3/report/${reportType}/`;
      console.log('Creating codecoverage %s report by calling %s', reportType, reportUrl);
      const cookie = 'CODECEPTION_CODECOVERAGE=' + encodeURIComponent(JSON.stringify({
        CodeCoverage: `get ${reportType} report`,
        CodeCoverage_Suite: 'cypress',
      }));
      return new Promise((resolve, reject) => {
        https.get(reportUrl, {headers: {Cookie: cookie}}, res => {
          if (res.statusCode !== 200) {
            console.error('Did not get an OK from the server. Status code: %s', res.statusCode);
            reject();
          }
          // We have to consume the data but don't want to store it.
          // Reports are stored in the elabtmp container and will be extracted from there.
          res.on('data', () => { return; });
          res.on('close', () => {
            console.log('Created %s report', reportType);
            resolve();
          });
        }).on('error', e => {
          console.error(e.message.replace(/\n|\r/g, ''));
          reject();
        });
      });
    }
    try {
      await createReport('html');
      await createReport('clover');
    } catch (e) {
      console.error(String(e).replace(/\n|\r/g, ''));
    }
  });

  on('before:run', async () => {
    const url = config.baseUrl + '/c3/report/clear/';
    console.log('Clearing potentially existing codecoverage files by calling %s', url);
    const cookie = 'CODECEPTION_CODECOVERAGE=' + encodeURIComponent(JSON.stringify({
      CodeCoverage: 'clear codecoverage',
      CodeCoverage_Suite: 'cypress',
    }));
    try {
      await new Promise((resolve, reject) => {
        https.get(url, {headers: {Cookie: cookie}}, res => {
          if (res.statusCode !== 200) {
            console.error('Did not get an OK from the server. Status code: %s', res.statusCode);
            reject();
          }
          console.log('Reports cleared.');
          resolve(true);
        }).on('error', e => {
          console.error(e.message.replace(/\n|\r/g, ''));
          reject();
        });
      });
    } catch (e) {
      console.error(String(e).replace(/\n|\r/g, ''));
    }
  });
};
