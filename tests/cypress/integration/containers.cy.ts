describe('Containers', () => {
  beforeEach(() => {
    cy.login();
  });

  // create a storage location via the API and return its id (parsed from the Location header)
  const createStorageUnit = (name: string): Cypress.Chainable<number> =>
    cy.request({ method: 'POST', url: '/api/v2/storage_units', body: { name } })
      .then(resp => {
        expect(resp.status).to.eq(201);
        return parseInt(resp.headers['location'].toString().split('/').pop(), 10);
      });

  it('distributes containers across multiple storage locations', () => {
    // set up two storage locations and a resource to store them in
    createStorageUnit(`Freezer A ${Date.now()}`).then(storageA => {
      createStorageUnit(`Freezer B ${Date.now()}`).then(storageB => {
        cy.request({ method: 'POST', url: '/api/v2/items', body: { title: `Cypress container item ${Date.now()}` } })
          .then(resp => {
            expect(resp.status).to.eq(201);
            const itemId = parseInt(resp.headers['location'].toString().split('/').pop(), 10);

            cy.visit(`/database.php?mode=edit&id=${itemId}`);

            // open the "Add container" modal
            cy.get('[data-action="toggle-modal"][data-target="storageModal"]').click();
            cy.get('#storageModal').should('be.visible');

            // set per-container capacity/unit and the target total
            cy.get('#containerQtyStoredInput').invoke('val', '10').trigger('input');
            cy.get('#containerQtyUnitSelect').select('mL');
            cy.get('#containerMultiplierInput').invoke('val', '5').trigger('input');

            // initial state: nothing assigned, submit disabled
            cy.get('#containerAssignedCount').should('have.text', '0');
            cy.get('#containerTargetCount').should('have.text', '5');
            cy.get('#storeContainersBtn').should('be.disabled');

            const stepper = (storageId: number) =>
              `[data-storage-id="${storageId}"]`;

            // put 3 in Freezer A via the + button
            for (let i = 0; i < 3; i++) {
              cy.get(`${stepper(storageA)} [data-action="container-qty-plus"]`).click();
            }
            cy.get(`input[data-action="container-qty-input"]${stepper(storageA)}`).should('have.value', '3');

            // clamp check: the sum cannot exceed the target of 5
            for (let i = 0; i < 5; i++) {
              cy.get(`${stepper(storageA)} [data-action="container-qty-plus"]`).click();
            }
            cy.get(`input[data-action="container-qty-input"]${stepper(storageA)}`).should('have.value', '5');

            // back down to 3 in Freezer A
            for (let i = 0; i < 2; i++) {
              cy.get(`${stepper(storageA)} [data-action="container-qty-minus"]`).click();
            }
            cy.get(`input[data-action="container-qty-input"]${stepper(storageA)}`).should('have.value', '3');

            // type 2 directly into Freezer B
            cy.get(`input[data-action="container-qty-input"]${stepper(storageB)}`).invoke('val', '2').trigger('input');

            // now fully distributed: counter reads 5 / 5 and submit is enabled
            cy.get('#containerAssignedCount').should('have.text', '5');
            cy.get('#storeContainersBtn').should('be.enabled');

            // re-clamp check: lowering the target below the assigned sum clamps the steppers and disables submit
            cy.get('#containerMultiplierInput').invoke('val', '4').trigger('input');
            cy.get('#storeContainersBtn').should('be.disabled');
            // total assigned must now be <= 4
            cy.get('input[data-action="container-qty-input"]').then($inputs => {
              const total = Cypress.$.makeArray($inputs)
                .reduce((sum, el) => sum + (parseInt((el as HTMLInputElement).value, 10) || 0), 0);
              expect(total).to.be.at.most(4);
            });

            // restore a valid 3 + 2 = 5 distribution and submit
            cy.get('#containerMultiplierInput').invoke('val', '5').trigger('input');
            cy.get(`input[data-action="container-qty-input"]${stepper(storageA)}`).invoke('val', '3').trigger('input');
            cy.get(`input[data-action="container-qty-input"]${stepper(storageB)}`).invoke('val', '2').trigger('input');
            cy.get('#storeContainersBtn').should('be.enabled').click();

            // modal closes after submit
            cy.get('#storageModal').should('not.be.visible');

            // verify persistence: 5 containers, split 3 in Freezer A and 2 in Freezer B
            cy.request({ method: 'GET', url: `/api/v2/items/${itemId}/containers` }).then(containersResp => {
              expect(containersResp.status).to.eq(200);
              const containers = containersResp.body as Array<{ storage_id: number }>;
              expect(containers).to.have.length(5);
              const inA = containers.filter(c => c.storage_id === storageA).length;
              const inB = containers.filter(c => c.storage_id === storageB).length;
              expect(inA).to.eq(3);
              expect(inB).to.eq(2);
            });
          });
      });
    });
  });
});
