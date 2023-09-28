describe('Search page', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('shows important elements', () => {
    cy.visit('/search.php');
    cy.get('h1#pageTitle').should('have.text', 'Advanced search');
    cy.get('input#extendedArea').should('exist');
    cy.get('select#searchin').should('exist');
    cy.get('select#searchonly').should('exist');
    cy.get('select#dateOperator').should('exist');
    cy.get('input#date').should('exist');
    cy.get('input#dateTo').should('exist');
    cy.get('select#visibility').should('exist');
    cy.get('select#rating').should('exist');
    cy.get('select#locked').should('exist');
    cy.get('select#timestamped').should('exist');
    cy.get('button#searchButton').should('exist');

    cy.get('table#itemList').should('not.exist');
  });

  it('lists results', () => {
    cy.visit('/search.php?type=experiments&q=author%3A%25&');
    cy.get('h2').should('have.text', 'Results');
    cy.get('#itemList').should('exist');

    cy.visit('/search.php?type=1&q=author%3A%25&');
    cy.get('h2').should('have.text', 'Results');
    cy.get('#itemList').should('exist');
  });
});
