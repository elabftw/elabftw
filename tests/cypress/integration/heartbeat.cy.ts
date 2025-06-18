describe('Heart Beat', () => {
  it('is normal', () => {
    cy.login();

    let phpSessId: string;
    cy.getCookie('PHPSESSID')
      .should('exist')
      .then(c => phpSessId = c.value);

    cy.request({
      url: '/app/controllers/HeartBeat.php',
      headers: {
        Cookie: {
          PHPSESSID: phpSessId,
        },
      },
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
