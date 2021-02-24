/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import { notif } from './misc';
import { getTinymceBaseConfig } from './tinymce';
import tinymce from 'tinymce/tinymce';

export default class ItemType extends Crud {
  what: string;

  constructor() {
    super('app/controllers/Ajax.php');
    this.what = 'itemsTypes';
  }

  create(): void {
    const name = $('#itemsTypesName').val();
    if (name === '') {
      notif({'res': false, 'msg': 'Name cannot be empty'});
      $('#itemsTypesName').css('border-color', 'red');
      return;
    }
    const color = $('#itemsTypesColor').val();
    const checkbox = $('#itemsTypesBookable').is(':checked');
    let bookable = 0;
    if (checkbox) {
      bookable = 1;
    }

    const template = tinymce.get('itemsTypesTemplate').getContent();

    this.send({
      action: 'create',
      what: this.what,
      params: {
        template: template,
        name: name,
        color: color,
        bookable: bookable,
      },
    }).then(function() {
      window.location.replace('admin.php?tab=5');
    });
  }

  showEditor(id): void {
    $('#itemsTypesTemplate_' + id).addClass('mceditable');
    tinymce.init(getTinymceBaseConfig('items_types'));
    $('#itemsTypesEditor_' + id).toggle();
  }

  update(id): void {
    const name = $('#itemsTypesName_' + id).val();
    const color = $('#itemsTypesColor_' + id).val();
    const checkbox = $('#itemsTypesBookable_' + id).is(':checked');
    let bookable = 0;
    if (checkbox) {
      bookable = 1;
    }
    // if tinymce is hidden, it'll fail to trigger
    // so we toggle it quickly to grab the content
    if ($('#itemsTypesTemplate_' + id).is(':hidden')) {
      this.showEditor(id);
    }
    const template = tinymce.get('itemsTypesTemplate_' + id).getContent();
    $('#itemsTypesEditor_' + id).toggle();

    this.send({
      action: 'update',
      what: this.what,
      params: {
        id: id,
        template: template,
        name: name,
        color: color,
        bookable: bookable,
      },
    });
  }

  destroy(id): void {
    this.send({
      action: 'destroy',
      what: this.what,
      params: {
        id: id,
      },
    }).then(function(response) {
      if (response.res) {
        $('#itemstypes_' + id).hide();
        $('#itemstypesOrder_' + id).hide();
      }
    });
  }
}
