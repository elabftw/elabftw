/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Ajax } from './Ajax.class';
import 'bootstrap-select';
import 'bootstrap/js/src/modal.js';
import { relativeMoment, displayMolFiles, makeSortableGreatAgain } from './misc';
import i18next from 'i18next';
import EntityClass from './Entity.class';
import { EntityType, Payload, Method, Model, Action } from './interfaces';
import 'marked';
import 'bootstrap-markdown-fa5/js/bootstrap-markdown';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.de.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.es.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.fr.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.it.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.ja.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.kr.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.nl.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.pl.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.ptBR.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.ru.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.sl.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.sv.js';
import 'bootstrap-markdown-fa5/locale/bootstrap-markdown.zh.js';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // set the language for js translated strings
  i18next.changeLanguage($('#user-prefs').data('lang'));

  // TOGGLABLE NEXT
  const toggleNextElem = document.querySelector('[data-action="toggle-next"]');
  if (toggleNextElem) {
    toggleNextElem.addEventListener('click', (event) => {
      (event.target as HTMLElement).nextElementSibling.toggleAttribute('hidden');
    });
  }

  // Toggle modal
  $('.modalToggle').on('click', function() {
    ($('#' + $(this).data('modal')) as any).modal('toggle');
  });


  makeSortableGreatAgain();
  relativeMoment();
  displayMolFiles();

  // SHOW/HIDE THE DOODLE CANVAS/CHEM EDITOR/JSON EDITOR
  const plusMinusButton = document.querySelector('.plusMinusButton');
  if (plusMinusButton) {
    plusMinusButton.addEventListener('click', (event) => {
      const el = (event.target as HTMLElement);
      if (el.innerText === '+') {
        el.classList.add('btn-neutral');
        el.classList.remove('btn-primary');
        el.innerText = '-';
      } else {
        el.classList.add('btn-primary');
        el.classList.remove('btn-neutral');
        el.innerText = '+';
      }
    });
  }

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

  document.querySelector('#container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // SHOW PRIVACY POLICY
    if (el.matches('[data-action="show-privacy-policy"]')) {
      const payload: Payload = {
        method: Method.GET,
        action: Action.Read,
        model: Model.PrivacyPolicy,
      };
      const AjaxC = new Ajax();
      AjaxC.send(payload).then(json => {
        let policy = json.value as string;
        if (!policy) {
          policy = 'No privacy policy is set.';
        }
        (document.getElementById('privacyModalBody') as HTMLDivElement).innerHTML = policy;
        // modal plugin requires jquery
        ($('#privacyModal') as any).modal('toggle');
      });

    // LOGOUT
    } else if (el.matches('[data-action="logout"]')) {
      localStorage.removeItem('isTodolistOpen');
      window.location.href = 'app/logout.php';

    // CREATE EXPERIMENT or DATABASE item: main create button in top right
    } else if (el.matches('[data-action="create-entity"]')) {
      const path = window.location.pathname;
      if (path.split('/').pop() === 'experiments.php') {
        const tplid = el.dataset.tplid;
        (new EntityClass(EntityType.Experiment)).create(tplid).then(json => window.location.replace(`?mode=edit&id=${json.value}`));
      } else {
        // for database items, show a selection modal
        // modal plugin requires jquery
        ($('#createModal') as any).modal('toggle');
      }
    } else if (el.matches('[data-action="create-item"]')) {
      const tplid = el.dataset.tplid;
      (new EntityClass(EntityType.Item)).create(tplid).then(json => window.location.replace(`?mode=edit&id=${json.value}`));
    }
  });
});
