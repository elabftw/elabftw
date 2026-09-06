describe('admin page', () => {
  beforeEach(() => {
    cy.login();
  });

  it('has valid html', () => {
    cy.visit('/admin.php?');
    cy.get('h1#pageTitle').should('have.text', 'Admin panel');
    // wait for page and tinymce to load before htmlvalidate
    cy.get('#loading-spinner').should('not.exist');
    cy.get('div.tox-menubar').contains('File').should('exist');
    cy.htmlvalidate();

    for (let i = 1; i <= 6; i++) {
      cy.visit(`/admin.php?tab=${i}`);
      cy.get(`[data-tabtarget="${i}"]`).should('have.class', 'selected');
      cy.get('#loading-spinner').should('not.exist');
    }
  });

  it('filters user groups', () => {
    const group1 = `Cypress Filter Alpha ${Date.now()}`;
    const group2 = `Cypress Filter Beta ${Date.now()}`;

    cy.request({
      method: 'POST',
      url: '/api/v2/teams/current/teamgroups',
      body: { name: group1 },
    }).then(response => {
      expect(response.status).to.eq(201);
    });

    cy.request({
      method: 'POST',
      url: '/api/v2/teams/current/teamgroups',
      body: { name: group2 },
    }).then(response => {
      expect(response.status).to.eq(201);
    });

    cy.visit('/admin.php?tab=2');

    cy.get('#teamGroupsFilter').should('contain.text', group1);
    cy.get('#teamGroupsFilter').should('contain.text', group2);

    cy.get('input[data-filter-target="teamGroupsFilter"]')
      .type(group1);

    cy.get('#teamGroupsFilter .team-group-filter-item')
      .contains(group1)
      .should('be.visible');

    cy.get('#teamGroupsFilter .team-group-filter-item')
      .contains(group2)
      .should('not.be.visible');
  });

});
