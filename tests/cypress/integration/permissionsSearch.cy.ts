describe('Permissions modal search', () => {
  beforeEach(() => {
    cy.login();
  });

  it('only searches for non-archived users', () => {
    // two accounts matching the same search: only the active one can be given access
    const token = `Permsearch${Date.now()}`;
    const createUser = (firstname: string) => cy.request('POST', '/api/v2/users', {
      firstname,
      lastname: token,
      email: `${firstname.toLowerCase()}-${token.toLowerCase()}@example.com`,
      team: 1,
    }).then(response => cy.extractIdFromLocation(response));
    createUser('Active');
    createUser('Archived').then(userid => cy.request('PATCH', `/api/v2/users/${userid}`, { action: 'archive' }));

    cy.intercept({ method: 'GET', pathname: '/api/v2/users/' }).as('searchUsers');
    cy.createEntity();
    cy.window().its('jQuery').then(($: JQueryStatic) => {
      $('#permissionsModal').one('shown.bs.modal', cy.stub().as('permissionsShown'));
    });
    cy.get('[data-cy="permissions-button"]').click();
    // wait for the end of the fade: Bootstrap moves the focus then, which would close the search dropdown
    cy.get('@permissionsShown').should('have.been.calledOnce');
    cy.get('#canread-permissions-search-input').type(token);
    cy.wait('@searchUsers').its('request.query.onlyActive').should('eq', '1');
    cy.get('#canread_permissions_search-wrapper .ts-dropdown')
      .should('be.visible')
      .and('contain', `Active ${token}`)
      .and('not.contain', `Archived ${token}`);
  });
});
