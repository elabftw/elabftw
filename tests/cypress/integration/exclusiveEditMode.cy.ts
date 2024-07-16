describe('Exclusive edit mode', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });
  const title = 'Entity with exclusive edit mode';
  // prepare an experiment with exclusive edit mode
  const setupEntityWithExclusiveEditMode = () => {
    cy.visit('/experiments.php');
    cy.contains('Create').click();
    cy.intercept('GET', '/api/v2/experiments/**').as('get');
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'Default template').contains('Default template').click();
    cy.url().should('include', 'mode=edit');
    cy.wait('@get');
    cy.intercept('PATCH', '/api/v2/experiments/**').as('api');
    cy.get('#documentTitle').click();
    cy.get('h1.text-dark').find('input').clear().type(title);
    cy.get('h1.text-dark').find('button').contains('Save').click();
    cy.wait('@api');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    cy.get('#exclusiveEditModeBtn span i').should('have.class', 'fa-lock-open').should('not.have.class', 'fa-lock');
    cy.get('#exclusiveEditModeBtn').click();
    cy.wait('@api');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    cy.get('#exclusiveEditModeBtn span i').should('have.class', 'fa-lock').should('not.have.class', 'fa-lock-open');
    cy.get('#exclusiveEditModeInfo').should('be.visible');
    cy.get('#date_input').type('2024-04-20').blur();
    cy.wait('@api');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    cy.get('[title="Select who can edit this entry"]').click();
    cy.get('#canwrite_select_base').should('be.visible').select('Only members of the team');
    cy.get('[data-identifier="canwrite"][data-action="save-permissions"]').click();
    cy.wait('@api');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    // log out Toto
    cy.request('/app/logout.php');
  };

  const accessEntityAsTiti = () => {
    // login as Titi
    cy.login('titi@yopmail.com');
    cy.visit('/experiments.php');
    cy.get('#showModeContent').contains(title).click();
    cy.url().should('include', 'mode=view');
    cy.intercept('experiments.php?mode=edit&id=*', req => {
      req.on('response', resp => {
        expect(resp.statusCode).to.equal(303);
      });
    }).as('redirect');
    cy.get('[aria-label="Edit"]').click();
    cy.wait('@redirect');
    cy.url().should('include', 'mode=view').should('include', 'openedInExclusiveEditMode');
    cy.get('[class="alert alert-warning"]')
      .should('contain', 'This entry is opened by Toto Le sysadmin in exclusive edit mode since')
      .should('contain', 'You cannot edit it now.')
      .should('contain', 'Request exclusive edit mode removal')
      .get('[data-action="request-exclusive-edit-mode-removal"]')
      .click();
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    // request twice to test request rejection
    cy.get('[data-action="request-exclusive-edit-mode-removal"]').click();
    cy.get('#overlay').should('be.visible').should('contain', 'Error: This action has been requested already');
    // silence 303 redirect intercept
    cy.intercept('experiments.php?mode=edit&id=*', req => req.continue());
    // log out Titi
    cy.request('/app/logout.php');
  };

  const checkReleaseWriteLockRequestNotification = () => {
    // login again as Toto
    cy.login();
    cy.visit('/experiments.php');
    cy.contains('is requesting removal of exclusive edit mode for').should('be.visible');
    cy.get('#showModeContent').contains(title).should('be.visible').click();
    cy.get('[aria-label="Edit"]').click();
    cy.contains('You opened this entry in exclusive edit mode at').should('be.visible');
    cy.get('#exclusiveEditModeBtn').click();
    cy.wait('@api');
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    cy.get('#exclusiveEditModeBtn span i').should('have.class', 'fa-lock-open').should('not.have.class', 'fa-lock');
    cy.contains('You opened this entry in exclusive edit mode at').should('not.exist');
  };

  it('Try to open entity with exclusive edit mode', () => {
    setupEntityWithExclusiveEditMode();
    accessEntityAsTiti();
    checkReleaseWriteLockRequestNotification();
  });
});
