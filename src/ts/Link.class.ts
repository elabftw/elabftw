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

  create(elem): void {
    const id = elem.data('id');
    // get link
    const link = elem.val();
    // fix for user pressing enter with no input
    if (link.length > 0) {
      // parseint will get the id, and not the rest (in case there is number in title)
      const linkId = parseInt(link, 10);
      if (!isNaN(linkId)) {
        this.send({
          action: 'create',
          what: 'link',
          type: this.type,
          params: {
            itemId: id,
            id: linkId,
          },
        }).then(() => {
          // reload the link list
          $('#links_div_' + id).load(window.location.href + ' #links_div_' + id);
          // clear input field
          elem.val('');
        });
      } // end if input is bad
    } // end if input < 0
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
