$(document).ready(function() {
    // Init single task data
    // taskid is defined in manage.php
    if (typeof(taskid) !== 'undefined') {
        init_task_data(taskid);
    }

    // if is redirected here from task show to task automatically
    rtask_id = get_url_param('rtaskid');
    if (rtask_id) {
        init_task_related_modal(rtask_id);
    }
    $('body').on('change', 'input[name="checklist-box"]', function() {
        var checked = $(this).prop('checked');
        if (checked == true) {
            val = 1;
        } else {
            val = 0;
        }
        var listid = $(this).parents('.checklist').data('checklist-id');
        $.get(admin_url + 'tasks/checkbox_action/' + listid + '/' + val);
        recalculate_checklist_items_progress();

    });

    $('body').on('blur', 'textarea[name="checklist-description"]', function() {
        var description = $(this).val();
        var listid = $(this).parents('.checklist').data('checklist-id');
        $.post(admin_url + 'tasks/update_checklist_item', {
            description: description,
            listid: listid
        });
    });
    // Assign task to staff member
    $('body').on('change', 'select[name="select-assignees"]', function() {
        $('body').append('<div class="dt-loader"></div>');
        var data = {};
        data.assignee = $('select[name="select-assignees"]').val();
        data.taskid = taskid;
        $.post(admin_url + 'tasks/add_task_assignees', data).success(function(response) {
            response = $.parseJSON(response);
            $('body').find('.dt-loader').remove();
            if (response.success == true) {
                if (!$('.task-related-modal-single').is(':visible')) {
                    init_task_data();
                } else {
                    init_task_related_modal(taskid);
                }
            }
        });
    });
    // Add follower to task
    $('body').on('change', 'select[name="select-followers"]', function() {
        var data = {};
        data.follower = $('select[name="select-followers"]').val();
        data.taskid = taskid;
        $('body').append('<div class="dt-loader"></div>');
        $.post(admin_url + 'tasks/add_task_followers', data).success(function(response) {
            response = $.parseJSON(response);
            $('body').find('.dt-loader').remove();
            if (response.success == true) {
                if (!$('.task-related-modal-single').is(':visible')) {
                    init_task_data();
                } else {
                    init_task_related_modal(taskid);
                }
            }
        });
    });
});

function recalculate_checklist_items_progress() {
    var total_finished = $('input[name="checklist-box"]:checked').length;
    var total_checklist_items = $('input[name="checklist-box"]').length;

    var percent = 0;

     if(total_checklist_items == 0){
        // remove the heading for checklist items
        $('body').find('.chk-heading').remove();
    }

    if (total_checklist_items > 2) {
        percent = (total_finished * 100) / total_checklist_items;
    } else {
        $('.task-progress-bar').parents('.progress').addClass('hide');
        return false;
    }

    $('.task-progress-bar').css('width', percent.toFixed(2) + '%');
    $('.task-progress-bar').text(percent.toFixed(2) + '%');
}

function delete_checklist_item(id, field) {
    $.get(admin_url + 'tasks/delete_checklist_item/' + id, function(response) {
        if (response.success == true) {
            $(field).parents('.checklist').remove();
            recalculate_checklist_items_progress();
        }
    }, 'json');
}

function add_task_checklist_item() {
    $.post(admin_url + 'tasks/add_checklist_item', {
        taskid: taskid
    }).success(function() {
        init_tasks_checklist_items(true);
    });
}

function init_tasks_checklist_items(is_new) {
    $.post(admin_url + 'tasks/init_checklist_items', {
        taskid: taskid
    }).success(function(data) {
        $('#checklist-items').html(data);
        if (typeof(is_new) != 'undefined') {
            $('body').find('.checklist textarea').eq(0).focus();
        }
        update_checklist_order();
    });
}

function reload_task_attachments() {
    $('#attachments').empty();
    $.get(admin_url + 'tasks/reload_task_attachments/' + taskid, function(response) {
        html = '';
        if (response.length > 0) {
            html += '<h4 class="bold">Attachments</h4>';
            html += '<ul class="list-unstyled">';
            $.each(response, function(i, obj) {
                html += '<li><i class="' + obj.mimeclass + '"></i><a href="' + site_url + 'download/file/taskattachment/' + obj.id + '" download>' + obj.file_name + '</a>';
                html += '<a href="#" class="pull-right text-danger" onclick="remove_task_attachment(this,' + obj.id + '); return false;"><i class="fa fa-trash-o"></i></a>'
                html += '</li>';
            });
            html += '</ul>'
        }

        $('#attachments').append(html);
    }, 'json');
}

