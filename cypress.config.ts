import { defineConfig } from 'cypress';
import htmlvalidate from 'cypress-html-validate/plugin';
import { Severity } from 'html-validate';

export default defineConfig({
  fixturesFolder: 'tests/cypress/fixtures',
  screenshotsFolder: 'tests/cypress/screenshots',
  videosFolder: 'tests/cypress/videos',
  viewportWidth: 1440,
  viewportHeight: 900,
  e2e: {
    setupNodeEvents(on, config) {
      htmlvalidate.install(
        on,
        {
          extends: [
            'html-validate:document',
            'html-validate:standard',
            //'html-validate:a11y',
          ],
          rules: {
            'heading-level': Severity.DISABLED, // should be WARN but does not work, TODO: fix violations
            'require-sri': [Severity.ERROR, {
              target: 'crossorigin',
              exclude: ['https://elabtmp'], // this is treated as crossorigin so we exclude it
            }],
          },
        },
        {
          exclude: [
            '#sketcher_search_dialog', // chemdoodle 2d-sketcher
          ],
        },
      );
      return require('./tests/cypress/plugins/index.ts')(on, config);
    },
    baseUrl: 'https://elab.local:3148',
    specPattern: 'tests/cypress/integration/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'tests/cypress/support/index.ts',
  },
  // give more time because Xdebug slows down php a bit
  defaultCommandTimeout: 8000,
  requestTimeout: 10000,
  responseTimeout: 130000,
  taskTimeout: 300000,
});
