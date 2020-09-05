/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';

export default class TeamGroup {
  controller: string;

  constructor() {
    this.controller = 'app/controllers/TeamGroupsController.php';
  }

  create(): void {
    const name = $('#teamGroupCreate').val() as string;
    if (name.length > 0) {
      $.post(this.controller, {
        teamGroupCreate: name
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#team_groups_div').load('admin.php #team_groups_div');
          $('#teamGroupCreate').val('');
        }
      });
    }
  }

  update(action, user, group): void {
    $.post(this.controller, {
      teamGroupUpdate: true,
      action: action,
      teamGroupUser: user,
      teamGroupGroup: group
    }).done(function() {
      $('#team_groups_div').load('admin.php #team_groups_div');
    });
  }

  destroy(id): void {
    if (confirm(i18next.t('generic-delete-warning'))) {
      $.post(this.controller, {
        teamGroupDestroy: true,
        teamGroupGroup: id
      }).done(function() {
        $('#team_groups_div').load('admin.php #team_groups_div');
      });
    }
  }
}
