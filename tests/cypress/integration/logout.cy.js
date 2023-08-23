describe('Logout', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('Logout', () => {
    cy.request({
      url: '/app/logout.php',
      followRedirect: false,
    }).then(resp => {
      expect(resp.status).to.eq(302);
      expect(resp.headers.location).to.eq('../login.php');
    });
  });
});
