describe('Sysconfig', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('Show sysconfig page', () => {
    cy.visit('/sysconfig.php');
    cy.get('h1#pageTitle').should('have.text', 'eLabFTW Configuration');
    cy.get('[data-tabtarget="1"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=2');
    cy.get('[data-tabtarget="2"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=3');
    cy.get('[data-tabtarget="3"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=4');
    cy.get('[data-tabtarget="4"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=5');
    cy.get('[data-tabtarget="5"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=6');
    cy.get('[data-tabtarget="6"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=7');
    cy.get('[data-tabtarget="7"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=8');
    cy.get('[data-tabtarget="8"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=9');
    cy.get('[data-tabtarget="9"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=10');
    cy.get('[data-tabtarget="10"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=11');
    cy.get('[data-tabtarget="11"]').should('have.class', 'selected')

    cy.visit('/sysconfig.php?tab=12');
    cy.get('[data-tabtarget="12"]').should('have.class', 'selected')
  });
});
