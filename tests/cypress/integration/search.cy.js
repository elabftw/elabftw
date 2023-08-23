describe('Search', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('Show search page', () => {
    cy.visit('/search.php');
    cy.get('h1#pageTitle').should('have.text', 'Advanced search');
  });
});
