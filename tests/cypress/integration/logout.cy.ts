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

  it ('does show message to close the browser', () => {
    cy.clearCookies();
    cy.visit('/login.php');
    //  cy.get('.overlay').first().should('be.visible').should('contain', 'You logged out. Please close your browser to prevent access to previous content.');
  });
});
