describe('Metadata', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('Show metadata', () => {
    cy.visit('/metadata.php');
    cy.get('body').should('contain', 'Nothing to show with this id');
  });
});
