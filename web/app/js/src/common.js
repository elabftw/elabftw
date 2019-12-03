/*
 * Common functions used by eLabFTW
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// add a csrf header to all ajax requests in a meta tag
$.ajaxSetup({
  headers: {
    'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
  }
});

$(document).ready(function() {
  // TOGGLABLE
  $(document).on('click', '.togglableNext', function() {
    $(this).next().toggle();
  });
  $('.togglableHidden').hide();

  // HELP MODAL
  $(document).on('click', '.helpToggle', function() {
    $('#helpModal').modal('toggle');
  });

  // SORTABLE ELEMENTS
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
      // send the orders as an array
      var ordering = $(this).sortable('toArray');

      $.post('app/controllers/SortableAjaxController.php', {
        table: $(this).data('table'),
        ordering: ordering
      }).done(function(json) {
        notif(json);
      });
    }
  });
});

// for editXP/DB, ctrl-shift-D will add the date
function addDateOnCursor() { // eslint-disable-line no-unused-vars
  var todayDate = new Date();
  var year = todayDate.getFullYear();
  // we use +1 on the month because january is 0
  var month = todayDate.getMonth() + 1;
  // we want to have two digits on the month
  if (month < 10) {
    month = '0' + month;
  }
  var day = todayDate.getDate();
  // we want to have two digits on the day
  if (day < 10) {
    day = '0' + day;
  }

  tinymce.activeEditor.execCommand('mceInsertContent', false, year + '-' + month + '-' + day + ' ');
}

// notifications (saved messages and such)
// json is an object with at least 'msg' to show to user and bool res for result of operation
function notif(json) {
  const htmlText = '<p>' + json.msg + '</p>';
  let result = 'ko';
  if (json.res) {
    result = 'ok';
  }
  var overlay = document.createElement('div');
  overlay.setAttribute('id','overlay');
  overlay.setAttribute('class', 'overlay ' + 'overlay-' + result);
  // show the overlay
  document.body.appendChild(overlay);
  // add text inside
  document.getElementById('overlay').innerHTML = htmlText;
  // wait a bit and make it disappear
  window.setTimeout(function() {
    $('#overlay').fadeOut(763, function() {
      $(this).remove();
    });
  }, 2733);
}

/* for menus on team, admin, sysconfig and ucp */

/* parse the $_GET from the url */
function getGetParameters() { // eslint-disable-line no-unused-vars
  var prmstr = window.location.search.substr(1);
  return prmstr !== null && prmstr !== '' ? transformToAssocArray(prmstr) : {};
}

/* put the $_GET in array */
function transformToAssocArray( prmstr ) {
  var params = {};
  var prmarr = prmstr.split('&');
  for (var i = 0; i < prmarr.length; i++) {
    var tmparr = prmarr[i].split('=');
    params[tmparr[0]] = tmparr[1];
  }
  return params;
}

// called when you click the save button of tinymce
function quickSave(type, id) { // eslint-disable-line no-unused-vars
  $.post('app/controllers/EntityAjaxController.php', {
    quickSave: true,
    type : type,
    id : id,
    // we need this to get the updated content
    title : document.getElementById('title_input').value,
    date : document.getElementById('datepicker').value,
    body : tinymce.activeEditor.getContent()
  }).done(function(json, textStatus, xhr) {
    // detect if the session timedout
    if (xhr.getResponseHeader('X-Elab-Need-Auth') === '1') {
      // store the modifications in local storage to prevent any data loss
      localStorage.setItem('body', tinymce.activeEditor.getContent());
      localStorage.setItem('id', id);
      localStorage.setItem('type', type);
      localStorage.setItem('date', new Date().toLocaleString());
      // reload the page so user gets redirected to the login page
      location.reload();
      return;
    }
    notif(json);
  });
}

// insert a get param in the url and reload the page
function insertParamAndReload(key, value) { // eslint-disable-line no-unused-vars
  key = escape(key); value = escape(value);

  var kvp = document.location.search.substr(1).split('&');
  if (kvp === '') {
    document.location.search = '?' + key + '=' + value;
  } else {

    var i = kvp.length; var x; while (i--) {
      x = kvp[i].split('=');

      if (x[0] === key) {
        x[1] = value;
        kvp[i] = x.join('=');
        break;
      }
    }

    if (i < 0) { kvp[kvp.length] = [key, value].join('='); }

    // reload the page
    document.location.search = kvp.join('&');
  }
}

// DISPLAY 2D MOL FILES
function displayMolFiles() { // eslint-disable-line no-unused-vars
  // loop all the mol files and display the molecule with ChemDoodle
  $.each($('.molFile'), function() {
    // id of the canvas to attach the viewer to
    var id = $(this).attr('id');
    // now get the file content and display it in the viewer
    ChemDoodle.io.file.content($(this).data('molpath'), function(fileContent){
      var mol = ChemDoodle.readMOL(fileContent);
      var viewer = new ChemDoodle.ViewerCanvas(id, 250, 250);
      // config some stuff in the viewer
      viewer.specs.bonds_width_2D = 0.6;
      viewer.specs.bonds_saturationWidth_2D = 0.18;
      viewer.specs.bonds_hashSpacing_2D = 2.5;
      viewer.specs.atoms_font_size_2D = 10;
      viewer.specs.atoms_font_families_2D = ['Helvetica', 'Arial', 'sans-serif'];
      viewer.specs.atoms_displayTerminalCarbonLabels_2D = true;
      // load it
      viewer.loadMolecule(mol);
    });
  });
}
