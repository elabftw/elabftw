describe('Experiments templates', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Create and edit an experiment template', () => {
    cy.visit('/ucp.php?tab=3');
    cy.htmlvalidate();
    // stub the window.prompt() because that's the only way in cypress to input something into a prompt()
    cy.window().then(win => {
      cy.stub(win, 'prompt').returns('Cypress created template');
      // create
      cy.get('button[data-action="create-template"]').click();
      // destroy
      cy.get('button[title="More options"]').click().get('button[data-action="destroy-template"]').click();
    });
  });
});
