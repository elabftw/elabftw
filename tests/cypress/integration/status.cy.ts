describe('Status', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Create, update and delete a status', () => {
    const newname = 'Cypress :: New status name';
    cy.visit('/experiments-status.php');
    /*
    cy.window().should(win => {
      const keys = win.__actionHandlers__ ? Array.from(win.__actionHandlers__.keys()) : [];
      expect(keys).to.include('toggle-modal');
    });
   */
    cy.get('[data-target="createCatStatModal"]').click();
    cy.get('#createCatStatModal').should('be.visible');
    // the wait is necessary or it doesn't have the time to type all
    cy.get('#createCatStatName').wait(500).type(newname);
    cy.get('[data-action="create-catstat"]').filter(':visible').click();
    // need to wait for it to appear
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
    cy.get('[data-action="destroy-catstat"][data-endpoint="experiments_status"]').then(allBtn => {
      const elementWithHighestDataId = Array.from(allBtn).reduce((prev, current) => {
        const prevDataId = parseInt(prev.dataset.id);
        const currentDataId = parseInt(current.dataset.id);
        return prevDataId > currentDataId ? prev : current;
      });
      const id = elementWithHighestDataId.dataset.id;
      cy.get(`[data-action="destroy-catstat"][data-endpoint="experiments_status"][data-id="${id}"]`).click();
    });
  });
});
