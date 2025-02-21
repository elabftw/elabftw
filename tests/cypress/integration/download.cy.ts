describe('download.php', () => {
  let csrf: string;
  beforeEach(() => {
    cy.login().then(value => csrf = value);
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('send empty file if no query is provided', () => {
    cy.request('/app/download.php').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/force-download');
      expect(resp.headers['content-disposition']).to.eq('attachment; filename=unnamed_file');
    });
  });

  it('send empty file if f parameter is invalid', () => {
    cy.request('/app/download.php?f=notvalid').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/force-download');
      expect(resp.headers['content-disposition']).to.eq('attachment; filename=unnamed_file');
    });
  });

  it('send requested file', () => {
    // add a file to an experiment via post request
    const filename = 'example.txt';
    cy.readFile(`tests/_data/${filename}`, 'binary').then(file => {
      const formData = new FormData();
      formData.append(
        'file',
        Cypress.Blob.binaryStringToBlob(file, 'text/plain'),
        filename,
      );
      cy.request({
        url: '/api/v2/experiments/2/uploads',
        method: 'POST',
        body: formData,
        headers: {
          'content-type': 'multipart/form-data',
          'X-CSRF-Token': csrf,
        },
      }).then(resp => {
        expect(resp.status).to.eq(201);
        expect(resp.headers.location).to.exist; // eslint-disable-line
        expect(resp.headers.location).to.be.a('string');
        //
        const url = new URL(resp.headers.location.toString());
        cy.request(url.pathname).then(resp1 => {
          expect(resp1.status).to.eq(200);
          expect(resp1.headers['content-type']).to.eq('application/json');
          expect(resp1.body).to.exist; // eslint-disable-line
          const url = `/app/download.php?f=${resp1.body.long_name}&name=${resp1.body.real_name}`;
          cy.request(url).then(resp => {
            expect(resp.status).to.eq(200);
            expect(resp.headers['content-type']).to.eq('text/plain; charset=UTF-8');
            expect(resp.headers['content-disposition']).to.eq(`inline; filename=${resp1.body.real_name}`);
          });
        });
      });
    });
  });
});