function remove_task_attachment(link, id) {
    $.get(admin_url + 'tasks/remove_task_attachment/' + id, function(response) {
        if (response.success == true) {
            $(link).parents('li').remove();
        }
    }, 'json');
}
// Custom sory by tasks function
function tasks_sort_by(sort) {
    $('input[name="tasks_sort_by"]').val(sort);
    $('.table-tasks').DataTable().ajax.reload();
    $('input[name="tasks_sort_by"]').val('');
}
// Get all task comments and append
function get_task_comments() {
    $.get(admin_url + 'tasks/get_task_comments/' + taskid, function(response) {
        $('#task-comments').html(response);
    });
}

function add_task_comment() {
    var comment = $('#task_comment').val();
    if (comment == '') {
        return;
    }
    var data = {};
    data.content = comment;
    data.taskid = taskid;
    $('body').append('<div class="dt-loader"></div>');
    $.post(admin_url + 'tasks/add_task_comment', data).success(function(response) {
        response = $.parseJSON(response);
        $('body').find('.dt-loader').remove();
        if (response.success == true) {
            $('#task_comment').val('');
            get_task_comments();
        }
    });
}
// Single task data init
function init_task_data() {
    if (taskid == '') {
        return;
    }

    if (!$('body').hasClass('small-table')) {
        toggle_small_view('.table-tasks', '#task');
    }

    $.post(admin_url + 'tasks/get_task_data/', {
        taskid: taskid
    }).success(function(response) {
        $('#task').html(response);
        reload_task_attachments();
        reload_followers_select();
        reload_assignees_select();
        init_tasks_checklist_items();

    if (is_mobile()) {
          $('html, body').animate({
                scrollTop: $('#task').offset().top + 150
          }, 600);
     }
    });
}

function init_task_related_modal(taskid) {
    $.post(admin_url + 'tasks/get_tasks_relation_data_single/', {
        taskid: taskid
    }).success(function(response) {
        if (response == 'false') {
            alert_float('warning', 'Failed to load task');
            setTimeout(function() {
                window.location.href.reload();
            }, 4000);
        }
        $('.task-related-modal-single .modal-content .data').html(response);
        $('.task-related-modal-single').modal('show');
        taskid = taskid;
        reload_task_attachments();
        reload_followers_select();
        reload_assignees_select();
        init_tasks_checklist_items();
    });
}

