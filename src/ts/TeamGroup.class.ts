/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import i18next from 'i18next';

export default class TeamGroup extends Crud {

  constructor() {
    super('app/controllers/Ajax.php');
  }

  create(): void {
    const name = $('#teamGroupCreate').val() as string;
    if (name.length > 0) {
      this.send({
        action: 'create',
        what: 'teamgroup',
        params: {
          name: name,
        },
      }).then((json) => {
        if (json.res) {
          $('#team_groups_div').load('admin.php #team_groups_div');
          $('#teamGroupCreate').val('');
        }
      });
    }
  }

  update(how, user, group): void {
    this.send({
      action: 'updateMember',
      what: 'teamgroup',
      params: {
        'user': user,
        'group': group,
        'how': how,
      },
    }).then(() => {
      $('#team_groups_div').load('admin.php #team_groups_div');
    });
  }

  destroy(id): void {
    if (confirm(i18next.t('generic-delete-warning'))) {
      this.send({
        action: 'destroy',
        what: 'teamgroup',
        params: {
          id: id,
        },
      }).then(() => {
        $('#team_groups_div').load('admin.php #team_groups_div');
      });
    }
  }
}
