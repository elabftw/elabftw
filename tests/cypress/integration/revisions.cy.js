describe('Revisions', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('Show revisions page', () => {
    cy.visit('revisions.php?type=experiments&item_id=1');
    cy.get('h1#pageTitle').should('have.text', 'Revisions');
  });
});
