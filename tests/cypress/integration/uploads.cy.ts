interface SortableJquery {
  sortable(method: 'option', option: 'update'): unknown;
}

type AppJquery = (element: HTMLElement) => SortableJquery;
type SortableUpdate = (this: HTMLElement) => void;

describe('Upload groups', () => {
  beforeEach(() => {
    cy.login();
    // Keep this test deterministic: grouped grid is the starting layout.
    cy.request({
      method: 'PATCH',
      url: '/api/v2/users/me',
      body: {notifOnSaved: 0, uploads_layout: 1},
    });
  });

  const createExperiment = (): Cypress.Chainable<number> => {
    return cy.request({
      method: 'POST',
      url: '/api/v2/experiments',
      body: {title: `Cypress upload groups ${Date.now()}`},
    }).then(response => {
      expect(response.status).to.eq(201);
      return cy.extractIdFromLocation(response);
    });
  };

  const createUpload = (entityId: number, name: string, content: string): Cypress.Chainable<number> => {
    return cy.request({
      method: 'POST',
      url: `/api/v2/experiments/${entityId}/uploads`,
      body: {
        action: 'createfromstring',
        file_type: 'json',
        real_name: name,
        content,
      },
    }).then(response => {
      expect(response.status).to.eq(201);
      return cy.extractIdFromLocation(response);
    });
  };

  const createUploadGroup = (title: string): Cypress.Chainable<number> => {
    cy.get('[data-action="toggle-upload-group-form"]').click();
    cy.get('#addUploadGroupForm').should('be.visible').find('input[name="title"]').type(title);
    cy.get('#addUploadGroupForm [data-action="create-upload-group"]').click();

    return cy.contains('.upload-group-title', title)
      .should('be.visible')
      .closest('.upload-group')
      .invoke('attr', 'data-groupid')
      .then(groupId => {
        if (!groupId) {
          throw new Error(`Could not find upload group id for ${title}.`);
        }
        return Number(groupId);
      });
  };

  const triggerSortableUpdate = (container: HTMLElement): void => {
    const appWindow = container.ownerDocument.defaultView as Window & { $: AppJquery };
    const update = appWindow.$(container).sortable('option', 'update');
    if (typeof update !== 'function') {
      throw new Error('Upload sortable update callback is not initialized.');
    }
    (update as SortableUpdate).call(container);
  };

  const moveUploadToGroup = (uploadId: number, groupId: number): void => {
    cy.get(`#uploadDiv_${uploadId}`).then($upload => {
      cy.get(`.uploads-sortable[data-groupid="${groupId}"]`).then($target => {
        $target[0].append($upload[0]);
        triggerSortableUpdate($target[0]);
      });
    });
  };

  const moveGroupToTop = (groupId: number): void => {
    cy.get('.upload-groups-sortable').then($container => {
      const group = $container[0].querySelector(`#upload_group_${groupId}`);
      if (!group) {
        throw new Error(`Could not find upload group ${groupId}.`);
      }
      $container[0].prepend(group);
      triggerSortableUpdate($container[0]);
    });
  };

  it('organizes attachments in groups and keeps grouped behavior across layouts', () => {
    createExperiment().then(entityId => {
      createUpload(entityId, 'first.json', '{"upload":1}').then(firstUploadId => {
        createUpload(entityId, 'second.json', '{"upload":2}').then(secondUploadId => {
          cy.visit(`/experiments.php?mode=edit&id=${entityId}`);

          createUploadGroup('Raw data').then(rawGroupId => {
            // Creating the first named group reveals the ungrouped attachments as General.
            cy.get('#upload_group_body_default')
              .closest('.upload-group')
              .should('contain.text', 'General');

            createUploadGroup('Processed data').then(processedGroupId => {
              cy.intercept('PATCH', `**/api/v2/experiments/${entityId}/uploads`).as('updateUploadOrdering');
              cy.intercept('PATCH', `**/api/v2/experiments/${entityId}/upload_groups`).as('updateUploadGroupOrdering');

              moveUploadToGroup(firstUploadId, rawGroupId);
              cy.wait('@updateUploadOrdering').its('response.statusCode').should('eq', 200);
              cy.get(`#upload_group_${rawGroupId} #uploadDiv_${firstUploadId}`).should('exist');

              moveUploadToGroup(secondUploadId, processedGroupId);
              cy.wait('@updateUploadOrdering').its('response.statusCode').should('eq', 200);
              cy.get(`#upload_group_${processedGroupId} #uploadDiv_${secondUploadId}`).should('exist');

              // General disappears once no attachment is left ungrouped.
              cy.get('#upload_group_body_default').should('not.exist');

              // The action menu must escape the group card instead of being clipped by it.
              cy.get(`#upload_group_${rawGroupId}`)
                .should('have.css', 'overflow', 'visible');
              cy.get(`#upload_group_${rawGroupId} #uploadDiv_${firstUploadId} .dropdown > [data-toggle="dropdown"]`)
                .click();
              cy.get(`#upload_group_${rawGroupId} #uploadDiv_${firstUploadId} .dropdown-menu`)
                .should('be.visible')
                .contains('button', 'Delete')
                .should('be.visible');
              cy.get('body').click(0, 0);

              // Duplicating an attachment keeps the duplicate in the same group.
              cy.get(`#upload_group_${rawGroupId} #uploadDiv_${firstUploadId} .dropdown > [data-toggle="dropdown"]`)
                .click();
              cy.get(`#upload_group_${rawGroupId} #uploadDiv_${firstUploadId} [data-action="duplicate-upload"]`).click();
              cy.get(`#upload_group_${rawGroupId} .uploads-sortable > .countable`).should('have.length', 2);

              // Group titles remain editable.
              cy.get(`#upload_group_${rawGroupId} .upload-group-title`).click();
              cy.get(`#upload_group_${rawGroupId} input.form-control`).clear().type('Contracts');
              cy.get(`#upload_group_${rawGroupId}`).contains('button', 'Save').click();
              cy.get(`#upload_group_${rawGroupId} .upload-group-title`).should('have.text', 'Contracts');

              // Group reordering uses the same sortable callback as a real drag operation.
              moveGroupToTop(processedGroupId);
              cy.wait('@updateUploadGroupOrdering').its('response.statusCode').should('eq', 200);
              cy.get('.upload-groups-sortable > .upload-group[data-groupid]')
                .first()
                .should('have.attr', 'data-groupid', String(processedGroupId));

              // New attachments still land in General while named groups exist.
              createUpload(entityId, 'general.json', '{"upload":3}').then(generalUploadId => {
                cy.reload();
                cy.get('#upload_group_body_default')
                  .should('exist')
                  .find(`#uploadDiv_${generalUploadId}`)
                  .should('exist');
                cy.get('#upload_group_body_default')
                  .closest('.upload-group')
                  .should('contain.text', 'General');

                // Grouping survives switching to the table layout.
                cy.get('[data-action="toggle-uploads-layout"]').click();
                cy.get(`#upload_group_${processedGroupId} tbody.uploads-sortable #uploadDiv_${secondUploadId}`)
                  .should('exist');
                cy.get(`#upload_group_${rawGroupId} tbody.uploads-sortable #uploadDiv_${firstUploadId}`)
                  .should('exist');

                // Deleting a group returns its attachments to General.
                cy.on('window:confirm', () => true);
                cy.get(`#upload_group_${rawGroupId} [data-action="destroy-upload-group"]`).click();
                cy.get(`#upload_group_${rawGroupId}`).should('not.exist');
                cy.get('#upload_group_body_default')
                  .closest('.upload-group')
                  .should('contain.text', 'General');
                cy.get('#upload_group_body_default').find(`#uploadDiv_${firstUploadId}`).should('exist');

                // Removing the last named group falls back to the original flat uploads UI.
                cy.get(`#upload_group_${processedGroupId} [data-action="destroy-upload-group"]`).click();
                cy.get('.upload-group[data-groupid]').should('not.exist');
                cy.get('#uploadsTable').find(`#uploadDiv_${firstUploadId}`).should('exist');
                cy.get('#uploadsTable').find(`#uploadDiv_${secondUploadId}`).should('exist');
                cy.get('#uploadsTable').find(`#uploadDiv_${generalUploadId}`).should('exist');

                cy.request({method: 'DELETE', url: `/api/v2/experiments/${entityId}`});
              });
            });
          });
        });
      });
    });
  });
});
