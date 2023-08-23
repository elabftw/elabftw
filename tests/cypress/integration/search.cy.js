describe('Search page', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('shows important elements', () => {
    cy.visit('/search.php');
    cy.get('h1#pageTitle').should('have.text', 'Advanced search');
    cy.get('table#itemList').should('not.exist');
  });

  it('lists results', () => {
    cy.visit('/search.php?type=experiments&q=author%3A%25&');
    cy.get('h2').should('have.text', 'Results');
    cy.get('table#itemList').should('be.visible');

    cy.visit('/search.php?type=1&q=author%3A%25&');
    cy.get('h2').should('have.text', 'Results');
    cy.get('table#itemList').should('be.visible');
  });
});
