describe('Make', () => {
  beforeEach(() => {
    cy.login();
  });

  it('csv', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=csv&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('text/csv; charset=UTF-8');
      });
    });
  });

  it('eln', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=eln&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('application/zip');
      });
    });
  });


  it('single entry PDF', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=pdf&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('application/pdf');
      });
    });
  });

  it('multientry PDF', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=pdf&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('application/pdf');
      });
    });
  });

  it('qrPDF', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=qrpdf&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('application/pdf');
      });
    });
  });

  it('JSON', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=json&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('application/json');
      });
    });
  });

  it('qrpng', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=qrpng&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('image/png');
      });
      // will be status code 400
      cy.visit(`/make.php?format=qrpng&type=experiments&id=${expid}+${expid}`, { failOnStatusCode: false });
      cy.get('div.alert.alert-danger').should('contain', 'QR PNG format is only suitable for one ID.');
    });
  });

  it('instance level report', () => {
    cy.request('/make.php?format=instance').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('zip with PDFA', () => {
    cy.getExperimentId().then(expid => {
      cy.request(`/make.php?format=zipa&type=experiments&id=${expid}`).then(resp => {
        expect(resp.status).to.eq(200);
        expect(resp.headers['content-type']).to.eq('application/zip');
      });
    });
  });

  it('scheduler report', () => {
    // will be status code 400
    cy.visit('/make.php?format=schedulerReport', { failOnStatusCode: false });
    cy.get('div.alert.alert-danger').should('contain', 'There are no events to report');
  });
});
