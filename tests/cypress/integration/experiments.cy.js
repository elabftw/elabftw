describe('Experiments', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('See page', () => {
    cy.visit('/experiments.php')
    cy.get('h1#pageTitle').should('have.text', 'Experiments');
    });
});
