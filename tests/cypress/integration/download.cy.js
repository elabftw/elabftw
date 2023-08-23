describe('Download', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('Download a file', () => {
    cy.request('/app/download.php').then(resp => {
      expect(resp.status).to.eq(200);
      cy.log(resp);
    });
    cy.request('/app/download.php?f=notvalid').then(resp => {
      expect(resp.status).to.eq(200);
      cy.log(resp);
    });
  });
});
