describe('Status in admin panel', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Create, update and delete a status', () => {
    const newname = 'Cypress :: New status name';
    cy.visit('/admin.php?tab=5');
    cy.get('[data-target="createexperiments_statusModal"]').click();
    cy.get('#createexperiments_statusName').type(newname);
    cy.get('[data-action="create-statuslike"]').filter(':visible').click();
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
    cy.get('[data-action="destroy-status"][data-target="experiments_status"]').last().click();
    cy.get('#overlay').should('be.visible').should('contain', 'Saved');
  });
});
