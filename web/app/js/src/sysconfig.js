/**
 * sysconfig.js - for the sysadmin panel
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
                var orgid = $('#teamOrgid_' + id).val();
                $.post(this.controller, {
                    teamsUpdate: true,
                    teamsUpdateId : id,
                    teamsUpdateName : name,
                    teamsUpdateOrgid : orgid
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
                if (data.res) {
                    notif(data.msg, 'ok');
                    $('#teamsDiv').load('sysconfig.php #teamsDiv');
                } else {
                    notif(data.msg, 'ko');
                }
            }
        };

        $(document).on('keyup', '.teamNameInput', function() {
            document.getElementById('teamsUpdateButton_' + $(this).data('id')).disabled = false;
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
                if (data.res) {
                    notif(data.msg, 'ok');
                    $('#massSend').text('Sent!');
                } else {
                    notif(data.msg, 'ko');
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
                if (data.res) {
                    notif(data.msg, 'ok');
                    $('#massSend').text('Sent!');
                    document.getElementById('testemailButton').disabled = false;
                } else {
                    notif(data.msg, 'ko');
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
                if (data.res) {
                    notif(data.msg, 'ok');
                    $('#logsDiv').load('sysconfig.php #logsDiv');
                } else {
                    notif(data.msg, 'ko');
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
}());
