describe('RORs component', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/sysconfig.php');
  });

  const firstRor = '02feahw73';
  const secondRor = '04vfs2w97';

  it('adds RORs and deletes one', () => {
    cy.get('[data-svelte-component="rors"]')
      .first()
      .as('rors');

    cy.get('@rors')
      .find('input[name="ror"]')
      .clear()
      .type(firstRor);

    cy.get('@rors')
      .find('form')
      .submit();

    cy.get('@rors')
      .find('#rorsTable')
      .should('contain', firstRor);

    cy.get('@rors')
      .find('input[name="ror"]')
      .clear()
      .type(`https://ror.org/${secondRor}`);

    cy.get('@rors')
      .find('form')
      .submit();

    cy.get('@rors')
      .find('#rorsTable')
      .should('contain', secondRor);

    cy.window().then(win => {
      cy.stub(win, 'confirm').returns(true);
    });

    cy.get('@rors')
      .find('#rorsTable')
      .contains('tr', firstRor)
      .find('button[aria-label]')
      .click();

    cy.get('@rors')
      .find('#rorsTable')
      .should('not.contain', firstRor)
      .and('contain', secondRor);
  });
});
