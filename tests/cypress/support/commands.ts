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
  return cy
    .request({
      method: 'GET',
      url: '/api/v2/experiments?scope=1',
    })
    .then((response) => {
      expect(response.status).to.eq(200);

      const experiments = response.body;
      if (Array.isArray(experiments) && experiments.length > 0) {
        // Return the ID of the first experiment if it exists
        return experiments[0].id;
      }
      // toto won't have any experiment by default
      return cy
        .request({ method: 'POST', url: '/api/v2/experiments', body: {} })
        .then((postRes) => {
          expect(postRes.status).to.eq(201);

          // Extract Location header
          const locationHeader = postRes.headers['location'] || postRes.headers['Location'];
          if (!locationHeader) {
            throw new Error('Location header not found in create experiment response');
          }

          // The Location header may include ports and full paths, e.g. https://elab.local:3148/api/v2/experiments/17
          // Split the URL by '/' and take the last segment as the ID
          const segments = locationHeader.split('/');
          const idSegment = segments.pop();
          const id = idSegment && !isNaN(Number(idSegment)) ? Number(idSegment) : null;
          if (id === null) {
            throw new Error(`Cannot parse experiment ID from Location header: ${locationHeader}`);
          }
          return id;
        });
    });
});
// create new entity (experiment or item. default: experiment)
Cypress.Commands.add('createEntity', (
  type: 'experiment' | 'item' = 'experiment',
  title = `Cypress ${type} ${Date.now()}`,
) => {
  const config = {
    experiment: { page: '/experiments.php', modal: '#createModal' },
    item: { page: '/database.php', modal: '#createModal' },
  }[type];
  cy.visit(config.page);
  cy.get('[data-action="toggle-create-modal"]').last().click();
  cy.get(config.modal).should('be.visible');
  // create modal -> enter title & confirm
  // do not use .type() here or for some reason the title won't be complete...
  cy.get('#createNewFormTitle')
    .invoke('val', title)
    .trigger('input');
  cy.get('[data-cy="create-entity"]').click();
  // ensure we navigated to the new entry
  cy.get('#documentTitle').should('contain', title);
  cy.url().should('include', 'mode=edit');
});
// metadata helpers
Cypress.Commands.add('addMetadataField', (fieldName: string, type: string) => {
  cy.get('[data-cy="addMetadataField"]').first().click();
  cy.get('#fieldBuilderModal').should('be.visible');
  cy.get('#newFieldTypeSelect').select(type);
  cy.get('#newFieldKeyInput').wait(500).type(fieldName);
  cy.get('[data-action="save-new-field"]').click();
  cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
  cy.get('#metadataDiv').should('be.visible').should('contain', fieldName);
});
Cypress.Commands.add('addTextMetadataField', (fieldName: string) => {
  cy.addMetadataField(fieldName, 'text');
});
Cypress.Commands.add('addUserMetadataField', (fieldName: string, username = 'Titi') => {
  cy.addMetadataField(fieldName, 'users');
  cy.get(`[data-field="${fieldName}"][data-target="users"]`).wait(500).type(`${username}{enter}`);
  cy.get('ul.ui-autocomplete').should('be.visible').contains('div.ui-menu-item-wrapper', username).click();
  cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
});
Cypress.Commands.add('removeMetadataField', () => {
  cy.get('#metadataDiv').should('be.visible');
  cy.on('window:confirm', () => true);
  cy.get('[data-action="metadata-rm-field"]').click();
});
