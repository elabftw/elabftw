/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import { notif } from './misc';
import i18next from 'i18next';

export default class Status extends Crud {
  what: string;

  constructor() {
    super('app/controllers/Ajax.php');
    this.what = 'status';
  }

  create(): void {
    const name = $('#statusName').val();
    if (name === '') {
      notif({'res': false, 'msg': 'Name cannot be empty'});
      $('#statusName').css('border-color', 'red');
      return;
    }
    const color = $('#statusColor').val();
    const isTimestampable = +$('#statusTimestamp').is(':checked');

    this.send({
      action: 'create',
      what: this.what,
      params: {
        name: name,
        color: color,
        isTimestampable: isTimestampable,
      },
    }).then((response) => {
      if (response.res) {
        window.location.replace('admin.php?tab=4');
      }
    });
  }

  update(id): void {
    const name = $('#statusName_' + id).val();
    const color = $('#statusColor_' + id).val();
    const isTimestampable = +$('#statusTimestamp_'+ id).is(':checked');
    const isDefault = $('#statusDefault_' + id).is(':checked');

    this.send({
      action: 'update',
      what: this.what,
      params: {
        id: id,
        name: name,
        color: color,
        isTimestampable: isTimestampable,
        isDefault: isDefault ? 1 : 0,
      },
    });
  }

  destroy(id): void {
    if (confirm(i18next.t('generic-delete-warning'))) {
      this.send({
        action: 'destroy',
        what: this.what,
        params: {
          id: id,
        },
      }).then((response) => {
        if (response.res) {
          $('#status_' + id).remove();
        }
      });
    }
  }
}
