/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Comment from './Comment.class';
import i18next from 'i18next';
import { Entity, EntityType } from './interfaces';
import { relativeMoment } from './misc';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
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

  const entity: Entity = {
    type: entityType,
    id: parseInt(about.id),
  };

  const CommentC = new Comment(entity);

  // CREATE COMMENTS
  $('#comment_container').on('click', '#commentsCreateButton', function() {
    const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
    CommentC.create(content).then(() => $('#comment_container').load(window.location.href + ' #comment'));
  });

  // MAKE comments editable on mousehover
  $(document).on('mouseenter', '.comment-editable', function() {
    ($(this) as any).editable(function(input: string) {
      CommentC.update(input, $(this).data('commentid'));
      return(input);
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
        setTimeout(() => {
          return fetch(window.location.href).then(response => {
            return response.text();
          }).then(data => {
            const parser = new DOMParser();
            const html = parser.parseFromString(data, 'text/html');
            document.getElementById('comment').innerHTML = html.getElementById('comment').innerHTML;
            relativeMoment();
          });
        }, 20);
      },
    });
  });

  // DESTROY COMMENTS
  $('#comment_container').on('click', '.commentsDestroy', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      CommentC.destroy($(this).data('commentid')).then(() => $('#comment_container').load(window.location.href + ' #comment'));
    }
  });
});
