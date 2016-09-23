$(document).ready(function() {

    // Add predefined reply click
    $('.add_predefined_reply').on('click', function(e) {
        e.preventDefault();
        var message = $(this).parents('li').find('.message').html();
        var data = Editor.getHTML();
        data += message;
        Editor.setHTML(data);
        $('#insert_predefined_reply').modal('hide');
    });

    $('.block-sender').on('click',function(){
        var sender = $(this).data('sender');
        if(sender == ''){
            alert('No Sender Found');
            return false;
        }

        $.post(admin_url + 'tickets/block_sender',{sender:sender}).success(function(){
            window.location.reload();
        });
    });

    // Admin ticket note add
    $('.add_note_ticket').on('click', function(e) {
        e.preventDefault();
        var note = $('textarea[name="note"]').val();
        var ticketid = $('input[name="ticketid"]').val();
        if (note == '') {
            return;
        }

        $.post(admin_url + 'tickets/add_ticket_note', {
            note: note,
            ticketid: ticketid
        }).success(function(response) {
            response = $.parseJSON(response);
            if (response.success == true) {
                window.location.reload();
            }
        });
    });

    // Update ticket settings from settings tab
    $('.save_changes_settings_single_ticket').on('click', function(e) {

        e.preventDefault();
        var data = {};
        var service = $('select[name="service"]').val();

        data.subject = $('input[name="subject"]').val();
        data.userid = $('select[name="userid"]').val();
        data.department = $('select[name="department"]').val();
        data.priority = $('select[name="priority"]').val();
        data.ticketid = $('input[name="ticketid"]').val();
        data.assigned = $('select[name="assigned"]').val();

        if (typeof(service) !== 'undefined') {
            data.service = service;
        }

        $.post(admin_url + 'tickets/update_single_ticket_settings', data).success(function(response) {
            response = $.parseJSON(response);
            if (response.success == true) {
                if (typeof(response.department_reassigned) !== 'undefined') {
                    window.location.href = admin_url + 'tickets/';
                } else {
                    window.location.reload();
                }
            }
        });
    });

    // Change ticket status without replying
    $('select[name="status_top"]').on('change', function() {
        var status = $(this).val();
        var ticketid = $('input[name="ticketid"]').val();
        $.get(admin_url + 'tickets/change_status_ajax/' + ticketid + '/' + status, function(response) {
            alert_float(response.alert, response.message);
        }, 'json');
    });

    // Select ticket user id
    $('body.ticket select[name="userid"]').on('change', function() {
        var clientid = $(this).val();
        if (clientid != '') {
            $.get(admin_url + 'clients/get_client_by_id_ajax/' + clientid, function(response) {
                $('input[name="to"]').val(response.firstname + ' ' + response.lastname);
                $('input[name="email"]').val(response.email);
            }, 'json');
        } else {
            $('input[name="to"]').val('');
            $('input[name="email"]').val('');
        }
    });

    // + button for adding more attachments
    $('.add_more_attachments').on('click', function() {
        var total_attachments = $('input[name="attachments[]"]').length;
        if (total_attachments >= maximum_allowed_ticket_attachments) {
            return false;
        }
        var newattachment = $('.attachments').find('.attachment').eq(0).clone().appendTo('.attachments');
        $(newattachment).find('input').val('');
        $(newattachment).find('i').removeClass('fa-plus').addClass('fa-minus');
        $(newattachment).find('button').removeClass('add_more_attachments').addClass('remove_attachment').removeClass('btn-success').addClass('btn-danger')
    });
    // Remove attachment
    $('body').on('click', '.remove_attachment', function() {
        $(this).parents('.attachment').remove();
    });
});

// Insert ticket knowledge base link modal
function insert_ticket_knowledgebase_link(id) {
    $.get(admin_url + 'knowledge_base/get_article_by_id_ajax/' + id, function(response) {
        var textarea = $('textarea[name="message"]');
        var data = Editor.getHTML();
        var link = site_url + 'knowledge_base/' + response.slug;
        data += link;
        Editor.setHTML(data);
        $('#insert_knowledge_base_link').modal('hide');
    }, 'json');
}
