describe('Resources', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('See page', () => {
    cy.visit('/database.php');
    cy.get('h1#pageTitle').should('have.text', 'Resources');
  });
});
