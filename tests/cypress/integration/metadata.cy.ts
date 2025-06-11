describe('Metadata', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Show metadata', () => {
    cy.visit('/metadata.php');
    cy.get('body').should('contain', 'Nothing to show with this id');
  });
});
