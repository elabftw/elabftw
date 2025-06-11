describe('Healthcheck', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Is OK', () => {
    cy.request('/healthcheck.php').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.body).to.eq('ok');
    });
  });
});
