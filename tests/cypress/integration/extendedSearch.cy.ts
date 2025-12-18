describe('Search', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Searches an experiment using extended metadata syntax', () => {
    const fieldName = 'Raw data URL';
    // create new experiment
    cy.createEntity().then(() => {
      cy.get('#documentTitle').invoke('text').then((title) => {
        const trimmedTitle = title.trim();
        cy.addTextMetadataField(fieldName);
        // Perform extended search
        cy.visit('experiments.php');
        const query = `extrafield:"${fieldName}":%`;
        cy.get('#extendedArea').should('be.visible').type(`${query}{enter}`);
        cy.url().should('include', 'q=');
        // Assert the experiment is visible
        cy.get('#itemList').should('be.visible').contains(trimmedTitle).should('exist');
      });
    });
  });
});
