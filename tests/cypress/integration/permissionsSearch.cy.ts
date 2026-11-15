describe('Permissions modal search', () => {
  beforeEach(() => {
    cy.login();
  });

  it('only searches for non-archived users', () => {
    cy.intercept({ method: 'GET', pathname: '/api/v2/users/' }).as('searchUsers');
    cy.createEntity();
    cy.window().its('jQuery').then(($: JQueryStatic) => {
      $('#permissionsModal').one('shown.bs.modal', cy.stub().as('permissionsShown'));
    });
    cy.get('[data-cy="permissions-button"]').click();
    // wait for the end of the fade: Bootstrap moves the focus then, which would close the search dropdown
    cy.get('@permissionsShown').should('have.been.calledOnce');
    cy.get('#canread-permissions-search-input').type('Titi');
    cy.wait('@searchUsers').its('request.query.onlyActive').should('eq', '1');
    cy.get('#canread_permissions_search-wrapper .ts-dropdown').contains('Titi').should('be.visible');
  });
});
