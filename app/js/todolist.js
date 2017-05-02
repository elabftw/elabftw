// TODOLIST
var Todolist = {
    controller: 'app/controllers/TodolistController.php',
    // add a todo item
    create: function(e) {
        e.preventDefault();
        var body = $('#todo').val();
        var currentdate = new Date();
        var datetime = currentdate.getFullYear() + "-" +
            (currentdate.getMonth()+1)  + "-" +
            currentdate.getDate() + " " +
            currentdate.getHours() + ":" +
            currentdate.getMinutes() + ":" +
            currentdate.getSeconds();
        if (body !== "") {
            $.post(this.controller, {
                create: true,
                body: body
            }).done(function(data) {
                var json = JSON.parse(data);
                if (json.res) {
                    // add the todoitem
                    $('#todoItems-list').prepend("<li class='todoItem' id='todoItem_" +
                        json.id +
                        "'><a href='#' class='destroyTodoItem' data-id='" + json.id + "'>X</a><span style='font-size:60%;display:block;'>" +
                        datetime + "</span><span id='todoItem_" + json.id + "' class='editable'>" + body +
                        '</li>');
                    // make it editable right away
                    makeEditableTodoitem();
                    // and clear the input
                    $('#todo').val("");
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    },
    // remove one todo item
    destroy: function(id) {
        $.post(this.controller, {
            destroy: true,
            id: id
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                // hide item
                $('#todoItem_' + id).css('background', '#29AEB9');
                $('#todoItem_' + id).toggle('blind');
            } else {
                notif(json.msg, 'ko');
            }
        });
    },
    // clear all the items
    destroyAll: function() {
        $.post(this.controller, {
            destroyAll: true
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                // hide all items
                $('#todoItems-list').children().toggle('blind');
            } else {
                notif(json.msg, 'ko');
            }
        });
    },
    // show or hide the todolist
    toggle: function() {
        if ($('#todoList').css('display') === 'none') {
            $('#todoList').css('display', 'inline');
        } else {
            $('#todoList').css('display', 'none');
        }
    }
};

$('#todo-form').submit(function(e) {
    Todolist.create(e);
});
$(document).on('click', '.todoToggle', function() {
    Todolist.toggle();
});
$(document).on('click', '.todoDestroyAll', function() {
    Todolist.destroyAll();
});

$(document).on('click', '.destroyTodoItem', function() {
    Todolist.destroy($(this).data('id'));
});

$('#todoItems-list').on('mouseover', '.editable', function(){
    makeEditableTodoitem();
});

// TOGGLE VISIBILITY WITH A SHORTCUT
key($('#todoSc').data('toggle'), function(){
    Todolist.toggle();
});

// SORTABLE for TODOLIST items
$('#todoItems-list').sortable({
    // limit to vertical dragging
    axis : 'y',
    helper : 'clone',
    // do ajax request to update db with new order
    update: function(event, ui) {
        // send the orders as an array
        var ordering = $("#todoItems-list").sortable("toArray");

        $.post("app/controllers/TodolistController.php", {
            'updateOrdering': true,
            'ordering': ordering
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
            } else {
                notif(json.msg, 'ko');
            }
        });
    }
});

// EDIT todoitem
function makeEditableTodoitem() {
    $('.editable').editable(function(value, settings) {
        $.post('app/controllers/TodolistController.php', {
            update: true,
            body: value,
            id: $(this).attr('id')
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
            } else {
                notif(json.msg, 'ko');
            }
        });

        return(value);
        }, {
     tooltip : 'Click to edit',
     indicator : 'Saving...',
     name : 'fileComment',
     submit : 'Save',
     cancel : 'Cancel',
     style : 'display:inline'
    });
}
// END TODOLIST

