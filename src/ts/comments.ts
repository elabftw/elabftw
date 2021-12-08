/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { InputType, Malle } from 'malle';
import { relativeMoment, getEntity } from './misc';
import Comment from './Comment.class';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in edit mode
  if (about.page !== 'view') {
    return;
  }
  const CommentC = new Comment(getEntity());

  document.getElementById('commentsDiv').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // CREATE COMMENT
    if (el.matches('[data-action="create-comment"]')) {
      const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
      CommentC.create(content).then(() => $('#commentsDiv').load(window.location.href + ' #comment'));

    // DESTROY COMMENT
    } else if (el.matches('[data-action="destroy-comment"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        CommentC.destroy(parseInt(el.dataset.target, 10)).then(() => $('#commentsDiv').load(window.location.href + ' #comment'));
      }
    }
  });

  // UPDATE MALLEABLE COMMENT
  const malleableComments = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
    inputClasses: ['form-control'],
    fun: (value, original) => {
      CommentC.update(value, parseInt(original.dataset.id, 10));
      return value;
    },
    inputType: InputType.Textarea,
    listenOn: '.comment.editable',
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // listen on existing comments
  malleableComments.listen();

  // add an observer so new comments will get an event handler too
  new MutationObserver(() => {
    malleableComments.listen();
    relativeMoment();
  }).observe(document.getElementById('commentsDiv'), {childList: true});
});
