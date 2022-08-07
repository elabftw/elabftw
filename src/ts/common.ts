/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Ajax } from './Ajax.class';
import { Api } from './Apiv2.class';
import 'bootstrap-select';
import 'bootstrap/js/src/modal.js';
import { notif, makeSortableGreatAgain, reloadElement, adjustHiddenState, getEntity } from './misc';
import i18next from 'i18next';
import EntityClass from './Entity.class';
import { PartialEntity, EntityType, Payload, Target, Method, Model, Action } from './interfaces';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
declare const MathJax: MathJaxObject;
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
  const ApiC = new Api();

  // set the language for js translated strings
  i18next.changeLanguage(document.getElementById('user-prefs').dataset.lang);

  makeSortableGreatAgain();

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

  // CAN READ/WRITE SELECT
  $(document).on('change', '.permissionSelect', function() {
    const value = $(this).val();
    const rw = $(this).data('rw');
    const params = {};
    params[rw] = value;
    const entity = getEntity();
    return ApiC.patch(`${entity.type}/${entity.id}`, params);
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
     * Clicking on it will toggle the "hidden" attribute of the next sibling element by default.
     * If there is a data-toggle-next-n value, the "hidden" attribute of the nth next sibling element will be toggled.
     * If there is a data-icon value, it is toggled > or V
     */
    } else if (el.matches('[data-action="toggle-next"]')) {
      const n = Array.from(el.parentNode.children).indexOf(el) + (parseInt(el.dataset.toggleNextN, 10) || 1);
      const targetEl = el.parentNode.children[n] as HTMLElement;
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
        reloadElement('navbarNotifDiv');
      });

    } else if (el.matches('[data-action="export-user"]')) {
      const source = (document.getElementById('userExport') as HTMLSelectElement).value;
      const format = (document.getElementById('userExportFormat') as HTMLSelectElement).value;
      window.location.href = `make.php?what=${format}&user=${source}&type=experiments`;


    // CREATE EXPERIMENT or DATABASE item: main create button in top right
    } else if (el.matches('[data-action="create-entity"]')) {
      const path = window.location.pathname;
      const page = path.split('/').pop();
      // team.php for "create experiment from this template
      if (page === 'experiments.php' || page === 'team.php') {
        const tplid = el.dataset.tplid;
        const urlParams = new URLSearchParams(document.location.search);
        const tags = urlParams.getAll('tags[]');
        (new EntityClass(EntityType.Experiment)).create(tplid, tags).then(resp => window.location.href = resp.headers.get('location'));
      } else {
        // for database items, show a selection modal
        // modal plugin requires jquery
        ($('#createModal') as JQuery).modal('toggle');
      }
    } else if (el.matches('[data-action="import-file"]')) {
      ($('#importModal') as JQuery).modal('toggle');
    } else if (el.matches('[data-action="create-item"]')) {
      const tplid = el.dataset.tplid;
      const urlParams = new URLSearchParams(document.location.search);
      const tags = urlParams.getAll('tags[]');
      (new EntityClass(EntityType.Item)).create(tplid, tags).then(resp => window.location.href = resp.headers.get('location'));

    } else if (el.matches('[data-action="toggle-body"]')) {
      const randId = el.dataset.randid;
      const plusMinusIcon = el.querySelector('.fas');
      const bodyDiv = document.getElementById(randId);
      let action = 'hide';
      // transform the + in - and vice versa
      if (bodyDiv.hasAttribute('hidden')) {
        plusMinusIcon.classList.remove('fa-plus-circle');
        plusMinusIcon.classList.add('fa-minus-circle');
        action = 'show';
      } else {
        plusMinusIcon.classList.add('fa-plus-circle');
        plusMinusIcon.classList.remove('fa-minus-circle');
      }
      // don't reload body if it is already loaded for show action
      // and the hide action is just toggle hidden attribute and do nothing else
      if ((action === 'show' && bodyDiv.dataset.bodyLoaded) || action === 'hide') {
        bodyDiv.toggleAttribute('hidden');
        return;
      }

      // prepare the get request
      const entityType = el.dataset.type === 'experiments' ? EntityType.Experiment : EntityType.Item;
      const payload: Payload = {
        method: Method.GET,
        action: Action.Read,
        model: entityType,
        entity: {
          type: entityType,
          id: parseInt(el.dataset.id, 10),
        },
        target: Target.Body,
      };
      (new Ajax()).send(payload).then(json => {
        // add html content and adjust the width of the children
        bodyDiv.innerHTML = (json.value as PartialEntity).body;
        // get the width of the parent. The -30 is to make it smaller than parent even with the margins
        const width = document.getElementById('parent_' + randId).clientWidth - 30;
        bodyDiv.style.width = String(width);
        bodyDiv.toggleAttribute('hidden');
        bodyDiv.dataset.bodyLoaded = '1';
        // ask mathjax to reparse the page
        MathJax.typeset();
      });
    }
  });
});
