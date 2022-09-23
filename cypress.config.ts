import { defineConfig } from 'cypress'

export default defineConfig({
  fixturesFolder: 'tests/cypress/fixtures',
  screenshotsFolder: 'tests/cypress/screenshots',
  videosFolder: 'tests/cypress/videos',
  e2e: {
    setupNodeEvents(on, config) {
      return require('./tests/cypress/plugins/index.js')(on, config)
    },
    baseUrl: 'https://elab.local:3148',
    specPattern: 'tests/cypress/integration/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'tests/cypress/support/index.js',
  },
})
