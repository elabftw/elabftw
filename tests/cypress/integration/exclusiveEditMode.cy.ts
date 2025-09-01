describe('Exclusive edit mode', () => {
  beforeEach(() => {
    cy.login();
  });
  const title = 'Entity with exclusive edit mode';
  // prepare an experiment with exclusive edit mode
  const setupEntityWithExclusiveEditMode = () => {
    // ignore window.onbeforeunload so we can keep the exclusive edit mode running
    cy.on('window:before:load', (win) => {
      Object.defineProperty(win, 'onbeforeunload', {
        get: () => null,
        set: () => {}, // swallow
      });
    });
    cy.visit('/experiments.php');
    cy.contains('Create').click();
    cy.get('#createModal_experiments').should('be.visible').should('contain', 'No category').contains('No category').click();
    cy.get('#askTitleModalTitleInput').should('be.visible').wait(500).type('Cypress created entity').click();
    cy.get('#askTitleButton').click();
    cy.url().should('include', 'mode=edit');
    cy.intercept('PATCH', '/api/v2/experiments/**').as('apiPATCH');
    cy.get('#documentTitle').click();
    cy.get('h1.text-dark').find('input').clear().type(title).blur();
    cy.wait('@apiPATCH');
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    // edit mode is always exclusive as of 2025/03, without clicking on a specific button
    cy.get('#date_input').type('2024-04-20').blur();
    cy.wait('@apiPATCH');
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    cy.get('[title="Select who can edit this entry"]').click();
    cy.get('#canwrite_select_base').should('be.visible').select('Only members of the team');
    cy.get('[data-identifier="canwrite"][data-action="save-permissions"]').click();
    cy.wait('@apiPATCH');
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
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
    cy.get('[aria-label="Edit"]').should('have.class', 'disabled');
    cy.get('[class="alert alert-warning"]').should('contain', 'This entry is being edited by Toto Le sysadmin');
    // now test the button
    cy.get('[data-action="override-exclusive-edit-lock"]').click();
    cy.get('body').should('not.contain', 'This entry is being edited by Toto Le sysadmin');
    cy.get('[aria-label="Edit"]').should('not.have.class', 'disabled');
  };

  it('Try to open entity with exclusive edit mode', () => {
    setupEntityWithExclusiveEditMode();
    accessEntityAsTiti();
  });
});
