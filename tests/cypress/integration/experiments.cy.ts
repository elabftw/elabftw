describe('Import tab on profile page', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('can import eln and csv files', () => {
    // first ELN
    cy.visit('/profile.php?tab=3');
    cy.get('#importSelectCategory').should('exist');
    cy.get('#importSelectOwner').should('exist');
    cy.get('#import_modal_canread').should('exist');
    cy.get('#import_modal_canwrite').should('exist');
    cy.get('#importFileInput').should('exist').selectFile('tests/_data/multiple-experiments.eln');
    /*
    cy.intercept('api/v2/import', req => {
      req.on('response', resp => {
        expect(resp.statusCode).to.equal(201);
      });
    }).as('importController');
   */
    cy.get('#importFileBtn').should('exist').click();
    //cy.wait('@importController');
    cy.get('#overlay').should('contain', 'File imported successfully');
    // now CSV
    cy.get('#importFileInput').should('exist').selectFile('tests/_data/importable.csv');
    /*
    cy.intercept('api/v2/import', req => {
      req.on('response', resp => {
        expect(resp.statusCode).to.equal(201);
      });
    }).as('importController');
   */
    cy.get('#importFileBtn').click();
    //cy.wait('@importController');
    cy.get('#overlay').should('contain', 'File imported successfully');
  });
});
