describe('Todolist', () => {
  beforeEach(() => {
    cy.login();
  });

  // open the dashboard with the todolist side panel already opened
  const visitDashboardWithTodolist = () => {
    cy.visit('/dashboard.php', {
      onBeforeLoad(win) {
        win.localStorage.setItem('opened-sidepanel', 'todolist');
      },
    });
    cy.get('#todolistPanel').should('be.visible');
  };

  // regression test for https://github.com/elabftw/elabftw/issues/7814
  it('finishes a step from the dashboard todolist', () => {
    cy.request('POST', '/api/v2/experiments', { title: `Cypress todolist step ${Date.now()}` }).then(resp => {
      cy.extractIdFromLocation(resp).then(id => {
        cy.request('POST', `/api/v2/experiments/${id}/steps`, { body: 'step to finish' }).then(stepResp => {
          cy.extractIdFromLocation(stepResp).then(stepId => {
            // a second step that stays unfinished, so we know when the todolist is rendered
            cy.request('POST', `/api/v2/experiments/${id}/steps`, { body: 'step left unfinished' }).then(otherStepResp => {
              cy.extractIdFromLocation(otherStepResp).then(otherStepId => {
                cy.intercept('PATCH', `/api/v2/experiments/${id}/steps/${stepId}`).as('finishStep');

                // check the step from the todolist
                visitDashboardWithTodolist();
                cy.get(`#todo_step_${stepId}`).should('not.be.checked').check();
                cy.wait('@finishStep').its('response.statusCode').should('eq', 200);

                // reopen the experiment: the step is finished
                cy.visit(`/experiments.php?mode=view&id=${id}`);
                cy.get(`#stepCheckbox_${stepId}`).should('be.checked');
                cy.get(`#stepCheckbox_${otherStepId}`).should('not.be.checked');

                // reopen the todolist: the step is not listed in the unfinished steps anymore
                visitDashboardWithTodolist();
                cy.get(`#todo_step_${otherStepId}`).should('exist');
                cy.get(`#todo_step_${stepId}`).should('not.exist');

                cy.request('DELETE', `/api/v2/experiments/${id}`);
              });
            });
          });
        });
      });
    });
  });
});
