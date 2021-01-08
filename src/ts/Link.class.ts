/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import i18next from 'i18next';

export default class Link extends Crud {
  type: string;

  constructor(type: string) {
    super('app/controllers/Ajax.php');
    this.type = type;
  }

  create(targetId: number, itemId: number): void {
    this.send({
      action: 'create',
      what: 'link',
      type: this.type,
      params: {
        itemId: itemId,
        id: targetId,
      },
    }).then(() => {
      // reload the link list
      $('#links_div_' + itemId).load(window.location.href + ' #links_div_' + itemId);
      // clear input field
      $('.linkinput').val('');
    });
  }

  destroy(elem): void {
    const id = elem.data('id') as number;
    if (confirm(i18next.t('link-delete-warning'))) {
      this.send({
        action: 'destroy',
        what: 'link',
        type: this.type,
        params: {
          itemId: id,
          id: elem.data('linkid') as number,
        },
      }).then(() => {
        $('#links_div_' + id).load(window.location.href + ' #links_div_' + id);
      });
    }
  }
}
