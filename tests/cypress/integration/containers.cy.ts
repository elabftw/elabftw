describe('Containers', () => {
  beforeEach(() => {
    cy.login();
  });

  // create a storage location via the API and return its id (parsed from the Location header)
  const createStorageUnit = (name: string, capacity?: number): Cypress.Chainable<number> =>
    cy.request({ method: 'POST', url: '/api/v2/storage_units', body: { name, capacity } })
      .then(resp => {
        expect(resp.status).to.eq(201);
        return parseInt(resp.headers['location'].toString().split('/').pop(), 10);
      });

  const createItem = (): Cypress.Chainable<number> =>
    cy.request({ method: 'POST', url: '/api/v2/items', body: { title: `Cypress container item ${Date.now()}` } })
      .then(resp => {
        expect(resp.status).to.eq(201);
        return parseInt(resp.headers['location'].toString().split('/').pop(), 10);
      });

  // occupy a location, so that a ceiling computed from it proves occupancy was subtracted
  const createContainer = (itemId: number, storageId: number): Cypress.Chainable =>
    cy.request({ method: 'POST', url: `/api/v2/items/${itemId}/containers/${storageId}`, body: { qty_stored: 1 } })
      .then(resp => {
        expect(resp.status).to.eq(201);
      });

  const stepperInput = (storageId: number): string =>
    `[data-storage-id="${storageId}"] input[data-action="container-qty-input"]`;

  const fullNotice = (storageId: number): string =>
    `[data-batch-full-notice][data-storage-id="${storageId}"]`;

  // show() takes orderby, sort and limit_nb from the user record, so all three are pinned
  // here: the resources each test creates have to reach the first page for the checkbox
  // lookups below. skip_pinned drops the pinned-first ordering on top of that.
  // The per-row checkboxes also only exist in item mode; table mode has its own
  const visitShowPageInItemMode = (): void => {
    cy.request({
      method: 'PATCH',
      url: '/api/v2/users/me',
      body: { display_mode: 'it', orderby: 'lastchange', sort: 'desc', limit_nb: 15 },
    });
    cy.visit('/database.php?skip_pinned=1');
  };

  const selectEntities = (ids: number[]): void => {
    ids.forEach(id => {
      cy.get(`[data-action="checkbox-entity"][data-id="${id}"]`).check();
    });
    cy.get('#withSelected').should('be.visible');
  };

  const openBatchContainerModal = (): void => {
    cy.get('[data-action="toggle-modal"][data-target="storageModal"]').click();
    cy.get('#storageModal').should('be.visible');
  };

  const closeBatchContainerModal = (): void => {
    cy.get('#storageModal .modal-footer button[data-dismiss="modal"]').click();
    cy.get('#storageModal').should('not.be.visible');
  };

  it('distributes containers and deletes them with their entity', () => {
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
            cy.get(`${stepper(storageA)} input[data-action="container-qty-input"]`).should('have.value', '3');

            // clamp check: the sum cannot exceed the target of 5
            for (let i = 0; i < 5; i++) {
              cy.get(`${stepper(storageA)} [data-action="container-qty-plus"]`).click();
            }
            cy.get(`${stepper(storageA)} input[data-action="container-qty-input"]`).should('have.value', '5');

            // back down to 3 in Freezer A
            for (let i = 0; i < 2; i++) {
              cy.get(`${stepper(storageA)} [data-action="container-qty-minus"]`).click();
            }
            cy.get(`${stepper(storageA)} input[data-action="container-qty-input"]`).should('have.value', '3');

            // type 2 directly into Freezer B
            cy.get(`${stepper(storageB)} input[data-action="container-qty-input"]`).invoke('val', '2').trigger('input');

            // now fully distributed: counter reads 5 / 5 and submit is enabled
            cy.get('#containerAssignedCount').should('have.text', '5');
            cy.get('#storeContainersBtn').should('be.enabled');

            // re-clamp check: lowering the target re-distributes the steppers down to exactly the new target
            cy.get('#containerMultiplierInput').invoke('val', '4').trigger('input');
            cy.get('#containerAssignedCount').should('have.text', '4');
            cy.get('#storeContainersBtn').should('be.enabled');
            // total assigned must now be exactly 4
            cy.get('input[data-action="container-qty-input"]').then($inputs => {
              const total = Cypress.$.makeArray($inputs)
                .reduce((sum, el) => sum + (parseInt((el as HTMLInputElement).value, 10) || 0), 0);
              expect(total).to.eq(4);
            });

            // restore a valid 3 + 2 = 5 distribution and submit
            cy.get('#containerMultiplierInput').invoke('val', '5').trigger('input');
            cy.get(`${stepper(storageA)} input[data-action="container-qty-input"]`).invoke('val', '3').trigger('input');
            cy.get(`${stepper(storageB)} input[data-action="container-qty-input"]`).invoke('val', '2').trigger('input');
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

            // deleting the entity must also free its storage locations
            cy.intercept('GET', `/api/v2/items/${itemId}/containers?has_any=1`).as('getItemContainers');
            cy.intercept('DELETE', `/api/v2/items/${itemId}?delete_containers=1`).as('deleteItemWithContainers');
            cy.get('button[title="More options"]').click();
            cy.get('button[data-action="toggle-modal"][data-target="deleteSelectedEntitiesModal"]').click();
            cy.wait('@getItemContainers');
            cy.get('[data-output="delete-containers-count"]').should('have.text', '5');
            cy.get('#deleteSelectedEntitiesButton').should('be.enabled').click();
            cy.wait('@deleteItemWithContainers');

            cy.request({ method: 'GET', url: `/api/v2/storage_units/${storageA}` }).its('body.occupancy').should('eq', 0);
            cy.request({ method: 'GET', url: `/api/v2/storage_units/${storageB}` }).its('body.occupancy').should('eq', 0);
          });
      });
    });
  });

  it('applies the distribution to every entity selected on the show page', () => {
    // capacity 7 with 3 entries selected leaves room for 2 containers per entry, remainder unusable
    createStorageUnit(`Freezer C ${Date.now()}`, 7).then(storageId => {
      createItem().then(first => {
        createItem().then(second => {
          createItem().then(third => {
            const ids = [first, second, third];
            visitShowPageInItemMode();
            selectEntities(ids);
            openBatchContainerModal();

            // the ceiling is per entry: floor(7 / 3), not the 7 free slots the server rendered
            cy.get(stepperInput(storageId))
              .should('have.attr', 'max', '2')
              .and('have.attr', 'data-slots-left', '7');

            // 2 containers per entry across 3 entries
            cy.get('#containerMultiplierInput').invoke('val', '2').trigger('input');
            // asserted as non-empty rather than by wording, which is translated
            cy.get('#containerBatchSummary').should('not.have.text', '');
            cy.get('#storeContainersBtn').should('be.disabled');

            cy.get(stepperInput(storageId)).invoke('val', '2').trigger('input');
            cy.get('#containerAssignedCount').should('have.text', '2');
            cy.get('#storeContainersBtn').should('be.enabled').click();

            cy.get('#storageModal').should('not.be.visible');

            // every selected entity got the same distribution
            ids.forEach(id => {
              cy.request({ method: 'GET', url: `/api/v2/items/${id}/containers` }).then(resp => {
                expect(resp.status).to.eq(200);
                const containers = resp.body as Array<{ storage_id: number }>;
                expect(containers).to.have.length(2);
                expect(containers.every(c => c.storage_id === storageId)).to.eq(true);
              });
            });

            // reopening recomputes from fresh occupancy: 7 - 6 = 1 free slot, so no entry
            // can be given a container any more and the location is refused
            openBatchContainerModal();
            cy.get(stepperInput(storageId))
              .should('have.attr', 'data-slots-left', '1')
              .and('have.attr', 'max', '0')
              .and('be.disabled');
            // reached through occupancy rather than a small capacity, but refused all the same
            cy.get(fullNotice(storageId)).should('be.visible');
          });
        });
      });
    });
  });

  it('caps each location by what one selected entry may claim', () => {
    const stamp = Date.now();
    // unlimited, too small for the selection, exactly the selection, and partly occupied
    createStorageUnit(`Unlimited ${stamp}`).then(unlimited => {
      createStorageUnit(`Too small ${stamp}`, 2).then(tooSmall => {
        createStorageUnit(`Exactly three ${stamp}`, 3).then(exactly => {
          createStorageUnit(`Partly occupied ${stamp}`, 9).then(occupied => {
            createItem().then(holder => {
              // 3 of the 9 slots taken, so 6 remain and the ceiling must be floor(6 / 3)
              createContainer(holder, occupied);
              createContainer(holder, occupied);
              createContainer(holder, occupied);
              createItem().then(first => {
                createItem().then(second => {
                  createItem().then(third => {
                    visitShowPageInItemMode();
                    selectEntities([first, second, third]);
                    openBatchContainerModal();

                    // no capacity declared, so no ceiling to narrow and no slot count to read
                    cy.get(stepperInput(unlimited))
                      .should('not.have.attr', 'max')
                      .and('not.have.attr', 'data-slots-left')
                      .and('be.enabled');
                    cy.get(fullNotice(unlimited)).should('not.be.visible');

                    // floor(2 / 3) is 0: room for some entries is room for none of them
                    cy.get(stepperInput(tooSmall))
                      .should('have.attr', 'max', '0')
                      .and('be.disabled');
                    cy.get(`[data-storage-id="${tooSmall}"] [data-action="container-qty-plus"]`)
                      .should('be.disabled');
                    // the badge still shows 0 / 2, so the reason has to be spelled out
                    cy.get(fullNotice(tooSmall)).should('be.visible');

                    // floor(3 / 3) is exactly 1
                    cy.get(stepperInput(exactly)).should('have.attr', 'max', '1').and('be.enabled');
                    cy.get(fullNotice(exactly)).should('not.be.visible');

                    // occupancy is subtracted before the division: floor((9 - 3) / 3)
                    cy.get(stepperInput(occupied))
                      .should('have.attr', 'data-slots-left', '6')
                      .and('have.attr', 'max', '2');

                    // the plus button clamps to the per-entry ceiling, not to the free slots
                    cy.get('#containerMultiplierInput').invoke('val', '3').trigger('input');
                    cy.get(`[data-storage-id="${exactly}"] [data-action="container-qty-plus"]`).click();
                    cy.get(`[data-storage-id="${exactly}"] [data-action="container-qty-plus"]`).click();
                    cy.get(stepperInput(exactly)).should('have.value', '1');
                  });
                });
              });
            });
          });
        });
      });
    });
  });

  it('widens the ceilings again when the selection shrinks', () => {
    const stamp = Date.now();
    // 4 free slots gives 1 per entry for 3 entries, but 2 per entry once one is unselected
    createStorageUnit(`Freezer D ${stamp}`, 4).then(storageId => {
      // 2 free slots is nothing per entry for 3 entries, but 1 each for 2 entries
      createStorageUnit(`Freezer D refused ${stamp}`, 2).then(refusedId => {
        createItem().then(first => {
          createItem().then(second => {
            createItem().then(third => {
              visitShowPageInItemMode();
              selectEntities([first, second, third]);

              openBatchContainerModal();
              cy.get(stepperInput(storageId)).should('have.attr', 'max', '1');
              cy.get(stepperInput(refusedId)).should('have.attr', 'max', '0').and('be.disabled');
              cy.get(fullNotice(refusedId)).should('be.visible');
              closeBatchContainerModal();

              cy.get(`[data-action="checkbox-entity"][data-id="${third}"]`).uncheck();
              openBatchContainerModal();
              cy.get(stepperInput(storageId)).should('have.attr', 'max', '2');
              // the location is usable again, so its reason has to be taken back down
              cy.get(stepperInput(refusedId)).should('have.attr', 'max', '1').and('be.enabled');
              cy.get(fullNotice(refusedId)).should('not.be.visible');
            });
          });
        });
      });
    });
  });

  it('refuses a target that no location has room for across the selection', () => {
    // 3 free slots is 1 per entry, so a target of 2 per entry cannot be distributed at all
    createStorageUnit(`Freezer E ${Date.now()}`, 3).then(storageId => {
      createItem().then(first => {
        createItem().then(second => {
          createItem().then(third => {
            visitShowPageInItemMode();
            selectEntities([first, second, third]);
            openBatchContainerModal();

            cy.get(stepperInput(storageId)).should('have.attr', 'max', '1');
            // submit stays out of reach: the steppers cannot sum to the target.
            // #containerCapacityNotice is deliberately not asserted here, as totalSlotsLeft()
            // is Infinity as soon as the team owns one location with no declared capacity
            cy.get('#containerMultiplierInput').invoke('val', '2').trigger('input');
            cy.get('#containerAssignedCount').should('have.text', '0');
            cy.get('#storeContainersBtn').should('be.disabled');

            cy.get(`[data-storage-id="${storageId}"] [data-action="container-qty-plus"]`).click();
            cy.get(`[data-storage-id="${storageId}"] [data-action="container-qty-plus"]`).click();
            cy.get('#containerAssignedCount').should('have.text', '1');
            cy.get('#storeContainersBtn').should('be.disabled');
          });
        });
      });
    });
  });
});
