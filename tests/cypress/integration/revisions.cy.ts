describe('Revisions', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Show revisions page', () => {
    cy.visit('revisions.php?type=experiments&item_id=1');
    cy.htmlvalidate();
    cy.get('h1#pageTitle').should('have.text', 'Revisions');
  });
});
