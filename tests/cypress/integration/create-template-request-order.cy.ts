describe('Create dialog template request ordering', () => {
  const template = (id: number, title: string) => ({ id, title, fullname: 'Synthetic Owner' });

  beforeEach(() => cy.login());

  function openCreateDialog() {
    cy.visit('/experiments.php');
    cy.window().its('jQuery').then(($: JQueryStatic) => {
      $('#createModal').one('shown.bs.modal', cy.stub().as('createShown'));
    });
    cy.get('[data-action="toggle-create-modal"]').last().click();
    cy.get('@createShown').should('have.been.calledOnce');
  }

  function allowRendering() {
    cy.window().then(win => new Cypress.Promise(resolve => {
      win.requestAnimationFrame(() => win.requestAnimationFrame(resolve));
    }));
  }

  it('does not label an old experiment template as a resource template', () => {
    cy.intercept({ method: 'GET', pathname: '/api/v2/experiments_templates/' }, {
      delay: 1000, body: [template(90101, 'Synthetic experiment template')],
    }).as('experimentTemplates');
    cy.intercept({ method: 'GET', pathname: '/api/v2/items_types/' }, {
      body: [template(90102, 'Synthetic resource template')],
    }).as('resourceTemplates');
    openCreateDialog();
    cy.get('#createNewTypeItems').check();
    cy.wait('@resourceTemplates');
    cy.wait('@experimentTemplates');
    allowRendering();
    cy.get('#tplCreateNewTable').should('contain.text', 'Synthetic resource template')
      .and('not.contain.text', 'Synthetic experiment template');
    cy.get('#tplCreateNewTable [data-action="create-entity"]')
      .should('have.attr', 'data-type', 'items').and('have.attr', 'data-tplid', '90102');
    cy.get('#createNewCategoryList_items').should('not.have.attr', 'hidden');
    cy.get('#createNewCategoryList_experiments').should('have.attr', 'hidden');
  });

  it('removes previously loaded template actions while a new type is loading', () => {
    cy.intercept({ method: 'GET', pathname: '/api/v2/experiments_templates/' }, {
      body: [template(90101, 'Synthetic experiment template')],
    }).as('experimentTemplates');
    cy.intercept({ method: 'GET', pathname: '/api/v2/items_types/' }, {
      delay: 1000, body: [template(90102, 'Synthetic resource template')],
    }).as('resourceTemplates');
    openCreateDialog();
    cy.wait('@experimentTemplates');
    cy.get('#tplCreateNewTable [data-action="create-entity"]').should('exist');
    cy.get('#createNewTypeItems').check();
    cy.get('#tplCreateNewTable [data-action="create-entity"]').should('not.exist');
    cy.wait('@resourceTemplates');
    cy.get('#tplCreateNewTable [data-action="create-entity"]').should('have.attr', 'data-tplid', '90102');
  });

  it('invalidates a pending search when switching to creating a template', () => {
    cy.intercept({ method: 'GET', pathname: '/api/v2/experiments_templates/' }, {
      delay: 1000, body: [template(90101, 'Synthetic experiment template')],
    }).as('experimentTemplates');
    openCreateDialog();
    cy.get('#createNewTypeExperimentsTemplates').check();
    cy.wait('@experimentTemplates');
    allowRendering();
    cy.get('#tplCreateNewTable').should('be.empty');
    cy.get('#createNewTemplatesDiv').should('have.attr', 'hidden');
    cy.get('#createNoTplBtn').should('have.attr', 'data-type', 'experiments_templates');
    // No synthetic template is ever submitted to the server.
  });
});
