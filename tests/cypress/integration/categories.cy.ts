describe('Categories', () => {
  beforeEach(() => {
    cy.login();
  });

    it('Preview resource category colors', () => {
    const catname = 'Justice';

    cy.intercept('PATCH', '**/api/v2/teams/current/resources_categories/*').as('patchCategory');

    cy.visit('/resources-categories.php');

    cy.get(`[data-cy="expcatName"][value="${catname}"]`)
      .closest('[data-catstat-row]')
      .as('categoryRow');

    // update background color and immediately update preview
    cy.get('@categoryRow')
      .find<HTMLInputElement>('[data-target="color"]')
      .invoke('val', '#ffffff')
      .trigger('input')
      .trigger('change');

    cy.wait('@patchCategory');

    // update text color and immediately update preview
    cy.get('@categoryRow')
      .find<HTMLInputElement>('[data-target="color_fg"]')
      .invoke('val', '#000000')
      .trigger('input')
      .trigger('change');

    cy.wait('@patchCategory');

    // preview uses the selected colors
    cy.get('@categoryRow')
      .find('[data-catstat-preview]')
      .should('contain', catname)
      .and('have.css', 'background-color', 'rgb(255, 255, 255)')
      .and('have.css', 'color', 'rgb(0, 0, 0)');

    // white/black has the maximum WCAG contrast ratio
    cy.get('@categoryRow')
      .find('[data-catstat-contrast]')
      .should('contain', 'AAA 21.0:1');

    // colors are persisted
    cy.reload();

    cy.get(`[data-cy="expcatName"][value="${catname}"]`)
      .closest('[data-catstat-row]')
      .within(() => {
        cy.get('[data-target="color"]').should('have.value', '#ffffff');
        cy.get('[data-target="color_fg"]').should('have.value', '#000000');
        cy.get('[data-catstat-preview]')
          .should('have.css', 'background-color', 'rgb(255, 255, 255)')
          .and('have.css', 'color', 'rgb(0, 0, 0)');
      });
  });
});
