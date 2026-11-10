describe('Attachment preview request ordering', () => {
  beforeEach(() => {
    cy.login();
    cy.createEntity();
  });

  function addPreviewButton(win: Window, name: string, ext: string) {
    const button = win.document.createElement('button');
    button.id = `preview-${name}`;
    button.type = 'button';
    Object.assign(button.dataset, {
      action: 'toggle-modal', target: 'plainTextModal', name, ext,
      storage: '1', path: `synthetic-${name}`,
    });
    button.textContent = name;
    win.document.body.appendChild(button);
  }

  function openPreview(name: string) {
    cy.window().its('jQuery').then(($: JQueryStatic) => {
      $('#plainTextModal').one('shown.bs.modal', cy.stub().as('previewShown'));
    });
    cy.get(`#preview-${name}`).click({ force: true });
    cy.get('@previewShown').should('have.been.calledOnce');
  }

  function closePreview() {
    cy.get('#plainTextModal .modal-footer [data-dismiss="modal"]').click();
    cy.get('#plainTextModal').should('not.be.visible');
  }

  for (const ext of ['txt', 'md', 'json', 'table']) {
    it(`keeps the latest ${ext} preview when an old response arrives last`, () => {
      let finishOld: () => void;
      let oldSignal: AbortSignal;
      cy.window().then(win => {
        addPreviewButton(win, 'old', ext);
        addPreviewButton(win, 'new', ext);
        const originalFetch = win.fetch.bind(win);
        const content = (name: string) => ext === 'json' ? JSON.stringify({ value: `${name} content` }) : `${name} content`;
        cy.stub(win, 'fetch').callsFake((input: RequestInfo | URL, options?: RequestInit) => {
          const path = String(input);
          if (path.includes('f=synthetic-old')) {
            oldSignal = options?.signal;
            return new Promise<Response>(resolve => {
              finishOld = () => resolve(new win.Response(content('old'), { headers: { 'Content-Type': 'text/csv' } }));
            });
          }
          if (path.includes('f=synthetic-new')) {
            return Promise.resolve(new win.Response(content('new'), { headers: { 'Content-Type': 'text/csv' } }));
          }
          return originalFetch(input, options);
        });
      });
      openPreview('old');
      closePreview();
      openPreview('new');
      cy.get('#plainTextContentDiv').should('contain.text', 'new content');
      cy.then(() => {
        expect(oldSignal.aborted).to.eq(true);
        finishOld();
      });
      cy.window().then(win => new Cypress.Promise(resolve => {
        win.requestAnimationFrame(() => win.requestAnimationFrame(resolve));
      }));
      cy.get('#plainTextModalLabel').should('have.text', 'new');
      cy.get('#plainTextContentDiv').should('contain.text', 'new content').and('not.contain.text', 'old content');
    });
  }

  it('clears the old preview while loading and when the next download fails', () => {
    let failDownload: () => void;
    cy.window().then(win => {
      addPreviewButton(win, 'first', 'txt');
      addPreviewButton(win, 'failed', 'txt');
      const originalFetch = win.fetch.bind(win);
      cy.stub(win, 'fetch').callsFake((input: RequestInfo | URL, options?: RequestInit) => {
        const path = String(input);
        if (path.includes('f=synthetic-first')) return Promise.resolve(new win.Response('previous content'));
        if (path.includes('f=synthetic-failed')) {
          return new Promise<Response>(resolve => {
            failDownload = () => resolve(new win.Response('error response must not be previewed', { status: 403 }));
          });
        }
        return originalFetch(input, options);
      });
    });
    openPreview('first');
    cy.get('#plainTextContentDiv').should('contain.text', 'previous content');
    closePreview();
    openPreview('failed');
    cy.get('#plainTextContentDiv').should('be.empty');
    cy.then(() => failDownload());
    cy.get('.overlay').should('contain.text', '403');
    cy.get('#plainTextContentDiv').should('be.empty');
  });
});
