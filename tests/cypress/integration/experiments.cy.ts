describe('Import tab on profile page', () => {
  beforeEach(() => {
    cy.login();
  });

  it('can import eln and csv files', () => {
    // first ELN
    cy.visit('/profile.php?tab=3');
    cy.get('#importSelectCategory').should('exist');
    cy.get('#importSelectOwner').should('exist');
    cy.get('#import_modal_canread').should('exist');
    cy.get('#import_modal_canwrite').should('exist');
    // check if custom button exists and make form input visible
    cy.get('[data-action="show-file-input"]').should('exist').click();
    cy.get('#importFileInput').should('exist').selectFile('tests/_data/multiple-experiments.eln', { force: true });
    // Check that the file name is displayed
    cy.get('#fileName').should('be.visible').and('have.text','multiple-experiments.eln');
    cy.get('#importFileBtn').should('exist').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'File imported successfully');
    // now CSV
    cy.get('[data-action="show-file-input"]').should('exist').click();
    cy.get('#importFileInput').should('exist').selectFile('tests/_data/importable.csv', { force: true });
    cy.get('#fileName').should('be.visible').and('have.text','importable.csv');
    cy.get('#importFileBtn').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'File imported successfully');
  });
});
