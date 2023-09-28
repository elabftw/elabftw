describe('Metadata', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Show metadata', () => {
    cy.visit('/metadata.php');
    cy.get('body').should('contain', 'Nothing to show with this id');
  });
});
