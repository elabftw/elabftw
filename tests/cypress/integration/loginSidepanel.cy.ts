describe('Login with a saved sidebar', () => {
  it('opens the password reset modal without a saved sidebar', () => {
    cy.visit('/login.php');
    cy.get('[data-target="resetModal"]').click();
    cy.get('#resetModal').should('be.visible');
  });

  for (const panel of ['todolist', 'favtags']) {
    it(`opens the password reset modal with a saved ${panel} sidebar`, () => {
      cy.visit('/login.php', {
        onBeforeLoad(win) {
          win.localStorage.setItem('opened-sidepanel', panel);
        },
      });

      cy.get('#todolistPanel, #favtagsPanel').should('not.exist');
      cy.get('[data-target="resetModal"]').click();
      cy.get('#resetModal').should('be.visible');
      cy.window().then(win => {
        expect(win.localStorage.getItem('opened-sidepanel')).to.eq(panel);
      });
    });

    it(`restores the saved ${panel} sidebar on an authenticated page`, () => {
      cy.login();
      cy.visit('/dashboard.php', {
        onBeforeLoad(win) {
          win.localStorage.setItem('opened-sidepanel', panel);
        },
      });

      cy.get(`#${panel}Panel`).should('be.visible');
      cy.get(`#${panel}PanelOpener`).should('have.attr', 'aria-expanded', 'true');
    });
  }
});
