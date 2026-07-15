describe('TinyMCE templates', () => {
  let templateId: number | null = null;

  const templateTitle = `Cypress TinyMCE template ${Date.now()}`;
  const templateText = 'Cypress TinyMCE template body inserted';
  const templateBody = `<p>${templateText}</p>`;

  beforeEach(() => {
    cy.login();
  });

  afterEach(() => {
    if (templateId !== null) {
      cy.request({
        method: 'DELETE',
        url: `/api/v2/experiments_templates/${templateId}`,
        failOnStatusCode: false,
      });
      templateId = null;
    }
  });

  const getIframeBody = (selector: string) => cy
    .get<HTMLIFrameElement>(selector)
    .its('0.contentDocument.body')
    .should('not.be.empty')
    .then(cy.wrap);

  it('displays and inserts an experiment template from TinyMCE', () => {
    cy.request({
      method: 'POST',
      url: '/api/v2/experiments_templates',
      body: {
        title: templateTitle,
        body: templateBody,
      },
    }).then(response => {
      expect(response.status).to.eq(201);
      cy.extractIdFromLocation(response).then(id => {
        templateId = id;
      });
    });

    cy.intercept('GET', '**/api/v2/experiments_templates*').as('getTemplates');

    cy.createEntity('experiment', 'Cypress TinyMCE template experiment').then(() => {
      cy.get('.tox-tinymce').should('be.visible');
      getIframeBody('iframe.tox-edit-area__iframe')
        .should('not.contain', templateText);

      cy.get('.tox-dialog__footer').contains('.tox-button', 'Insert').click();
      cy.get('.tox-collection__item').contains('Template').click();
      cy.wait('@getTemplates');

      cy.get('.tox-dialog').should('be.visible');
      cy.get('.tox-dialog').find('.tox-listbox').click();
      cy.get('.tox-collection__item').contains(templateTitle).click();

      getIframeBody('.tox-dialog iframe')
        .should('contain', templateText);

      cy.get('.tox-dialog').contains('button', 'Insert').click();
      cy.get('.tox-dialog').should('not.exist');

      getIframeBody('iframe.tox-edit-area__iframe')
        .should('contain', templateText);

      cy.get('button[title="More options"]').click()
        .get('button[data-action="destroy"]').click();
    });
  });
});
