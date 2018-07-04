/**
 * tags.js - for the tags
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';
    $(document).ready(function() {
        let id = $('#entityInfos').data('id');
        let type = $('#entityInfos').data('type');
        if (type === undefined) {
            type = 'experiments_tpl';
        }
        let confirmText = $('#entityInfos').data('confirm');

        class Tag {

            constructor() {
                this.controller = 'app/controllers/TagsController.php';
            }

            saveForTemplate(tplId) {
                // get tag
                const tag = $('#createTagInput_' + tplId).val();
                // POST request
                $.post(this.controller, {
                    createTag: true,
                    tag: tag,
                    item_id: tplId,
                    type: 'experiments_tpl'
                }).done(function () {
                    $('#tags_div_' + tplId).load(' #tags_div_' + tplId);
                    // clear input field
                    $('#createTagInput_' + tplId).val('');
                });
            }

            // REFERENCE A TAG
            save() {
                // get tag
                const tag = $('#createTagInput').val();
                // do nothing if input is empty
                if (tag.length > 0) {
                    // POST request
                    $.post(this.controller, {
                        createTag: true,
                        tag: tag,
                        item_id: id,
                        type: type
                    }).done(function () {
                        $('#tags_div').load(location + '?mode=edit&id=' + id + ' #tags_div');
                        // clear input field
                        $('#createTagInput').val('');
                    });
                }
            }

            // DEDUPLICATE
            deduplicate(tag) {
                $.post(this.controller, {
                    deduplicate: true,
                    tag: tag
                }).done(function (data) {
                    notif(data.msg, 'ok');
                    $('#tag_manager').load(location + '?tab=9 #tag_manager');
                });
            }

            // REMOVE THE TAG FROM AN ENTITY
            unreference(tagId) {
                if (confirm(confirmText)) {
                    $.post(this.controller, {
                        unreferenceTag: true,
                        type: type,
                        item_id: id,
                        tag_id: tagId
                    }).done(function() {
                        $('#tags_div').load(location + '?mode=edit&id=' + id + ' #tags_div');
                    });
                }
            }

            // REMOVE THE TAG FROM AN ENTITY
            unreferenceForTemplate(tagId, tplId) {
                if (confirm(confirmText)) {
                    $.post(this.controller, {
                        unreferenceTag: true,
                        type: type,
                        item_id: tplId,
                        tag_id: tagId
                    }).done(function() {
                        $('#tags_div_' + tplId).load(' #tags_div_' + tplId);
                    });
                }
            }
            // REMOVE A TAG COMPLETELY (from admin panel/tag manager)
            destroy(tagId) {
                if (confirm('Delete this?')) {
                    $.post(this.controller, {
                        destroyTag: true,
                        tag_id: tagId
                    }).done(function() {
                        $('#tag_manager').load(location + '?tab=9 #tag_manager');
                    });
                }
            }
        }


        ///////
        // TAGS
        const TagC = new Tag();

        // CREATE
        $(document).on('keypress', '.createTagInput', function(e) {
            if (e.which === 13) {
                const id = $(this).data('id');
                TagC.saveForTemplate(id);
            }
        });

        // listen keypress, add tag when it's enter
        $(document).on('keypress', '#createTagInput', function(e) {
            // Enter is ascii code 13
            if (e.which === 13) {
                TagC.save();
            }
        });
        // also add the tag if the focus is lost because it looks like it's not obvious for people to use the enter key
        $(document).on('blur', '#createTagInput', function() {
            TagC.save();
        });

        // AUTOCOMPLETE
        let cache = {};
        // # is for db or xp, . is for templates, should probably be homogeneized soon
        $('#createTagInput, .createTagInput').autocomplete({
            source: function(request, response) {
                let term = request.term;
                if (term in cache) {
                    response(cache[term]);
                    return;
                }
                $.getJSON("app/controllers/TagsController.php", request, function(data, status, xhr) {
                    cache[term] = data;
                    response(data);
                });
            }
        });

        // DEDUPLICATE
        $(document).on('click', '.tagDeduplicate', function() {
            TagC.deduplicate($(this).data('tag'));
        });

        // UNREFERENCE (remove link between tag and entity)
        $(document).on('click', '.tagUnreference', function() {
            TagC.unreference($(this).data('tagid'));
        });
        $(document).on('click', '.tagUnreferenceForTemplate', function() {
            TagC.unreferenceForTemplate($(this).data('tagid'), $(this).data('id'));
        });

        // DESTROY (from admin panel/tag manager)
        $(document).on('click', '.tagDestroy', function() {
            TagC.destroy($(this).data('tagid'));

        });
    });
}());
