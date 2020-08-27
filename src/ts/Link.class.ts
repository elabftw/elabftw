/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';

export default class Link {
  controller: string;
  type: string;

  constructor(type: string) {
    this.type = type;
    this.controller = 'app/controllers/EntityAjaxController.php';
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
        $.post(this.controller, {
          createLink: true,
          id: id,
          linkId: linkId,
          type: this.type
        }).done(function () {
          // reload the link list
          $('#links_div_' + id).load(window.location.href + ' #links_div_' + id);
          // clear input field
          elem.val('');
        });
      } // end if input is bad
    } // end if input < 0
  }

  destroy(elem): void {
    const id = elem.data('id');
    const linkId = elem.data('linkid');
    if (confirm(i18next.t('link-delete-warning'))) {
      $.post(this.controller, {
        destroyLink: true,
        id: id,
        linkId: linkId,
        type: this.type
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#links_div_' + id).load(window.location.href + ' #links_div_' + id);
        }
      });
    }
  }
}
