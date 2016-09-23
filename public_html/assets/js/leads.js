var leads_update_req;

window.onbeforeunload = function() {
    if (leads_update_req) {
        return 'Request in progress....are you sure you want to continue? If you have a lot of leads takes time to update all leads orders. If this still shows after couple of minutes refresh your page.';
    }
};
// JS files used for leads
$(document).ready(function() {


    validate_lead_form();
    validate_lead_convert_to_client_form();
    // Init leads canban
    leads_canban();
    get_lead_attachments();

    Dropzone.options.leadAttachmentUpload = {
            addRemoveLinks: false,
            sending: function(file, xhr, formData) {
                formData.append("leadid", $('input[name="leadid"]').val());
            },
            complete: function(file) {
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    get_lead_attachments();
                }
            }
        }
        // Status color change
    $('body').on('click', '.leads-kan-ban .cpicker', function() {
        var color = $(this).data('color');
        var status_id = $(this).parents('.panel-heading-bg').data('status-id');
        $.post(admin_url + 'leads/change_status_color', {
            color: color,
            status_id: status_id
        });
    });

    $('body').on('change', 'input.include_leads_custom_fields', function() {
        var val = $(this).val();
        var fieldid = $(this).data('field-id');
        if (val == 2) {
            $('#merge_db_field_' + fieldid).removeClass('hide');
        } else {
            $('#merge_db_field_' + fieldid).addClass('hide');
        }
    });

    $('body').on('click', '.convert-client-close-modal', function() {
        $('#convert_lead_to_client_modal').modal('hide');
    });


    $('.kan-ban-lead-modal').on('show.bs.modal', function(event) {

        // Set default tab profiel
        window.location.hash = '#tab_lead_profile';
        var button = $(event.relatedTarget);
        // date picker i got in touch fix. Modal broken when change dont know why
        if (button.length == 0) {
            return;
        }
        var id = button.data('lead-id');
        if (typeof(id) == 'undefined') {
            return;
        }
        init_lead_modal_data(id);
    });

    $('.kan-ban-lead-modal').on('hidden.bs.modal', function(event) {
        // clear the hash
        if (!$('.kan-ban-lead-modal').is(':visible')) {
            history.pushState("", document.title, window.location.pathname + window.location.search);
        }
        // in case the convert to client modal is open to prevent the transparent modal screen
        $('#convert_lead_to_client_modal').modal('hide');
        $('.reminder-modal').modal('hide');
    });
    // Set hash on modal tab change
    $('body').on('click', '.kan-ban-lead-modal a[data-toggle="tab"]', function() {
        window.location.hash = this.hash;
    });

    // submit notes on lead modal do ajax not the regular request
    $('body').on('submit', '.kan-ban-lead-modal #lead-notes', function() {
        var form = $(this);
        var data = $(form).serialize();
        var serializeArray = $(form).serializeArray();
        $.post(form.attr('action'), data).success(function() {
            init_lead_modal_data(serializeArray[0].value);
        });
        return false;
    });

    // Add additional server params $_POST
    var LeadsServerParams = {
        "custom_view": "[name='custom_view']",
        "assigned": "[name='view_assigned']"
    }

    // Init the table
    var headers_leads = $('.table-leads').find('th');
    var not_sortable_leads = (headers_leads.length - 1);

    initDataTable('.table-leads', admin_url + 'leads?table_leads=true', 'leads', [not_sortable_leads], [not_sortable_leads], LeadsServerParams);
    $('select[name="view_assigned"]').on('change', function() {
        $('.table-leads').DataTable().ajax.reload();
    })
    $('body').on('click', '.convert_lead_to_client_modal', function() {
        if (!$(this).hasClass('disabled')) {
            $('#convert_lead_to_client_modal').modal('show');
        }
    });
    // When adding if lead is contacted today
    $('body').on('change', 'input[name="contacted_today"]', function() {
        var checked = $(this).prop('checked');
        if (checked == false) {
            $('.lead-select-date-contacted').removeClass('hide');
        } else {
            $('.lead-select-date-contacted').addClass('hide');
        }
    });

    $('body').on('change', 'input[name="contacted_indicator"]', function() {
        var val = $(this).val();
        if (val == 'yes') {
            $('.lead-select-date-contacted').removeClass('hide');
        } else {
            $('.lead-select-date-contacted').addClass('hide');
        }
    });
});

function validate_lead_form(formHandler) {
    _validate_form($('#lead_form'), {
        name: 'required',
        status: 'required',
        source: 'required',
        email: {
            email: true,
        }
    }, formHandler);
}

function validate_lead_convert_to_client_form() {
    _validate_form($('#lead_to_client_form'), {
        firstname: 'required',
        password: {
            required: {
                depends: function(element) {
                    var sent_set_password = $('input[name="send_set_password_email"]');
                    if (sent_set_password.prop('checked') == false) {
                        return true;
                    }
                }
            }
        },
        email: {
            required: true,
            email: true,
            remote: {
                url: site_url + "admin/misc/clients_email_exists",
                type: 'post',
                data: {
                    email: function() {
                        return $('#lead_to_client_form input[name="email"]').val();
                    },
                    userid: 'undefined'
                }
            }
        }

    });
}