function init_task(id) {
    taskid = id;
    init_task_data();
}
// Reload followers select field and removes the already added follower from the select field
function reload_followers_select() {
    $.get(admin_url + 'tasks/reload_followers_select/' + taskid, function(response) {
        $('select[name="select-followers"]').html(response);
        $('select[name="select-followers"]').selectpicker('refresh');
    });
}
// Reload assignes select field and removes the already added assignees from the select field
function reload_assignees_select() {
    $.get(admin_url + 'tasks/reload_assignees_select/' + taskid, function(response) {
        $('select[name="select-assignees"]').html(response);
        $('select[name="select-assignees"]').selectpicker('refresh');
    });
}
// Deletes task comment from database
function remove_task_comment(commentid) {
    $.get(admin_url + 'tasks/remove_comment/' + commentid, function(response) {
        if (response.success == true) {
            $('[data-commentid="' + commentid + '"]').remove();
        }
    }, 'json');
}
// Remove task assignee
function remove_assignee(id, taskid) {
    $.get(admin_url + 'tasks/remove_assignee/' + id + '/' + taskid, function(response) {
        if (response.success == true) {
            alert_float('success', response.message);
            if (!$('.task-related-modal-single').is(':visible')) {
                init_task_data();
            } else {
                init_task_related_modal(taskid);
            }
        }
    }, 'json');
}
// Remove task follower
function remove_follower(id, taskid) {
    $.get(admin_url + 'tasks/remove_follower/' + id + '/' + taskid, function(response) {
        if (response.success == true) {
            alert_float('success', response.message);
            if (!$('.task-related-modal-single').is(':visible')) {
                init_task_data();
            } else {
                init_task_related_modal(taskid);
            }
        }
    }, 'json');
}
// Marking task as complete
function mark_complete(task_id,invoker) {
    $('body').append('<div class="dt-loader"></div>');
    $.get(admin_url + 'tasks/mark_complete/' + task_id, function(response) {
        $('body').find('.dt-loader').remove();
        if (response.success == true) {
            $('.table-tasks').DataTable().ajax.reload();
            $('.table-rel-tasks').DataTable().ajax.reload();
            alert_float('success', response.message);
            if (!$('.task-related-modal-single').is(':visible')) {
                init_task_data(task_id);
            } else {
                init_task_related_modal(task_id);
            }

            if($('body').hasClass('home')){
                $(invoker).parents('.widget-task').remove();
            }
        }
    }, 'json');
}
// Marking task as complete
function unmark_complete(task_id) {
    $('body').append('<div class="dt-loader"></div>');
    $.get(admin_url + 'tasks/unmark_complete/' + task_id, function(response) {
        $('body').find('.dt-loader').remove();
        if (response.success == true) {
            $('.table-tasks').DataTable().ajax.reload();
            $('.table-rel-tasks').DataTable().ajax.reload();
            alert_float('success', response.message);
            if (!$('.task-related-modal-single').is(':visible')) {
                init_task_data(task_id);
            } else {
                init_task_related_modal(task_id);
            }
        }
    }, 'json');
}

function make_task_public(task_id) {
    $.get(admin_url + 'tasks/make_public/' + task_id, function(response) {
        if (response.success == true) {
            $('.table-tasks').DataTable().ajax.reload();
            $('.table-rel-tasks').DataTable().ajax.reload();
            if (!$('.task-related-modal-single').is(':visible')) {
                init_task_data(task_id);
            } else {
                init_task_related_modal(task_id);
            }
        }
    }, 'json');
}

// Deletes task from database
function delete_task() {
    $.get(admin_url + 'tasks/delete_task/' + taskid, function(response) {
        if (response.success == true) {
            $('.table-tasks').DataTable().ajax.reload();
            $('#task').html('');
            $('.tool-container').remove();
            alert_float('success', response.message);
            if ($('.task-related-modal-single').is(':visible')) {
                $('.task-related-modal-single').modal('hide');
                $('.table-rel-tasks').DataTable().ajax.reload();
            }
        }
    }, 'json');
}

function new_task(url){
    var _url = admin_url + 'tasks/task';
    if(typeof(url) != 'undefined'){
        _url = url;
    }
    $.get(_url,function(response){
        $('#_task').html(response)
        $('body').find('#_task_modal').modal('show');
    });
}

// Go to edit view
function edit_task() {
    $.get(admin_url + 'tasks/task/'+taskid,function(response){
        $('#_task').html(response)
        $('body').find('#_task_modal').modal('show');
    });
}

function new_task_from_relation(table) {
    var rel_id = $(table).data('new-rel-id');
    var rel_type = $(table).data('new-rel-type');
    var url = admin_url + 'tasks/task?rel_id=' + rel_id + '&rel_type=' + rel_type;
    new_task(url);
}

function task_form_handler(form){
    var data = $(form).serialize();
    var url = form.action;
    $.post(url, data).success(function(response) {
        response = $.parseJSON(response);
        if(response.success == true){
            alert_float('success',response.message);
        }
        $('#_task_modal').modal('hide');
         if(response.id){
            $('.table-rel-tasks').DataTable().ajax.reload();
            if ($('.table-tasks').length > 0) {
                 init_task(response.id);
                 $('.table-tasks').DataTable().ajax.reload();
            } else {
                init_task_related_modal(response.id);
            }
         } else {
            if (!$('.task-related-modal-single').is(':visible')) {
                if(response.success == true){
                     init_task(taskid);
                     $('.table-tasks').DataTable().ajax.reload();
                }
            } else {
                if(response.success == true){
                    $('.table-rel-tasks').DataTable().ajax.reload();
                    init_task_related_modal(taskid);
                }
            }
         }
    });
   return false;
}
