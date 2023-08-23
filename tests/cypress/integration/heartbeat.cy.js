describe('Heart Beat', () => {
  beforeEach(() => {
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('is normal', () => {
    cy.login();
    cy.request({
      url: '/app/controllers/HeartBeat.php',
      headers: { Cookie: { PHPSESSID: cy.getCookie('PHPSESSID').value } },
    }).then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('is dead line', () => {
    cy.request({
      url:'/app/controllers/HeartBeat.php',
      failOnStatusCode: false,
    }).then(resp => {
      expect(resp.status).to.eq(401);
    });
  });
});
