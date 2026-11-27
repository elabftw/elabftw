describe('Template type labels', () => {
  const templates = [
    { endpoint: 'experiments_templates', page: 'templates.php', label: 'Experiment template' },
    { endpoint: 'items_types', page: 'resources-templates.php', label: 'Resource template' },
  ];

  templates.forEach(({ endpoint, page, label }) => {
    describe(label, () => {
      let templateId: number | undefined;

      beforeEach(() => {
        templateId = undefined;
        cy.login();
        cy.request('POST', `/api/v2/${endpoint}`, { title: `Cypress ${label}` }).then(response => {
          expect(response.status).to.eq(201);
          cy.extractIdFromLocation(response).then(id => {
            templateId = id;
          });
        });
      });

      afterEach(() => {
        if (templateId !== undefined) {
          cy.request('DELETE', `/api/v2/${endpoint}/${templateId}`);
        }
      });

      it('shows the type label in view and edit modes', () => {
        for (const mode of ['view', 'edit']) {
          cy.visit(`/${page}?mode=${mode}&id=${templateId}`);
          cy.get('[data-cy="entity-type-label"]').should('be.visible').and('have.text', label);
        }
      });
    });
  });
});
