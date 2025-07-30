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

Cypress.Commands.add('login', (email = 'toto@yopmail.com', password = 'totototototo') => {
  cy.request('/login.php')
    .its('body')
    .then(body => {
      const $html = Cypress.$(body);
      const csrf: string = $html.filter('meta[name="csrf-token"]').attr('content');
      cy.request({
        method: 'POST',
        url: '/app/controllers/LoginController.php',
        failOnStatusCode: false, // don't fail so we can make assertions
        form: true, // we are submitting a regular form body
        body: {
          email: email,
          password: password,
          auth_type: 'local',
          rememberme: 'on',
          csrf: csrf,
        },
      }).then(resp => {
        expect(resp.status).to.eq(200);
        return cy.wrap(csrf);
      });
    });
});
Cypress.Commands.add('getExperimentId', () => {
  // Perform a GET request to the experiments endpoint
  return cy
    .request({
      method: 'GET',
      url: '/api/v2/experiments?scope=1',
    })
    .then((response) => {
      // Assert that the request was successful
      expect(response.status).to.eq(200);

      const experiments = response.body;
      // Ensure we have at least one experiment
      if (!Array.isArray(experiments) || experiments.length === 0) {
        throw new Error('No experiments found in the response');
      }

      // Return the ID of the first experiment
      return experiments[0].id;
    });
});
