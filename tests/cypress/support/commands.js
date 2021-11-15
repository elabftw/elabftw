// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('login', () => {
  const email = 'toto@yopmail.com';
  const password = 'totototo';
  cy.request('/login.php')
    .its('body')
    .then((body) => {
      const $html = Cypress.$(body)
      const csrf = $html.filter('meta[name="csrf-token"]').attr('content')
      cy.request({
        method: 'POST',
        url: '/app/controllers/LoginController.php',
        failOnStatusCode: false, // dont fail so we can make assertions
        form: true, // we are submitting a regular form body
        body: {
        email: email,
        password: password,
        auth_type: 'local',
        rememberme: 'on',
        csrf: csrf,
        },
      })
      .then((resp) => {
      expect(resp.status).to.eq(200)
    })
  })
  visitExperiments()
})

/**
 * A utility function to check that we are seeing the dashboard page
 */
const inExperiments = () => {
  cy.url().should('include', '/experiments.php');
  cy.contains('h1', 'Experiments')
  // our auth cookie should be present
  cy.getCookie('token').should('exist');
  cy.getCookie('token_team').should('exist');
  // UI should reflect this user being logged in
  cy.get('h6').should('contain', 'Toto');
}

/**
 * A utility function to confirm we can visit a protected page
 */
const visitExperiments = () => {
  cy.visit('/experiments.php')
  inExperiments()
}
