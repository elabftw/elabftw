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
import { notif, makeSortableGreatAgain } from './misc';
import i18next from 'i18next';
import EntityClass from './Entity.class';
import { EntityType, Payload, Method, Model, Action } from './interfaces';
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

document.addEventListener('DOMContentLoaded', () => {

  // HEARTBEAT
  // this function is to check periodically that we are still authenticated
  // and show a message if we the session is not valid anymore but we are still on a page requiring auth
  // only run if we are an auth user by checking the presence of this element in the footer
  if (document.getElementById('is-auth')) {
    // check every 5 minutes
    const heartRate = 300000;
    setInterval(() => {
      fetch('app/controllers/HeartBeat.php').then(response => {
        if (!response.ok) {
          alert('Your session expired!');
          window.location.replace('login.php');
        }
      });
    }, heartRate);
  }

  // DEPRECATED, this can go away once all $.post disappeared and everyone uses custom Ajax class
  // TODO
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content'),
    },
  });

  // set the language for js translated strings
  i18next.changeLanguage(document.getElementById('user-prefs').dataset.lang);

  makeSortableGreatAgain();

  // SHOW/HIDE THE DOODLE CANVAS/CHEM EDITOR/JSON EDITOR
  const plusMinusButton = document.getElementsByClassName('plusMinusButton');
  if (plusMinusButton) {
    Array.from(plusMinusButton).forEach(element => {
      element.addEventListener('click', (event) => {
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
    });
  }

  /**
   * MAIN click event listener bound to container
   * this will listen for click events on the container and if the element
   * matches a known action then that action is triggered
   */
  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // SHOW PRIVACY POLICY
    if (el.matches('[data-action="show-privacy-policy"]')) {
      const payload: Payload = {
        method: Method.UNAUTHGET,
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

    // TOGGLE NEXT ACTION
    } else if (el.matches('[data-action="toggle-next"]')) {
      el.nextElementSibling.toggleAttribute('hidden');

    // TOGGLE MODAL
    } else if (el.matches('[data-action="toggle-modal"]')) {
      // TODO this requires jquery for now. Not in BS5.
      ($('#' + el.dataset.target) as JQuery<HTMLDivElement>).modal('toggle');
      // special code to select the existing permissions for templates on ucp/templates-edit page
      if (window.location.pathname === '/ucp.php') {
        (document.querySelector(`#canread_select option[value="${el.dataset.read}"]`) as HTMLOptionElement).selected = true;
        (document.querySelector(`#canwrite_select option[value="${el.dataset.write}"]`) as HTMLOptionElement).selected = true;
      }

    // PASSWORD VISIBILITY TOGGLE
    } else if (el.matches('[data-action="toggle-password"]')) {
      // toggle eye icon
      const icon = el.firstChild as HTMLElement;
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');

      // toggle input type
      const input = document.getElementById(el.dataset.target);
      let attribute = 'password';
      if (input.getAttribute('type') === 'password') {
        attribute = 'text';
      }
      input.setAttribute('type', attribute);

    // LOGOUT
    } else if (el.matches('[data-action="logout"]')) {
      localStorage.removeItem('isTodolistOpen');
      window.location.href = 'app/logout.php';

    // CREATE EXPERIMENT or DATABASE item: main create button in top right
    } else if (el.matches('[data-action="create-entity"]')) {
      const path = window.location.pathname;
      if (path.split('/').pop() === 'experiments.php') {
        const tplid = el.dataset.tplid;
        (new EntityClass(EntityType.Experiment)).create(tplid).then(json => {
          if (json.res) {
            window.location.replace(`?mode=edit&id=${json.value}`);
          } else {
            notif(json);
          }
        });
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
