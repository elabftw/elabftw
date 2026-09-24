describe('Scheduler', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Displays Scheduler page', () => {
    // Scheduler
    cy.visit('/scheduler.php');
    cy.get('h1#pageTitle').should('have.text', 'Scheduler');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();
  });

  it('Display Scheduler with selected item', () => {
    // TODO: itemId currently has no real active use
    cy.request('PATCH', '/api/v2/users/me', { scope_items: 2 });
    cy.createBooking().then(itemId => {
      cy.visit(`/scheduler.php?items[]=${itemId}`);
      cy.get('#loading-spinner').should('not.exist');
      cy.get('#scopeBtn').should('not.be.visible');
      cy.get('#scopeItemsLocked').should('be.visible')
        .find('button').should('be.disabled')
        .find('i').should('have.class', 'fa-globe');
      cy.get('[data-cy="selectedItemsDisplay"]').should('contain', 'Cypress booked resource');
    });
  });
});
