// Experiment Steps todolist
var self;

class ExperimentSteps extends Todolist {

    constructor() {
        // Call parent constructor
        super('app/controllers/ExperimentStepsController.php');
        this.experimentId = null;
        self = this;
    }

    /**
     * Open the todolist per experiment
     */
    open(experimentId) {
        //var self = this;
        this.experimentId = experimentId;
        console.log("Opening ExperimentSteps for experiment " + experimentId);
        $.post(self.controller, {
            read: true,
            experimentid: self.experimentId
        }).done(function (data) {
            console.log("read operation returned: " + data);
            var json = JSON.parse(data);
            $('#experimentStep-list').html("");
            if (json.res && json.todoItems != null) {
                for (var i = 0; i < json.todoItems.length; i++) {
                    var experimentStep = json.todoItems[i];
                    $('#experimentStep-list').prepend("<li class='todoItem' id='experimentStep_" +
                        experimentStep.id +
                        "'><a href='#' class='destroyTodoItem' data-id='" + experimentStep.id + "'>X</a><span style='font-size:60%;display:block;'>" +
                        experimentStep.creation_time + "</span><span id='experimentStep_" + experimentStep.id + "' class='editable'>" + experimentStep.body +
                        '</li>');
                }
            } else {
                notif(json.msg, 'ko');
            }

            self.show();
        });
    }

    /**
     * Overrides create function in todolist to provide the experiment's id
     */
    create(e) {
        e.preventDefault();
        var self = this;
        var body = $('#es-todo').val();

        var datetime = this.getCurrentTimestamp();
        if (body !== "") {
            console.log("Calling " + self.controller + " with body='" + body + "' and experimentid='" + self.experimentId + "'")
            $.post(self.controller, {
                create: true,
                body: body,
                experimentid: self.experimentId
            }).done(function (data) {
                var json = JSON.parse(data);
                console.log(data);
                if (json.res) {
                    // add the todoitem
                    $('#experimentStep-list').prepend("<li class='todoItem' id='experimentStep_" +
                        json.id +
                        "'><a href='#' class='destroyTodoItem' data-id='" + json.id + "'>X</a><span style='font-size:60%;display:block;'>" +
                        datetime + "</span><span id='experimentStep_" + json.id + "' class='editable'>" + body +
                        '</li>');
                    // make it editable right away
                    self.makeEditableTodoitem();
                    // and clear the input
                    $('#es-todo').val("");
                } else {
                    notif(json.msg, 'ko');
                }
            });
        }
    }

    /**
     * Ovverides destroy function in todolist to destroy one element per experiment
     */
    destroy(id) {
        $.post(this.controller, {
            destroy: true,
            id: id
        }).done(function (data) {
            var json = JSON.parse(data);
            if (json.res) {
                // hide item
                $('#experimentStep_' + id).css('background', '#29AEB9');
                $('#experimentStep_' + id).toggle('blind');
            } else {
                notif(json.msg, 'ko');
            }
        });
    }

    /**
    * Overrides toggle function in todolist to toggle the element per experiment
    */
    toggle() {
        if ($('#experimentSteps').css('display') === 'none') {
            $('#experimentSteps').css('display', 'inline');
        } else {
            $('#experimentSteps').css('display', 'none');
        }
    }

    /**
    * Overrides toggle function in todolist to toggle the element per experiment
    */
    show() {
        if ($('#experimentSteps').css('display') === 'none') {
            $('#experimentSteps').css('display', 'inline');
        }
    }

    /**
    * Overrides toggle function in todolist to toggle the element per experiment
    */
    hide() {
        if ($('#experimentSteps').css('display') !== 'none') {
            $('#experimentSteps').css('display', 'none');
        }
    }

    /**
    * Overrides destroyAll function in todolist to destroy the todos associated to the todolist
    */
    destroyAll() {
        $.post(this.controller, {
            destroyAll: true,
            experimentid: self.experimentId
        }).done(function (data) {
            var json = JSON.parse(data);
            if (json.res) {
                // hide all items
                $('#experimentStep-list').children().toggle('blind');
            } else {
                notif(json.msg, 'ko');
            }
        });
    }
}

var es = new ExperimentSteps();

$('#es-form').submit(function (e) {
    es.create(e);
});

$(document).on('click', '.todoHide', function () {
    es.hide();
});

$(document).on('click', '.experimentStepsDestroyAll', function () {
    es.destroyAll();
});

$(document).on('click', '.destroyTodoItem', function () {
    es.destroy($(this).data('id'));
});

$('#experimentStep-list').on('mouseover', '.editable', function () {
    es.makeEditableTodoitem();
});

// TOGGLE VISIBILITY ON CLICK
$(document).on('click', '.experimentstepicon', function () {
    es.toggle();
});

// SORTABLE for TODOLIST items
$('#experimentStep-list').sortable({
    // limit to vertical dragging
    axis: 'y',
    helper: 'clone',
    // do ajax request to update db with new order
    update: function (event, ui) {
        // send the orders as an array
        var ordering = $("#experimentStep-list").sortable("toArray");

        $.post(es.controller, {
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


// END TODOLIST
