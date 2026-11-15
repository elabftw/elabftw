describe('Permissions modal search', () => {
  beforeEach(() => {
    cy.login();
  });

  it('only searches for non-archived users', () => {
    cy.intercept({ method: 'GET', pathname: '/api/v2/users/' }).as('searchUsers');
    cy.createEntity();
    cy.get('[data-cy="permissions-button"]').click();
    cy.get('#canread-permissions-search-input').should('be.visible').type('Titi');
    cy.wait('@searchUsers').its('request.url').should('include', 'onlyActive=1');
    cy.get('#canread_permissions_search-wrapper').contains('Titi').should('be.visible');
  });
});
