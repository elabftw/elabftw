describe('Experiments page', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('is visible', () => {
    cy.visit('/experiments.php')
    cy.get('h1#pageTitle').should('have.text', 'Experiments');
  });

  it('provides import interface and can import eln files', () => {
    cy.visit('/experiments.php');
    cy.get('div.btn-group div.dropleft button.dropdown-toggle[data-toggle="dropdown"]').click()
    cy.get('[data-target="importModal"]').should('be.visible').should('contain', 'Import from file').click();
    cy.get('#importModalLabel').should('be.visible').should('have.text', 'Import from file');
    cy.get('#import_modal_target').should('exist').select('experiments:1');
    cy.get('#import_modal_canread').should('exist');
    cy.get('#import_modal_canwrite').should('exist');
    cy.get('#import_modal_file_input').should('exist').selectFile('tests/_data/multiple-experiments.eln');
    cy.intercept('app/controllers/ImportController.php', req => {
      req.on('response', resp => {
          expect(resp.statusCode).to.equal(302);
          expect(resp.headers.location).to.include('experiments.php?order=lastchange');
      });
    }).as('importController');
    cy.get('[data-action="check-max-size"]').should('exist').click();
    cy.wait('@importController');
    cy.get('div.alert.alert-success').should('contain', '2 items imported successfully.');
    cy.get('#itemList').should('contain', 'Synthesis of Aspirin');
  });

  function importWrapper(filename, target, canread, canwrite, mimeType, redirectUrl) {
    // get the csrf token
    cy.visit('/experiments.php');
    let csrf;
    cy.get('meta[name="csrf-token"]').should('exist').invoke('attr', 'content').then(content => {
      csrf = content;
    });

    cy.readFile(`tests/_data/${filename}`, 'binary').then(file => {
      const formData = new FormData();
      formData.append('csrf', csrf);
      formData.append('target', target);
      formData.append('canread', canread);
      formData.append('canwrite', canwrite);
      formData.append(
        'file',
        Cypress.Blob.binaryStringToBlob(file, mimeType),
        filename
      );

      cy.request({
        method: 'POST',
        url: 'app/controllers/ImportController.php',
        body: formData,
        headers: {
          'content-type': 'multipart/form-data'
        },
        followRedirect: false,
      }).then(resp => {
        expect(resp.status).to.eq(302);
        expect(resp.headers.location).to.include(redirectUrl);
      });
      cy.visit(redirectUrl);
    });
  }

  it('can import zip files', () => {
    importWrapper('importable-multi.zip', 'experiments:1', '30', '20', 'application/zip', 'experiments.php?order=lastchange');
    cy.get('div.alert.alert-success').should('contain', '2 items imported successfully.');
    cy.get('#itemList').should('contain', 'Zip for testing import');
  });

  it('can import csv files', () => {
    importWrapper('importable.csv', 'experiments:1', '30', '20', 'text/csv', 'experiments.php?order=lastchange');
    cy.get('div.alert.alert-success').should('contain', '3 items imported successfully.');
    cy.get('#itemList').should('contain', 'from csv row 3');
  });
});
