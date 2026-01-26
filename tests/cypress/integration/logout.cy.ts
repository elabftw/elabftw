describe('Logout', () => {
  beforeEach(() => {
    cy.login();
  });

  it('redirects to login.php', () => {
    cy.request({
      url: '/app/logout.php?after_logout=1',
      followRedirect: false,
    }).then(resp => {
      expect(resp.status).to.eq(302);
      expect(resp.headers.location).to.eq('/login.php?after_logout=1');
    });
  });

  it ('does show message to close the browser', () => {
    cy.clearCookies();
    cy.visit('/login.php?after_logout=1');
    cy.get('#logoutMessage').should('exist');
  });
});
