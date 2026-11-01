import type { Ketcher } from 'ketcher-core';

describe('Chemical editor attachments', () => {
  let entityId: number;

  const setDrawing = (smiles: string) => cy.window().its('ketcher')
    .then((ketcher: Ketcher) => ketcher.setMolecule(smiles));

  const expectDrawing = (smiles: string) => {
    cy.get('[data-cy="chem-load-attachment"]').should('be.enabled');
    return cy.window().its('ketcher')
      .then((ketcher: Ketcher) => ketcher.getSmiles()).should('eq', smiles);
  };

  const openAttachmentModal = (action: 'save' | 'load') => {
    const selector = action === 'save' ? '#chemSaveAttachmentModal' : '#chemLoadAttachmentModal';
    cy.window().its('jQuery').then(($: JQueryStatic) => {
      $(selector).one('shown.bs.modal', cy.stub().as('attachmentModalShown'));
    });
    cy.get(`[data-cy="chem-${action}-attachment"]`).click();
    // Bootstrap ignores dismissals while the opening fade is still running.
    cy.get('@attachmentModalShown').should('have.been.calledOnce');
  };

  const attach = (name: string, content: string) => cy.request({
    method: 'POST',
    url: `/api/v2/experiments/${entityId}/uploads`,
    body: { action: 'createfromstring', file_type: 'json', real_name: name, content },
  });

  beforeEach(() => {
    cy.login();
    cy.request('PATCH', '/api/v2/users/me', { uploads_layout: 1 });
    cy.request('POST', '/api/v2/experiments', { title: 'Chemical attachment test' })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => {
        entityId = id;
        cy.visit(`/experiments.php?mode=edit&id=${id}`);
      });
    cy.get('[data-cy="chem-save-attachment"]', { timeout: 60000 }).should('be.enabled');
    cy.get('#chemEditorDiv').then($editor => {
      if ($editor[0].hidden) cy.get('#chem-editor [data-action="toggle-next"]').click();
    });
  });

  it('saves an editable KET attachment with the chosen filename', () => {
    setDrawing('CCO');
    cy.intercept('POST', `**/api/v2/experiments/${entityId}/uploads`).as('saveDrawing');
    openAttachmentModal('save');
    cy.get('[data-cy="chem-attachment-format"]').should('have.value', 'ket');
    cy.get('[data-cy="chem-attachment-filename"]').clear().type('ethanol.ket');
    cy.get('[data-cy="chem-save-attachment-submit"]').click();
    cy.wait('@saveDrawing').its('response.statusCode').should('eq', 201);
    cy.get('#chemSaveAttachmentModal').should('not.be.visible');
    cy.get('#uploadsDiv').should('contain.text', 'ethanol.ket');
    cy.request(`/api/v2/experiments/${entityId}/uploads`).then(response => {
      const upload = response.body.find((upload: { real_name: string }) => upload.real_name === 'ethanol.ket');
      expect(upload).not.to.eq(undefined);
      cy.request(`/api/v2/experiments/${entityId}/uploads/${upload.id}?format=binary`).then(file => {
        const ket = typeof file.body === 'string' ? JSON.parse(file.body) : file.body;
        expect(ket.root.nodes).to.have.length.greaterThan(0);
      });
    });
    setDrawing('CC');
    cy.window().then(win => cy.stub(win, 'confirm').returns(true));
    openAttachmentModal('load');
    cy.get('[data-cy="chem-attachment-select"]').should('contain.text', 'ethanol.ket');
    cy.get('[data-cy="chem-load-attachment-submit"]').should('be.enabled').click();
    cy.get('#chemLoadAttachmentModal').should('not.be.visible');
    expectDrawing('CCO');
  });

  it('refreshes compatible attachments and allows cancelling canvas replacement', () => {
    openAttachmentModal('load');
    cy.get('#chemAttachmentsEmpty').should('be.visible');
    cy.get('[data-cy="chem-load-attachment-submit"]').should('be.disabled');
    cy.get('#chemLoadAttachmentModal [data-dismiss="modal"]').last().click();
    cy.get('#chemLoadAttachmentModal').should('not.be.visible');
    attach('notes.txt', 'Notes');
    attach('metadata.json', '{"metadata":true}');
    attach('picture.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
    attach('ethane.SMI', 'CC');
    setDrawing('CCO');
    cy.window().then(win => cy.stub(win, 'confirm').returns(false).as('replaceDrawing'));
    openAttachmentModal('load');
    cy.get('[data-cy="chem-attachment-select"] option').should('have.length', 1).and('have.text', 'ethane.SMI');
    cy.get('[data-cy="chem-load-attachment-submit"]').should('be.enabled').click();
    cy.get('@replaceDrawing').should('have.been.calledOnce');
    expectDrawing('CCO');
    cy.get('#chemLoadAttachmentModal').should('be.visible');
    cy.get('@replaceDrawing').invoke('returns', true);
    cy.get('[data-cy="chem-load-attachment-submit"]').should('be.enabled').click();
    cy.get('#chemLoadAttachmentModal').should('not.be.visible');
    expectDrawing('CC');
  });

  it('keeps the drawing when a compatible filename contains invalid structure data', () => {
    attach('broken.ket', 'This is not a chemical structure.\nInvalid content.');
    setDrawing('CCO');
    cy.window().then(win => cy.stub(win, 'confirm').returns(true));
    openAttachmentModal('load');
    cy.get('[data-cy="chem-load-attachment-submit"]').should('be.enabled').click();
    cy.get('#chemLoadAttachmentError').should('be.visible').and('not.be.empty');
    cy.get('[data-cy="chem-load-attachment-submit"]').should('be.enabled');
    expectDrawing('CCO');
  });

  it('keeps the save modal usable after an upload fails', () => {
    setDrawing('CCO');
    cy.intercept({ method: 'POST', url: `**/api/v2/experiments/${entityId}/uploads`, times: 1 }, {
      statusCode: 503,
      body: { description: 'Upload temporarily unavailable' },
    });
    openAttachmentModal('save');
    cy.get('[data-cy="chem-save-attachment-submit"]').click();
    cy.get('#chemSaveAttachmentError').should('be.visible').and('contain.text', 'Upload temporarily unavailable');
    cy.get('[data-cy="chem-save-attachment-submit"]').should('be.enabled').click();
    cy.get('#chemSaveAttachmentModal').should('not.be.visible');
    cy.get('#uploadsDiv').should('contain.text', 'structure.ket');
    expectDrawing('CCO');
  });

  it('loads from the attachment menu and opens the collapsed editor', () => {
    attach('ethane.SMI', 'CC').then(response => cy.extractIdFromLocation(response)).then(uploadId => {
      cy.reload();
      cy.get('[data-cy="chem-save-attachment"]', { timeout: 60000 }).should('be.enabled');
      setDrawing('CCO');
      cy.get('#chemEditorDiv').then($editor => {
        if (!$editor[0].hidden) cy.get('#chem-editor [data-action="toggle-next"]').click();
      });
      cy.window().then(win => cy.stub(win, 'confirm').returns(false).as('replaceDrawing'));
      const openMenu = () => cy.get(`#uploadDiv_${uploadId} .dropdown > [data-toggle="dropdown"]`).click();
      const loadAction = `#uploadDiv_${uploadId} .dropdown-menu [data-action="chem-load-file"]`;
      openMenu();
      cy.get(loadAction).should('be.enabled').click();
      cy.get('@replaceDrawing').should('have.been.calledOnce');
      expectDrawing('CCO');
      cy.get('#chemEditorDiv').should('not.be.visible');

      cy.get('@replaceDrawing').invoke('returns', true);
      openMenu();
      cy.get(loadAction).click();
      cy.get('#chemEditorDiv').should('be.visible');
      cy.get('#chem-editor [aria-controls="chemEditorDiv"]').should('have.attr', 'aria-expanded', 'true');
      cy.get('#chemLoadAttachmentModal').should('not.be.visible');
      expectDrawing('CC');
    });
  });

  it('loads MOL thumbnails with the edit overlay and also supports the table menu', () => {
    setDrawing('CO');
    cy.window().its('ketcher').then((ketcher: Ketcher) => ketcher.getMolfile())
      .then(mol => attach('methanol.mol', mol))
      .then(response => cy.extractIdFromLocation(response)).then(uploadId => {
        attach('notes.txt', 'Notes');
        cy.reload();
        cy.get('[data-cy="chem-save-attachment"]', { timeout: 60000 }).should('be.enabled');
        cy.get('#chemEditorDiv').then($editor => {
          if (!$editor[0].hidden) cy.get('#chem-editor [data-action="toggle-next"]').click();
        });
        cy.get(`#uploadDiv_${uploadId} .viewer_3Dmoljs`).should('not.exist');
        cy.get(`#uploadDiv_${uploadId} .icon-overlay[data-action="chem-load-file"]`)
          .should('have.class', 'edit-overlay').click();
        cy.get('#chemEditorDiv').should('be.visible');
        expectDrawing('CO');

        cy.get('[data-action="toggle-uploads-layout"]').click();
        setDrawing('CCO');
        cy.window().then(win => cy.stub(win, 'confirm').returns(true));
        cy.get(`tr#uploadDiv_${uploadId} .dropdown > [data-toggle="dropdown"]`).click();
        cy.get(`tr#uploadDiv_${uploadId} .dropdown-menu [data-action="chem-load-file"]`).should('be.enabled').click();
        expectDrawing('CO');
        cy.contains('#uploadsDiv tr', 'notes.txt').find('[data-action="chem-load-file"]').should('be.disabled');
      });
  });
});
