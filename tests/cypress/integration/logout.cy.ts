describe('Logout', () => {
  beforeEach(() => {
    cy.login();
  });

  it('redirects to login.php', () => {
    cy.request({
      url: '/app/logout.php',
      followRedirect: false,
    }).then(resp => {
      expect(resp.status).to.eq(302);
      expect(resp.headers.location).to.eq('/login.php');
    });
  });

  it('shows message to close the browser when user logged out', () => {
    cy.visit('/dashboard.php');
    cy.get('#navbarDropdown').should('exist').click();
    cy.get('[data-action="logout"]').click();
    cy.location('pathname').should('include', '/login.php');
    cy.get('#logoutMessage').should('exist').should('not.have.attr', 'hidden');
    cy.clearCookies();
  });
});
