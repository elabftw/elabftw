/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Comment from './Comment.class';
import i18next from 'i18next';
import { relativeMoment, reloadElement, getEntity } from './misc';

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

  // observe the comment container for changes
  // this observer will make the relative dates displayed again
  new MutationObserver(() => relativeMoment())
    .observe(document.getElementById('commentsDiv'), {childList: true, subtree: true});

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

  // MAKE comments editable on mousehover
  $(document).on('mouseenter', '.comment-editable', function() {
    ($(this) as any).editable(function(input: string) {
      CommentC.update(input, $(this).data('commentid'));
      return (input);
    }, {
      type : 'textarea',
      width: '80%',
      height: '200',
      tooltip : i18next.t('click-to-edit'),
      indicator : i18next.t('saving'),
      submit : i18next.t('save'),
      cancel : i18next.t('cancel'),
      style : 'display:inline',
      submitcssclass : 'button btn btn-primary mt-2',
      cancelcssclass : 'button btn btn-danger mt-2',
      callback : () => {
        // use setTimeout to give the time for sql to change the data before we fetch it
        setTimeout(() => reloadElement('comment'), 20);
      },
    });
  });
});
