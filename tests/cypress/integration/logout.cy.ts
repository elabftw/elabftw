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
});
