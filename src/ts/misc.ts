/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let ChemDoodle: any;
import 'jquery-ui/ui/widgets/sortable';
import * as $3Dmol from '3dmol/build/3Dmol-nojquery.js';
import { CheckableItem, ResponseMsg } from './interfaces';
import { DateTime } from 'luxon';
import { EntityType, Entity } from './interfaces';

// get html of current page reloaded via get
function fetchCurrentPage(): Promise<Document>{
  return fetch(window.location.href).then(response => {
    return response.text();
  }).then(data => {
    const parser = new DOMParser();
    return parser.parseFromString(data, 'text/html');
  });
}

// DISPLAY TIME RELATIVE TO NOW
// the datetime is taken from the title of the element so mouse hover will show raw datetime
export function relativeMoment(): void {
  const locale = document.getElementById('user-prefs').dataset.jslang;
  document.querySelectorAll('.relative-moment').forEach(el => {
    const span = el as HTMLElement;
    // do nothing if it's already loaded, prevent infinite loop with mutation observer
    if (span.innerText) {
      return;
    }
    span.innerText = DateTime.fromFormat(span.title, 'yyyy-MM-dd HH:mm:ss', {'locale': locale}).toRelative();
  });
}

// for view or edit mode, get type and id from the page to construct the entity object
export function getEntity(): Entity {
  if (!document.getElementById('info')) {
    throw new Error('Could not find entity info!');
  }
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;
  let entityType: EntityType;
  if (about.type === 'experiments') {
    entityType = EntityType.Experiment;
  }
  if (about.type === 'items') {
    entityType = EntityType.Item;
  }
  if (about.type === 'experiments_templates') {
    entityType = EntityType.Template;
  }
  if (about.type === 'items_types') {
    entityType = EntityType.ItemType;
  }
  let entityId = null;
  if (about.id) {
    entityId = parseInt(about.id);
  }
  return {
    type: entityType,
    id: entityId,
  };
}


// PUT A NOTIFICATION IN TOP LEFT WINDOW CORNER
export function notif(info: ResponseMsg): void {
  // clear an existing one
  if (document.getElementById('overlay')) {
    document.getElementById('overlay').remove();
  }

  const p = document.createElement('p');
  p.innerText = (info.msg as string);
  const result = info.res ? 'ok' : 'ko';
  const overlay = document.createElement('div');
  overlay.setAttribute('id','overlay');
  overlay.setAttribute('class', 'overlay ' + 'overlay-' + result);
  // show the overlay
  document.body.appendChild(overlay);
  // add text inside
  document.getElementById('overlay').appendChild(p);
  // wait a bit and make it disappear
  window.setTimeout(function() {
    $('#overlay').fadeOut(763, function() {
      $(this).remove();
    });
  }, 2733);
}

// DISPLAY 2D MOL FILES
export function displayMolFiles(): void {
  // loop all the mol files and display the molecule with ChemDoodle
  $.each($('.molFile'), function() {
    // id of the canvas to attach the viewer to
    const id = $(this).attr('id');
    // now get the file content and display it in the viewer
    ChemDoodle.io.file.content($(this).data('molpath'), function(fileContent: string){
      const mol = ChemDoodle.readMOL(fileContent);
      const viewer = new ChemDoodle.ViewerCanvas(id, 250, 250);
      // load it
      viewer.loadMolecule(mol);
    });
  });
}

// DISPLAY 3D MOL FILES
export function display3DMolecules(autoload = false): void {
  if (autoload) {
    $3Dmol.autoload();
  }
  // Top left menu to change the style of the displayed molecule
  $('.dropdown-item').on('click', '.3dmol-style', function() {
    const targetStyle = $(this).data('style');
    let options = {};
    const style = {};
    if (targetStyle === 'cartoon') {
      options = { color: 'spectrum' };
    }
    style[targetStyle] = options;

    $3Dmol.viewers[$(this).data('divid')].setStyle(style).render();
  });
}

// insert a get param in the url and reload the page
export function insertParamAndReload(key: string, value: any): void {
  const params = new URLSearchParams(document.location.search.slice(1));
  params.set(key, value);
  // reload the page
  document.location.search = params.toString();
}

// SORTABLE ELEMENTS
export function makeSortableGreatAgain(): void {
  // need an axis and a table via data attribute
  $('.sortable').sortable({
    // limit to horizontal dragging
    axis : $(this).data('axis'),
    helper : 'clone',
    handle : '.sortableHandle',
    // we don't want the Create new pill to be sortable
    cancel: 'nonSortable',
    // do ajax request to update db with new order
    update: function() {
      // send the order as an array
      const ordering = $(this).sortable('toArray');
      $.post('app/controllers/SortableAjaxController.php', {
        table: $(this).data('table'),
        ordering: ordering,
      }).done(function(json) {
        notif(json);
      });
    },
  });
}

export function getCheckedBoxes(): Array<CheckableItem> {
  const checkedBoxes = [];
  $('.item input[type=checkbox]:checked').each(function() {
    checkedBoxes.push({
      id: parseInt($(this).data('id')),
      // the randomid is used to get the parent container and hide it when delete
      randomid: parseInt($(this).data('randomid')),
    });
  });
  return checkedBoxes;
}

// reload the entities in show mode
export async function reloadEntitiesShow(): Promise<void | Response> {
  // get the html
  const html = await fetchCurrentPage();
  // reload items
  document.getElementById('itemList').innerHTML = html.getElementById('itemList').innerHTML;
  // also reload any pinned entities present
  if (document.getElementById('pinned-entities')) {
    document.getElementById('pinned-entities').innerHTML = html.getElementById('pinned-entities').innerHTML;
  }
}

export async function reloadElement(elementId): Promise<void> {
  if (!document.getElementById(elementId)) {
    console.error('Could not find element to reload!');
    return;
  }
  const html = await fetchCurrentPage();
  document.getElementById(elementId).innerHTML = html.getElementById(elementId).innerHTML;
}
