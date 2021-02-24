/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import modal from 'bootstrap';
import 'bootstrap-select';
import { relativeMoment, displayMolFiles, makeSortableGreatAgain } from './misc';
import i18next from 'i18next';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // set the language for js translated strings
  i18next.changeLanguage($('#user-prefs').data('lang'));

  // TOGGLABLE
  $(document).on('click', '.togglableNext', function() {
    $(this).next().toggle();
  });

  // Toggle modal
  $('.modalToggle').on('click', function() {
    ($('#' + $(this).data('modal')) as any).modal('toggle');
  });


  makeSortableGreatAgain();
  relativeMoment();
  displayMolFiles();

  // SHOW/HIDE THE DOODLE CANVAS/CHEM EDITOR/JSON EDITOR
  $(document).on('click', '.plusMinusButton',  function() {
    if ($(this).html() === '+') {
      $(this).html('-').addClass('btn-neutral').removeClass('btn-primary');
    } else {
      $(this).html('+').removeClass('btn-neutral').addClass('btn-primary');
    }
  });

  // SHOW/HIDE PASSWORDS
  $('.togglePassword').on('click', function(event) {
    event.preventDefault();
    $(this).find('[data-fa-i2svg]').toggleClass('fa-eye fa-eye-slash');
    const input = $($(this).data('toggle'));
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
    } else {
      input.attr('type', 'password');
    }
  });

  // CLICK THE CREATE NEW BUTTON
  // done with javascript because if it's a link the css is not clean
  // and there is a gap with the separator
  // also this allows different behavior for exp/items
  $('.createNew').on('click', function() {
    const path = window.location.pathname;
    if (path.split('/').pop() === 'experiments.php') {
      window.location.replace('?create=1');
    } else {
      ($('#createModal') as any).modal('toggle');
    }
  });

  $('.logout').on('click', function() {
    localStorage.removeItem('isTodolistOpen');
    location.href = 'app/logout.php';
  });
});
