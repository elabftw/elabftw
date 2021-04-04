/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Comment from './Comment.class';
import { notif } from './misc';
import i18next from 'i18next';
import { Model, Type, Entity } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;
  let entityType: Type;
  if (about.type === 'experiments') {
    entityType = Type.Experiment;
  }
  if (about.type === 'items') {
    entityType = Type.Item;
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
    ($(this) as any).editable('app/controllers/Ajax.php', {
      type : 'textarea',
      submitdata: (revert, settings, submitdata) => {
        return {
          //action: Action.Update,
          action: 'updateComment',
          what: Model.Comment,
          type: entity.type,
          params: {
            itemId: entity.id,
            comment: submitdata.value,
            id: $(this).data('commentid'),
          },
        };
      },
      width: '80%',
      height: '200',
      tooltip : i18next.t('click-to-edit'),
      indicator : i18next.t('saving'),
      submit : i18next.t('save'),
      cancel : i18next.t('cancel'),
      style : 'display:inline',
      submitcssclass : 'button btn btn-primary mt-2',
      cancelcssclass : 'button btn btn-danger mt-2',
      callback : function(data: string) {
        const json = JSON.parse(data);
        notif(json);
        // show result in comment box
        if (json.res) {
          $(this).html(json.value);
        }
      }
    });
  });

  // DESTROY COMMENTS
  $('#comment_container').on('click', '.commentsDestroy', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      CommentC.destroy($(this).data('commentid')).then(() => $('#comment_container').load(window.location.href + ' #comment'));
    }
  });
});
