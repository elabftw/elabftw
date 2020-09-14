/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import { relativeMoment, makeSortableGreatAgain } from './misc';
import i18next from 'i18next';

export default class Step extends Crud {
  type: string;

  constructor(type: string) {
    super('app/controllers/Ajax.php');
    this.type = type;
  }

  create(elem): void {
    const id = elem.data('id');
    // get body
    const body = elem.val();
    // fix for user pressing enter with no input
    if (body.length > 0) {
      this.send({
        action: 'create',
        what: 'step',
        type: this.type,
        params: {
          itemId: id,
          template: body,
        },
      }).then(() => {
        // reload the step list
        $('#steps_div_' + id).load(window.location.href + ' #steps_div_' + id, function() {
          relativeMoment();
          makeSortableGreatAgain();
        });
        // clear input field
        elem.val('');
      });
    } // end if input < 0
  }

  finish(elem): void {
    // the id of the exp/item/tpl
    const id = elem.data('id');
    const stepId = elem.data('stepid');
    // on the todolist we don't want to grab the type from the page
    // because it's only steps from experiments
    // so if the element has a data-type, take that instead
    let itemType = this.type;
    if (elem.data('type')) {
      itemType = elem.data('type');
    }

    this.send({
      action: 'finish',
      what: 'step',
      type: itemType,
      params: {
        itemId: id,
        id: stepId,
      },
    }).then(() => {
      const loadUrl = window.location.href + ' #steps_div_' + id;
      console.log(loadUrl);
      // reload the step list
      $('#steps_div_' + id).load(loadUrl, function() {
        relativeMoment();
        makeSortableGreatAgain();
      });
      $('#todo_step_' + stepId).prop('checked', true);
    });
  }

  destroy(elem): void {
    // the id of the exp/item/tpl
    const id = elem.data('id');
    const stepId = elem.data('stepid');
    if (confirm(i18next.t('step-delete-warning'))) {
      this.send({
        action: 'destroy',
        what: 'step',
        type: this.type,
        params: {
          itemId: id,
          id: stepId,
        },
      }).then(() => {
        const loadUrl = window.location + ' #steps_div_' + id;
        // reload the step list
        $('#steps_div_' + id).load(loadUrl, function() {
          relativeMoment();
          makeSortableGreatAgain();
        });
      });
    }
  }
}
