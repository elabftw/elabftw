/**
 * edit.js - for the ?mode=edit
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
    'use strict';

    // UPLOAD FORM
    // config for dropzone, id is camelCased.
    Dropzone.options.elabftwDropzone = {
        // i18n message to user
        dictDefaultMessage: $('#entityInfos').data('upmsg'),
        maxFilesize: $('#entityInfos').data('maxsize'), // MB
        init: function() {

            // add additionnal parameters (id and type)
            this.on('sending', function(file, xhr, formData) {
                formData.append('upload', true);
                formData.append('id', $('#entityInfos').data('id'));
                formData.append('type', $('#entityInfos').data('type'));
            });

            // once it is done
            this.on('complete', function(answer) {
                // check the answer we get back from app/controllers/EntityController.php
                var json = JSON.parse(answer.xhr.responseText);
                if (json.res) {
                    notif(json.msg, 'ok');
                } else {
                    notif(json.msg, 'ko');
                }
                // reload the #filesdiv once the file is uploaded
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    $('#filesdiv').load('?mode=edit&id=' + $('#entityInfos').data('id') + ' #filesdiv', function() {
                        // make the comment zone editable (fix issue #54)
                        makeEditableFileComment($('#entityInfos').data('type'), $('#entityInfos').data('id'));
                    });
                }
            });
        }
    };

    $(document).ready(function() {
        // add the title in the page name (see #324)
        document.title = $('#entityInfos').data('title');

        var type = $('#entityInfos').data('type');
        var id = $('#entityInfos').data('id');
        var confirmText = $('#entityInfos').data('confirm');
        var controller = 'app/controllers/ExperimentsController.php';
        var location = 'experiments.php';
        if (type != 'experiments') {
            controller = 'app/controllers/DatabaseController.php';
            location = 'database.php';
        }

        var Entity = {
            destroy: function() {
                if (confirm(confirmText)) {
                    if (type === 'items') {
                        controller = 'app/controllers/EntityController.php';
                    }
                    $.post(controller, {
                        destroy: true,
                        id: id
                    }).done(function(data) {
                        if (data.res) {
                            notif(data.msg, 'ok');
                            window.location.replace(location);
                        } else {
                            notif(data.msg, 'ko');
                        }
                    });
                }
            }
        };

        var Tag = {
            controller: 'app/controllers/EntityController.php',
            // the argument here is the event (needed to detect which key is pressed)
            create: function(e) {
                var keynum;
                if (e.which) {
                    keynum = e.which;
                }
                if (keynum === 13) { // if the key that was pressed was Enter (ascii code 13)
                    // get tag
                    var tag = $('#createTagInput').val();
                    // POST request
                    $.post(this.controller, {
                        createTag: true,
                        tag: tag,
                        id: id,
                        type: type
                    }).done(function () {
                        $('#tags_div').load(location + '?mode=edit&id=' + id + ' #tags_div');
                        // clear input field
                        $('#createTagInput').val('');
                    });
                } // end if key is enter
            },
            destroy: function(tag) {
                if (confirm(confirmText)) {
                    $.post(this.controller, {
                        destroyTag: true,
                        type: type,
                        id: id,
                        tag_id: tag
                    }).done(function() {
                        $('#tags_div').load(location + '?mode=edit&id=' + id + ' #tags_div');
                    });
                }
            }
        };

        var Step = {
            controller: 'app/controllers/ExperimentsController.php',
            // the argument here is the event (needed to detect which key is pressed)
            create: function(e) {
                var keynum;
                if (e.which) {
                    keynum = e.which;
                }
                if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
                    // get body
                    var body = decodeURIComponent($('#stepinput').val());
                    // fix for user pressing enter with no input
                    if (body.length > 0) {
                        $.post(this.controller, {
                            createStep: true,
                            id: id,
                            body: body
                        })
                        // reload the step list
                        .done(function () {
                            $("#steps_div").load("experiments.php?mode=edit&id=" + id + " #steps_div");
                            // clear input field
                            $("#stepinput").val("");
                        });
                    } // end if input < 0
                } // end if key is enter
            },

            finish: function(stepId) {
                $.post(this.controller, {
                    finishStep: true,
                    id: id,
                    stepId: stepId
                })
                // reload the step list
                .done(function () {
                    $("#steps_div").load("experiments.php?mode=edit&id=" + id + " #steps_div");
                    // clear input field
                    $("#stepinput").val("");
                });
            },

            destroy: function(stepId) {
                if (confirm(confirmText)) {
                    $.post(this.controller, {
                        destroyStep: true,
                        id: id,
                        stepId: stepId
                    }).done(function (data) {
                        if (data.res) {
                            notif(data.msg, 'ok');
                            $("#steps_div").load("experiments.php?mode=edit&id=" + id + " #steps_div");
                        } else {
                            notif(data.msg, 'ko');
                        }
                    });
                }
            }
        };

        var Link = {
            controller: 'app/controllers/ExperimentsController.php',
            // the argument here is the event (needed to detect which key is pressed)
            create: function(e) {
                var keynum;
                if (e.which) {
                    keynum = e.which;
                }
                if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
                    // get link
                    var link = decodeURIComponent($('#linkinput').val());
                    // fix for user pressing enter with no input
                    if (link.length > 0) {
                        // parseint will get the id, and not the rest (in case there is number in title)
                        link = parseInt(link, 10);
                        if (!isNaN(link)) {
                            $.post(this.controller, {
                                createLink: true,
                                id: id,
                                linkId: link
                            })
                            // reload the link list
                            .done(function () {
                                $("#links_div").load("experiments.php?mode=edit&id=" + id + " #links_div");
                                // clear input field
                                $("#linkinput").val("");
                            });
                        } // end if input is bad
                    } // end if input < 0
                } // end if key is enter
            },
            destroy: function(link) {
                if (confirm(confirmText)) {
                    $.post(this.controller, {
                        destroyLink: true,
                        id: id,
                        linkId: link
                    }).done(function (data) {
                        if (data.res) {
                            notif(data.msg, 'ok');
                            $("#links_div").load("experiments.php?mode=edit&id=" + id + " #links_div");
                        } else {
                            notif(data.msg, 'ko');
                        }
                    });
                }
            }
        };

        $(document).on('click', '.entityDestroy', function() {
            Entity.destroy();
        });
        $(document).on('click', '.tagDestroy', function() {
            Tag.destroy($(this).data('tagid'));
        });
        $(document).on('click', '.stepDestroy', function() {
            Step.destroy($(this).data('stepid'));
        });
        $(document).on('click', '.linkDestroy', function() {
            Link.destroy($(this).data('linkid'));
        });
        // VISIBILITY SELECT
        $(document).on('change', '#visibility_select', function() {
            var visibility = $(this).val();
            $.post("app/controllers/ExperimentsController.php", {
                updateVisibility: true,
                id: id,
                visibility: visibility
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                } else {
                    notif(data.msg, 'ko');
                }
            });
        });

        // STATUS SELECT
        $(document).on('change', '#category_select', function() {
            var categoryId = $(this).val();
            $.post("app/controllers/EntityController.php", {
                updateCategory: true,
                id: id,
                type: type,
                categoryId : categoryId
            }).done(function(data) {
                if (data.res) {
                    notif(data.msg, 'ok');
                    // change the color of the item border
                    // we first remove any status class
                    $("#main_section").css('border', null);
                    // and we add our new border color
                    // first : get what is the color of the new status
                    var css = '6px solid #' + data.color;
                    $("#main_section").css('border-left', css);
                } else {
                    notif(data.msg, 'ko');
                }
            });
        });

        // AUTOSAVE
        var typingTimer;                // timer identifier
        var doneTypingInterval = 7000;  // time in ms between end of typing and save

        // user finished typing, save work
        function doneTyping () {
            quickSave(type, id);
        }
        // KEYBOARD SHORTCUTS
        key($('#shortcuts').data('create'), function(){
            location.href = controller + '?create=true';
        });
        key($('#shortcuts').data('submit'), function(){
            document.forms.main_form.submit();
        });

        // INSERT IMAGE AT CURSOR POSITION IN TEXT
        $(document).on('click', '.inserter',  function() {
            var imgLink = "<img src='app/download.php?f=" + $(this).data('link') + "' />";
            tinymce.activeEditor.execCommand('mceInsertContent', false, imgLink);
        });

        // SHOW/HIDE THE DOODLE CANVAS/CHEM EDITOR
        $(document).on('click', '.show-hide',  function() {
            var elem;

            if ($(this).data('type') === 'doodle') {
                elem = $('.canvasDiv');
            } else {
                elem = $('#chem_editor');
            }
            if (elem.is(':hidden')) {
                $(this).html('-');
                $(this).addClass('button-neutral');
            } else {
                $(this).html('+');
                $(this).removeClass('button-neutral');
            }
            elem.toggle();
        });

        // MAKE THE FILE COMMENT FIELD EDITABLE
        $('.thumbnail').on('mouseover', '.editable', function(){
            makeEditableFileComment($(this).data('type'), $(this).data('id'));
        });

        // CREATE TAG
        // listen keypress, add tag when it's enter
        $(document).on('keypress', '#createTagInput', function(e) {
            Tag.create(e);
        });
        // AUTOCOMPLETE THE TAGS
        var cache = {};
        $('#createTagInput').autocomplete({
            source: function(request, response) {
                var term = request.term;
                if (term in cache) {
                    response(cache[term]);
                    return;
                }
                $.getJSON("app/controllers/EntityController.php?tag=1&type=" + type + "&id=" + id, request, function(data, status, xhr) {
                    cache[term] = data;
                    response(data);
                });
            }
        });

        if (type === 'experiments') {
            // CREATE STEP
            $('#stepinput').keypress(function (e) {
                Step.create(e);
            });

            // STEP IS DONE
            $(document).on('click', 'input[type=checkbox]', function() {
                Step.finish($(this).data('stepid'));
            });


            // CREATE LINK
            // listen keypress, add link when it's enter
            $('#linkinput').keypress(function (e) {
                Link.create(e);
            });
            // AUTOCOMPLETE THE LINKS
            $( '#linkinput' ).autocomplete({
                source: function(request, response) {
                    var term = request.term;
                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }
                    $.getJSON("app/controllers/ExperimentsController.php", request, function(data, status, xhr) {
                        cache[term] = data;
                        response(data);
                    });
                }
            });
        }

        // DATEPICKER
        $( '#datepicker' ).datepicker({dateFormat: 'yymmdd'});
        // If the title is 'Untitled', clear it on focus
        $('#title_input').focus(function(){
            if ($(this).val() === $('#entityInfos').data('untitled')) {
                $('#title_input').val('');
            }
        });

        // STAR RATING
        var Star = {
            controller: 'app/controllers/DatabaseController.php',
            update: function(rating) {
                $.post(this.controller, {
                    rating: rating,
                    id: id
                }).done(function(data) {
                    if (data.res) {
                        notif(data.msg, 'ok');
                    } else {
                        notif(data.msg, 'ko');
                    }
                });
            }
        };
        $('input.star').rating();
        $('.star').click(function() {
            Star.update($(this).data('rating').current[0].innerText);
        });

        // EDITOR
        tinymce.init({
            mode : 'specific_textareas',
            editor_selector : 'mceditable',
            content_css : 'app/css/tinymce.css',
            plugins : 'table textcolor searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak mention codesample',
            pagebreak_separator: '<pagebreak>',
            toolbar1: 'undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | image link | save',
            removed_menuitems : 'newdocument',
            codesample_languages: [
                {text: 'Bash', value: 'bash'},
                {text: 'C', value: 'c'},
                {text: 'C++', value: 'cpp'},
                {text: 'CSS', value: 'css'},
                {text: 'Fortran', value: 'fortran'},
                {text: 'Go', value: 'go'},
                {text: 'HTML/XML', value: 'markup'},
                {text: 'Java', value: 'java'},
                {text: 'JavaScript', value: 'javascript'},
                {text: 'Julia', value: 'julia'},
                {text: 'Latex', value: 'latex'},
                {text: 'Makefile', value: 'makefile'},
                {text: 'Matlab', value: 'matlab'},
                {text: 'Perl', value: 'perl'},
                {text: 'Python', value: 'python'},
                {text: 'R', value: 'r'},
                {text: 'Ruby', value: 'ruby'}
                ],
            // save button :
            save_onsavecallback: function() {
                quickSave(type, id);
            },
            // keyboard shortcut to insert today's date at cursor in editor
            setup : function(editor) {
                editor.addShortcut('ctrl+shift+d', 'add date at cursor', function() { addDateOnCursor(); });
                editor.on('keydown', function(event) {
                    clearTimeout(typingTimer);
                });
                editor.on('keyup', function(event) {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(doneTyping, doneTypingInterval);
                });
            },
            mentions: {
                // # is for items + all experiments of the team, $ is for items + user's experiments
                delimiter: ['#', '$'],
                // get the source from json with get request
                source: function (query, process, delimiter) {
                    var url = "app/controllers/EntityController.php?mention=1&term=" + query;
                    if (delimiter === '#') {
                        $.getJSON(url, function(data, status, xhr) {
                            process(data);
                        });
                    }
                    if (delimiter === '$') {
                        url += "&userFilter=1";
                        $.getJSON(url, function(data, status, xhr) {
                            process(data);
                        });
                    }
                }
            },
            language : $('#entityInfos').data('lang'),
            style_formats_merge: true,
            style_formats: [
                {
                    title: 'Image Left',
                    selector: 'img',
                    styles: {
                        'float': 'left',
                        'margin': '0 10px 0 10px'
                    }
                 },
                 {
                     title: 'Image Right',
                     selector: 'img',
                     styles: {
                         'float': 'right',
                         'margin': '0 0 10px 10px'
                     }
                 }
            ]
        });

    });
}());
