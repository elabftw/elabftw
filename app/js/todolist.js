// TODOLIST
class Todolist {

    constructor(controller) {
        this.controller = controller;
    }

    formatLeadingZero(leadingZeros,number){
        var temp = ""+(Math.pow(10,leadingZeros)+number);
        return temp.substr(1,temp.length);
    }

    getCurrentTimestamp(){
        var currentdate = new Date();
        var datetime = currentdate.getFullYear() + "-" +
            this.formatLeadingZero(2,(currentdate.getMonth() + 1)) + "-" +
            currentdate.getDate() + " " +
            this.formatLeadingZero(2,currentdate.getHours()) + ":" +
            this.formatLeadingZero(2,currentdate.getMinutes()) + ":" +
            this.formatLeadingZero(2,currentdate.getSeconds());
        return datetime;
    }
    // add a todo item
    create(e) {
        e.preventDefault();
        var self=this;
        var body = $('#todo').val();
        var currentdate = new Date();
        var datetime = this.getCurrentTimestamp();

        if (body !== "") {
            $.post(this.controller, {
                create: true,
                body: body
            }).done(function (data) {
                var json = JSON.parse(data);
                if (json.res) {
                    // add the todoitem
                    $('#todoItems-list').prepend("<li class='ui-sortable-handle' id='todoItem_" +
                        json.id +
                        "'><a href='#' class='destroyTodoItem' data-id='" + json.id + "'>X</a><span style='font-size:60%;display:block;'>" +
                        datetime + "</span><span id='todoItem_" + json.id + "' class='editable'>" + body +
                        '</li>');
                    // make it editable right away
                    self.makeEditableTodoitem();
                    // and clear the input
                    $('#todo').val("");
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    }

    // remove one todo item
    destroy(id) {
        $.post(this.controller, {
            destroy: true,
            id: id
        }).done(function (data) {
            var json = JSON.parse(data);
            if (json.res) {
                // hide item
                $('#todoItem_' + id).css('background', '#29AEB9');
                $('#todoItem_' + id).toggle('blind');
            } else {
                notif(json.msg, 'ko');
            }
        });
    }

    // clear all the items
    destroyAll() {
        $.post(this.controller, {
            destroyAll: true
        }).done(function (data) {
            var json = JSON.parse(data);
            if (json.res) {
                // hide all items
                $('#todoItems-list').children().toggle('blind');
            } else {
                notif(json.msg, 'ko');
            }
        });
    };

    // show or hide the todolist
    toggle() {
        if ($('#todoList').css('display') === 'none') {
            $('#todoList').css('display', 'inline');
        } else {
            $('#todoList').css('display', 'none');
        }
    }


    // EDIT todoitem
    makeEditableTodoitem() {
        var self = this;
        $('.editable').editable(function (value, settings) {
            $.post(self.controller, {
                update: true,
                body: value,
                id: $(this).attr('id')
            }).done(function (data) {
                var json = JSON.parse(data);
                if (json.res) {
                    notif(json.msg, 'ok');
                } else {
                    notif(json.msg, 'ko');
                }
            });

            return (value);
        }, {
                tooltip: 'Click to edit',
                indicator: 'Saving...',
                name: 'fileComment',
                submit: 'Save',
                cancel: 'Cancel',
                style : 'display:inline'
            });
    }
    // END TODOLIST

}



var tl = new Todolist('app/controllers/TodolistController.php');

$('#todo-form').submit(function (e) {
    tl.create(e);
});
$(document).on('click', '.todoToggle', function () {
    tl.toggle();
});
$(document).on('click', '.todoDestroyAll', function () {
    tl.destroyAll();
});

$(document).on('click', '.destroyTodoItem', function () {
    tl.destroy($(this).data('id'));
});

$('#todoItems-list').on('mouseover', '.editable', function () {
    tl.makeEditableTodoitem();
});

// TOGGLE VISIBILITY WITH A SHORTCUT
key($('#todoSc').data('toggle'), function () {
    tl.toggle();
});

// SORTABLE for TODOLIST items
$('#todoItems-list').sortable({
    // limit to vertical dragging
    axis: 'y',
    helper: 'clone',
    // do ajax request to update db with new order
    update: function (event, ui) {
        // send the orders as an array
        var ordering = $("#todoItems-list").sortable("toArray");

        $.post(tl.controller, {
            'updateOrdering': true,
            'ordering': ordering
        }).done(function (data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
            } else {
                notif(json.msg, 'ko');
            }
        });
    }
});