function lead_kanban_profile_form_handler(form) {
    form = $(form);
    var data = form.serialize();
    var serializeArray = $(form).serializeArray();
    var leadid = $('.kan-ban-lead-modal').find('input[name="leadid"]').val();
    $.post(form.attr('action'), data).success(function() {
        init_lead_modal_data(leadid);
    });
    return false;
}

function init_lead_modal_data(id) {
    // clean the modal
    $('.kan-ban-lead-modal .modal-body').html('');
    $.get(admin_url + 'leads/get_lead_kan_ban_content/' + id, function(data) {
        $('.kan-ban-lead-modal .modal-body').html(data);
        get_lead_attachments(id);
    });
}

function leads_canban(search) {
    var search_query = '';

    if (typeof(search) !== 'undefined') {
        if (search != '') {
            search_query = '&search=' + search;
        }
    }

    if ($('#kan-ban').length == 0) {
        return;
    }

    $('body').append('<div class="dt-loader"></div>');
    $('#kan-ban').load(admin_url + 'leads/?canban=true' + search_query, function() {
        // Set the width of the kanban container
        $('body').find('div.dt-loader').remove();
        var kanbanCol = $('.kan-ban-content-wrapper');
        kanbanCol.css('max-height', (window.innerHeight - 275) + 'px');
        $('.kan-ban-content').css('min-height', (window.innerHeight - 275) + 'px');
        var kanbanColCount = parseInt(kanbanCol.length);
        $('.container-fluid').css('min-width', (kanbanColCount * 360) + 'px');
        $("#kan-ban").sortable({
            helper: 'clone',
            item: '.kan-ban-col',
            update: function(event, ui) {
                var order = [];
                var status = $('.kan-ban-col');
                var i = 0;
                $.each(status, function() {
                    order.push([$(this).data('col-status-id'), i]);
                    i++;
                });
                var data = {}
                data.order = order;
                $.post(admin_url + 'leads/update_status_order', data);
            }
        });


        $(".status").sortable({
            connectWith: ".leads-status",
            helper: 'clone',
            appendTo: '#kan-ban',
            placeholder: "ui-state-highlight-leads-kan-ban",
            revert: 'invalid',
            scroll: true,
            scrollSensitivity: 50,
            scrollSpeed: 70,
            start: function(event, ui) {
                //  clone = $(ui.item).clone();
                // Save the old status for activity log
                $(ui.item).data('old-status', $(ui.item.parent()[0]).data('lead-status-id'));
            },

            drag: function(event, ui) {
                var st = parseInt($(this).data("startingScrollTop"));
                ui.position.top -= $(this).parent().scrollTop() - st;
            },
            update: function(event, ui) {
                if (this === ui.item.parent()[0]) {
                    var data = {};
                    data.status = $(ui.item.parent()[0]).data('lead-status-id');
                    data.old_status = $(ui.item).data('old-status');
                    data.leadid = $(ui.item).data('lead-id');

                    var order = [];
                    var status = $(ui.item).parents('.leads-status').find('li')
                    var i = 1;
                    $.each(status, function() {
                        order.push([$(this).data('lead-id'), i]);
                        i++;
                    });
                    data.order = order;
                    setTimeout(function() {
                        leads_update_req = $.post(admin_url + 'leads/update_can_ban_lead_status', data).success(function(response) {
                            leads_update_req = null;
                            response = $.parseJSON(response);
                            if (response.success == true) {
                                $('.table-leads').DataTable().ajax.reload();
                                alert_float('success', response.message);
                            }
                        });
                    }, 200);

                }
            }

        }).disableSelection();
        $('.status').sortable({
            cancel: '.not-sortable'
        });
    });
}

function get_lead_attachments(leadid) {
    // from profile view
    if (typeof(leadid) == 'undefined') {
        var leadid = $('input[name="leadid"]').val();
    }
    if (typeof(leadid) == 'undefined' || leadid == '') {
        return;
    }
    $.get(admin_url + 'leads/get_lead_attachments/' + leadid, function(response) {
        $('#lead_attachments').html(response);
    });
}

function delete_lead_attachment(wrapper, id) {
    $.get(admin_url + 'leads/delete_attachment/' + id, function(response) {
        if (response.success == true) {
            $(wrapper).parents('.lead-attachment-wrapper').remove();
        }
    }, 'json');
}

function delete_lead_note(wrapper, id) {
    $.get(admin_url + 'leads/delete_lead_note/' + id, function(response) {
        if (response.success == true) {
            $(wrapper).parents('.lead-note').remove();
        }
    }, 'json');
}
