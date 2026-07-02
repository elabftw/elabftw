type RorsPage = {
  path: string;
  firstRor: string;
  secondRor: string;
};

const pages: RorsPage[] = [
  {
    path: '/sysconfig.php',
    firstRor: '02feahw73',
    secondRor: '04vfs2w97',
  },
  {
    path: '/admin.php',
    firstRor: '003vg9w96',
    secondRor: '05q3vnk25',
  },
  {
    path: '/profile.php',
    firstRor: '04rcqnp59',
    secondRor: '04t0gwh46',
  },
];

describe('RORs component', () => {
  pages.forEach(({ path, firstRor, secondRor }) => {
    it(`adds RORs and deletes one on ${path}`, () => {
      cy.login();
      cy.visit(path);

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

      cy.get('@rors')
        .find('#rorsTable')
        .contains('tr', secondRor)
        .find('button[aria-label]')
        .click();

      cy.get('@rors')
        .should('not.contain', secondRor);
    });
  });
});
