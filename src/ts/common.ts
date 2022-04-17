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
import { notif, makeSortableGreatAgain, reloadElement, adjustHiddenState } from './misc';
import i18next from 'i18next';
import EntityClass from './Entity.class';
import { EntityType, Payload, Target, Method, Model, Action } from './interfaces';
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
          localStorage.clear();
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

  const AjaxC = new Ajax();

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

  // BACK TO TOP BUTTON
  const btn = document.createElement('div');
  btn.dataset.action = 'scroll-top';
  // make it look like a button, and on the right side of the screen, not too close from the bottom
  btn.classList.add('btn', 'btn-neutral', 'floating-middle-right');
  // element is invisible at first so we can make it visible so it triggers a css transition and appears progressively
  btn.style.opacity = '0';
  // will not be shown for small screens, only large ones
  btn.classList.add('d-none', 'd-xl-inline', 'd-lg-inline');
  // the button is an up arrow
  btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
  // give it an id so we can remove it easily
  btn.setAttribute('id', 'backToTopButton');

  // called when viewport approaches the footer
  const intersectionCallback = (entries): void => {
    // if we haven't scrolled much (not a long content or screen is big), do nothing
    if (window.scrollY < 100) {
      return;
    }
    entries.forEach(entry => {
      if (entry.isIntersecting && !document.getElementById('backToTopButton')) {
        const addedBtn = document.getElementById('container').appendChild(btn);
        // here we use requestAnimationFrame or the browser won't see the change and the css transition won't be triggered
        requestAnimationFrame(() => {
          addedBtn.style.opacity = '100';
        });
      } else {
        // if we're not intersecting, remove the button if it's here
        if (document.getElementById('backToTopButton')) {
          document.getElementById('backToTopButton').remove();
        }
      }
    });
  };

  // rootMargin: allow bigger margin for footer
  const observer = new IntersectionObserver(intersectionCallback, { rootMargin: '600px' });
  // the footer is our trigger element
  observer.observe(document.querySelector('footer'));
  // END BACK TO TOP BUTTON

  // Add a listener for all elements triggered by an event
  // and POST an update request
  // select will be on change, text inputs on blur
  function listenTrigger(): void {
    document.querySelectorAll('[data-trigger]').forEach((el: HTMLInputElement) => {
      el.addEventListener(el.dataset.trigger, event => {
        event.preventDefault();
        // for a checkbox element, look at the checked attribute, not the value
        const value = el.type === 'checkbox' ? el.checked ? '1' : '0' : el.value;
        const payload: Payload = {
          method: Method.POST,
          action: el.dataset.action as Action ?? Action.Update,
          model: el.dataset.model as Model,
          target: el.dataset.target as Target,
          content: value,
          notif: true,
        };
        AjaxC.send(payload)
          .then(json => notif(json))
          .then(() => {
            if (el.dataset.reload) {
              reloadElement(el.dataset.reload).then(() => {
                // make sure we listen to the new element too
                listenTrigger();
              });
            }
          });
      });
    });
  }
  listenTrigger();

  /**
   * Timestamp provider select
   */
  if (document.getElementById('ts_authority')) {
    const select = (document.getElementById('ts_authority') as HTMLSelectElement);
    const noAccountTsa = ['dfn', 'digicert', 'sectigo', 'globalsign'];
    select.addEventListener('change', () => {
      if (noAccountTsa.includes(select.value)) {
        // mask all
        document.getElementById('ts_loginpass').toggleAttribute('hidden', true);
        document.getElementById('ts_urldiv').toggleAttribute('hidden', true);
      } else if (select.value === 'universign') {
        // only make loginpass visible
        document.getElementById('ts_loginpass').removeAttribute('hidden');
        document.getElementById('ts_urldiv').toggleAttribute('hidden', true);
      } else if (select.value === 'custom') {
        // show all
        document.getElementById('ts_loginpass').removeAttribute('hidden');
        document.getElementById('ts_urldiv').removeAttribute('hidden');
      }
    });
  }

  adjustHiddenState();

  /**
   * Make sure the icon for toggle-next is correct depending on the stored state in localStorage
   */
  document.querySelectorAll('[data-icon]').forEach(el => {
    const iconEl = document.getElementById((el as HTMLElement).dataset.icon);
    if (el.nextElementSibling.hasAttribute('hidden')) {
      iconEl.classList.remove('fa-chevron-circle-down');
      iconEl.classList.add('fa-chevron-circle-right');
    } else {
      iconEl.classList.add('fa-chevron-circle-down');
      iconEl.classList.remove('fa-chevron-circle-right');
    }
  });

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
      AjaxC.send(payload).then(json => {
        let policy = json.value as string;
        if (!policy) {
          policy = 'No privacy policy is set.';
        }
        (document.getElementById('privacyModalBody') as HTMLDivElement).innerHTML = policy;
        // modal plugin requires jquery
        ($('#privacyModal') as JQuery).modal('toggle');
      });

    // SCROLL TO TOP
    } else if (el.matches('[data-action="scroll-top"]')) {
      document.documentElement.scrollTo({
        top: 0,
        behavior: 'smooth',
      });

    /* TOGGLE NEXT ACTION
     * An element with "toggle-next" as data-action value will appear clickable.
     * Clicking on it will toggle the "hidden" attribute of the next sibling element.
     * If there is a data-icon value, it is toggled > or V
     */
    } else if (el.matches('[data-action="toggle-next"]')) {
      const targetEl = el.nextElementSibling as HTMLElement;
      targetEl.toggleAttribute('hidden');
      if (el.dataset.icon) {
        const iconEl = document.getElementById(el.dataset.icon);
        if (targetEl.hasAttribute('hidden')) {
          iconEl.classList.remove('fa-chevron-circle-down');
          iconEl.classList.add('fa-chevron-circle-right');
        } else {
          iconEl.classList.add('fa-chevron-circle-down');
          iconEl.classList.remove('fa-chevron-circle-right');
        }
      }
      // save the hidden state of the target element in localStorage
      if (targetEl.dataset.saveHidden) {
        const targetKey = targetEl.dataset.saveHidden + '-isHidden';
        const value = targetEl.hasAttribute('hidden') ? '1' : '0';
        localStorage.setItem(targetKey, value);
      }

    // REPLACE WITH NEXT ACTION
    } else if (el.matches('[data-action="replace-with-next"]')) {
      const targetEl = el.nextElementSibling as HTMLElement;
      // show the target
      targetEl.toggleAttribute('hidden');
      // hide clicked element
      el.toggleAttribute('hidden');

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
      localStorage.clear();
      window.location.href = 'app/logout.php';

    // ACK NOTIF
    } else if (el.matches('[data-action="ack-notif"]')) {
      const payload: Payload = {
        method: Method.POST,
        action: Action.Update,
        model: Model.Notification,
        // use the finished target of steps for changing is_ack
        // so we don't need to add another target
        target: Target.Finished,
        id: parseInt(el.dataset.id, 10),
      };
      if (el.parentElement.dataset.ack === '0') {
        AjaxC.send(payload).then(() => {
          if (el.dataset.href) {
            window.location.href = el.dataset.href;
          } else {
            reloadElement('navbarNotifDiv');
          }
        });
      } else {
        if (el.dataset.href) {
          window.location.href = el.dataset.href;
        }
      }

    // DESTROY (clear all) NOTIF
    } else if (el.matches('[data-action="destroy-notif"]')) {
      const payload: Payload = {
        method: Method.POST,
        action: Action.Destroy,
        model: Model.Notification,
      };
      AjaxC.send(payload).then(() => {
        document.querySelectorAll('.notification').forEach(el => el.remove());
      });

    // CREATE EXPERIMENT or DATABASE item: main create button in top right
    } else if (el.matches('[data-action="create-entity"]')) {
      const path = window.location.pathname;
      if (path.split('/').pop() === 'experiments.php') {
        const tplid = el.dataset.tplid;
        const urlParams = new URLSearchParams(document.location.search);
        const tags = urlParams.getAll('tags[]');
        (new EntityClass(EntityType.Experiment)).create(tplid, tags).then(json => {
          if (json.res) {
            window.location.replace(`?mode=edit&id=${json.value}`);
          } else {
            notif(json);
          }
        });
      } else {
        // for database items, show a selection modal
        // modal plugin requires jquery
        ($('#createModal') as JQuery).modal('toggle');
      }
    } else if (el.matches('[data-action="create-item"]')) {
      const tplid = el.dataset.tplid;
      const urlParams = new URLSearchParams(document.location.search);
      const tags = urlParams.getAll('tags[]');
      (new EntityClass(EntityType.Item)).create(tplid, tags).then(json => window.location.replace(`?mode=edit&id=${json.value}`));
    }
  });
});
