$(document).ready(function() {
    // TEAMS
    var Teams = {
        controller: 'app/controllers/SysconfigController.php',
        create: function() {
            document.getElementById('teamsCreateButton').disabled = true;
            var name = $('#teamsName').val();
            $.post(this.controller, {
                teamsCreate: true,
                teamsName: name
            }).done(function(data) {
                Teams.destructor(data);
            });
        },
        update: function(id) {
            document.getElementById('teamsUpdateButton_' + id).disabled = true;
            var name = $('#teamName_' + id).val();
            $.post(this.controller, {
                teamsUpdate: true,
                teamsUpdateId : id,
                teamsUpdateName : name
            }).done(function(data) {
                Teams.destructor(data);
            });
        },
        destroy: function(id) {
            document.getElementById('teamsDestroyButton_' + id).disabled = true;
            $.post(this.controller, {
                teamsDestroy: true,
                teamsDestroyId: id
            }).done(function(data) {
                Teams.destructor(data);
            });
        },
        destructor: function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                $('#teamsDiv').load('sysconfig.php #teamsDiv');
            } else {
                notif(json.msg, 'ko');
            }
        }
    };

    $(document).on('keyup', '.teamNameInput', function() {
        button = document.getElementById('teamsUpdateButton_' + $(this).data('id')).disabled = false;
    });

    $(document).on('click', '#teamsCreateButton', function() {
        Teams.create();
    });
    $(document).on('click', '.teamsUpdateButton', function() {
        Teams.update($(this).data('id'));
    });
    $(document).on('click', '.teamsDestroyButton', function() {
        Teams.destroy($(this).data('id'));
    });
    $(document).on('click', '.teamsArchiveButton', function() {
        notif('Feature not yet implemented :)', 'ok');
    });

    // PROMOTE SYSADMIN
    $(document).on('click', '#promoteSysadminButton', function() {
        document.getElementById('promoteSysadminButton').disabled = true;
        var email = $('#promoteSysadmin').val();
        $.post('app/controllers/SysconfigController.php', {
            promoteSysadmin: true,
            email: email
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                $('#teamsDiv').load('sysconfig.php #teamsDiv');
            } else {
                notif(json.msg, 'ko');
            }
        });
    });

    // MAIL METHOD in a function because is also called in document ready
    function toggleMailMethod(method) {
        switch (method) {
            case 'sendmail':
                $('#smtp_config').hide();
                $('#sendmail_config').show();
                break;
            case 'smtp':
                $('#smtp_config').show();
                $('#sendmail_config').hide();
                break;
            case 'php':
                $('#smtp_config').hide();
                $('#sendmail_config').hide();
                $('#general_mail_config').show();
                break;
            default:
                $('#smtp_config').hide();
                $('#sendmail_config').hide();
                $('#general_mail_config').hide();
        }
    }
    $(document).on('change', '#selectMailMethod', function() {
        toggleMailMethod($(this).val());
    });

    // MASS MAIL
    $(document).on('click', '#massSend', function() {
        $('#massSend').prop('disabled', true);
        $('#massSend').text('Sending…');
        $.post("app/controllers/SysconfigController.php", {
            massEmail: true,
            subject: $('#massSubject').val(),
            body: $('#massBody').val()
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
            } else {
                notif(json.msg, 'ko');
                $('#massSend').prop('disabled', false);
                $('#massSend').css('background-color', '#e6614c');
                $('#massSend').text('Error');
            }
        });
    });

    // TEST EMAIL
    $(document).on('click', '#testemailButton', function() {
        var email = $('#testemailEmail').val();
        document.getElementById('testemailButton').disabled = true;
        $('#testemailButton').text('Sending…');
        $.post('app/controllers/SysconfigController.php', {
            testemailSend: true,
            testemailEmail: email
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                document.getElementById('testemailButton').disabled = false;
            } else {
                notif(json.msg, 'ko');
                $('#testemailButton').text('Error');
                $('#testemailButton').css('background-color', '#e6614c');
            }
        });
    });

    // LOGS
    $(document).on('click', '#logsDestroyButton', function() {
        document.getElementById('logsDestroyButton').disabled = true;
        $.post('app/controllers/SysconfigController.php', {
            logsDestroy: true
        }).done(function(data) {
            var json = JSON.parse(data);
            if (json.res) {
                notif(json.msg, 'ok');
                $('#logsDiv').load('sysconfig.php #logsDiv');
            } else {
                notif(json.msg, 'ko');
            }
        });
    });

    $(document).on('click', '#editSmtpPassword', function() {
        $('#hidden_smtp_password').toggle();
    });

    // we need to add this otherwise the button will stay disabled with the browser's cache (Firefox)
    var input_list = document.getElementsByTagName('input');
    for (var i=0; i < input_list.length; i++) {
        var input = input_list[i];
        input.disabled = false;
    }
    // honor already saved mail_method setting and hide unused options accordingly
    toggleMailMethod($('#selectMailMethod').val());
});
