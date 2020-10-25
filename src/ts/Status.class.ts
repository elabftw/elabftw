/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';

export default class Status {
  controller: string;

  constructor() {
    this.controller = 'app/controllers/StatusController.php';
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

    $.post(this.controller, {
      statusCreate: true,
      name: name,
      color: color,
      isTimestampable: isTimestampable
    }).done(function(json) {
      notif(json);
      if (json.res) {
        window.location.replace('admin.php?tab=4');
      }
    });
  }

  update(id): void {
    const name = $('#statusName_' + id).val();
    const color = $('#statusColor_' + id).val();
    const isTimestampable = +$('#statusTimestamp_'+ id).is(':checked');
    const isDefault = $('#statusDefault_' + id).is(':checked');

    $.post(this.controller, {
      statusUpdate: true,
      id: id,
      name: name,
      color: color,
      isTimestampable: isTimestampable,
      isDefault: isDefault ? 1 : 0,
    }).done(function(json) {
      notif(json);
    });
  }

  destroy(id): void {
    if (confirm(i18next.t('generic-delete-warning'))) {
      $.post(this.controller, {
        statusDestroy: true,
        id: id
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#status_' + id).remove();
        }
      });
    }
  }
}
