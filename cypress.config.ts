import { defineConfig } from 'cypress';
import htmlvalidate from 'cypress-html-validate/plugin';
import { Severity } from 'html-validate';

export default defineConfig({
  fixturesFolder: 'tests/cypress/fixtures',
  screenshotsFolder: 'tests/cypress/screenshots',
  video: true,
  videosFolder: 'tests/cypress/videos',
  viewportWidth: 1440,
  viewportHeight: 900,
  e2e: {
    setupNodeEvents(on, config) {
      require('cypress-terminal-report/src/installLogsPrinter')(on, {printLogsToConsole: 'always'})
      htmlvalidate.install(
        on,
        {
          extends: [
            'html-validate:document',
            'html-validate:standard',
            'html-validate:a11y',
          ],
          rules: {
            'heading-level': Severity.DISABLED, // should be WARN but does not work, TODO: fix violations
            // we keep autocomplete on checkboxes because it's important in firefox
            'valid-autocomplete': Severity.DISABLED,
            'require-sri': [Severity.ERROR, {
              target: 'crossorigin',
              exclude: ['https://elabtmp', 'https://elab.local:3148'], // this is treated as crossorigin so we exclude it
            }],
          },
        },
        {
          exclude: [
            '#scheduler', // scheduler on the team page has several violations
            'h3[data-action="toggle-next"]', // these get the attribute role='button' ...
            'h4[data-action="toggle-next"]', // ... hence, trigger prefer-native-element
          ],
        },
      );
      return require('./tests/cypress/plugins/index.ts')(on, config);
    },
    baseUrl: 'https://elab.local:3148',
    specPattern: 'tests/cypress/integration/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'tests/cypress/support/index.ts',
  },
  // give more time
  defaultCommandTimeout: 15000,
  requestTimeout: 10000,
  responseTimeout: 130000,
  taskTimeout: 300000,
});
