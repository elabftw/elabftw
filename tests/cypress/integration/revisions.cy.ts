describe('Revisions', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Show revisions page', () => {
    cy.getExperimentId().then(expid => {
      cy.visit(`revisions.php?type=experiments&item_id=${expid}`);
      cy.htmlvalidate();
      cy.get('h1#pageTitle').should('have.text', 'Revisions');
    });
  });
});
