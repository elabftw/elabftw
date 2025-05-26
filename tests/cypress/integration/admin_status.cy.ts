describe('Status in admin panel', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Create, update and delete a status', () => {
    const newname = 'Cypress :: New status name';
    cy.visit('/admin.php?tab=5');
    cy.get('[data-target="createexperiments_statusModal"]').click();
    cy.get('#createexperiments_statusModal').should('be.visible');
    // the wait is necessary or it doesn't have the time to type all
    cy.get('#createexperiments_statusName').wait(500).type(newname);
    cy.get('[data-action="create-statuslike"]').filter(':visible').click();
    // need to wait for it to appear
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved').wait(400);
    cy.get('[data-action="destroy-catstat"][data-target="experiments_status"]').then(allBtn => {
      const elementWithHighestDataId = Array.from(allBtn).reduce((prev, current) => {
        const prevDataId = parseInt(prev.dataset.id);
        const currentDataId = parseInt(current.dataset.id);
        return prevDataId > currentDataId ? prev : current;
      });
      const id = elementWithHighestDataId.dataset.id;
      cy.get(`[data-action="destroy-catstat"][data-target="experiments_status"][data-id="${id}"]`).click();
      // this doesn't work so we get the element instead
      //cy.wrap(elementWithHighestDataId).click();
    });
  });
});
