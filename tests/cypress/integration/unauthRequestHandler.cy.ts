describe('UnauthRequestHandler', () => {
  beforeEach(() => {
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('provides Json response', () => {
    cy.request('/app/controllers/UnauthRequestHandler.php').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/json');
      expect(resp.body).to.have.keys('privacy', 'privacy_name', 'tos', 'tos_name', 'a11y', 'a11y_name', 'legal', 'legal_name');
    });
  });
});
