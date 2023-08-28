import { defineConfig } from 'cypress'

export default defineConfig({
  fixturesFolder: 'tests/cypress/fixtures',
  screenshotsFolder: 'tests/cypress/screenshots',
  videosFolder: 'tests/cypress/videos',
  viewportWidth: 1440,
  viewportHeight: 900,
  e2e: {
    setupNodeEvents(on, config) {
      return require('./tests/cypress/plugins/index.ts')(on, config)
    },
    baseUrl: 'https://elab.local:3148',
    specPattern: 'tests/cypress/integration/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'tests/cypress/support/index.ts',
  },
  // give more time because Xdebug slows down php a bit
  defaultCommandTimeout: 8000,
  requestTimeout: 10000,
  responseTimeout: 130000,
})
