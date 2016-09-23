$(document).ready(function(){

   $.validator.setDefaults({
        highlight: function(element) {
            $(element).closest('.form-group').addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).closest('.form-group').removeClass('has-error');
        },
        errorElement: 'span',
        errorClass: 'text-danger',
        errorPlacement: function(error, element) {
            if(element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        }
    });

	$('.open-new-ticket-form').validate({
		rules: {
			subject:'required',
			department:'required',
			"attachments[]" : {
				extension: ticket_attachments_file_extension
			}
		}
	});

	$('#ticket-reply').validate({
		rules:{
			"attachments[]" : {
				extension: ticket_attachments_file_extension
			}
		}
	});

	$('.add_more_attachments').on('click',function(){
		var total_attachments = $('input[name="attachments[]"]').length;
		if(total_attachments >= maximum_allowed_ticket_attachments){
			return false;
		}
		var newattachment = $('.attachments').find('.attachment').eq(0).clone().appendTo('.attachments');
		$(newattachment).find('input').val('');
		$(newattachment).find('i').removeClass('fa-plus').addClass('fa-minus');
		$(newattachment).find('button').removeClass('add_more_attachments').addClass('remove_attachment').removeClass('btn-success').addClass('btn-danger')
	});

	$('body').on('click','.remove_attachment', function(){
		$(this).parents('.attachment').remove();
	});

	$('.single-ticket-add-reply').on('click',function(e){
		e.preventDefault()
		var reply_area = $('.single-ticket-reply-area');
		reply_area.slideToggle();
	});

	initDataTable('.tickets-table',window.location.href,'tickets','undefined','undefined',[4,'DESC']);
	initDataTable('.table-invoices',window.location.href,'invoices');
    initDataTable('.table-contracts',window.location.href,'contracts');
    initDataTable('.table-estimates',window.location.href,'estimates','undefined','undefined',[0,'DESC']);
	initDataTable('.table-proposals',window.location.href,'proposals','undefined','undefined',[3,'DESC']);

	$('.dismiss_announcement').on('click',function(){
		var announcementid = $(this).data('id');
		$.post(site_url + 'clients/dismiss_announcement',{announcementid:announcementid});
	});

    // User cant add more money then the invoice total remaining
    $('body.viewinvoice input[name="amount"]').on('keyup',function(){
        var original_total = $(this).data('total');
        var val = $(this).val();
        var form_group = $(this).parents('.form-group');
        if(val > original_total){
            form_group.addClass('has-error');
            if(form_group.find('p.text-danger').length == 0){
                form_group.append('<p class="text-danger">Maximum pay value passed</p>');
                $(this).parents('form').find('input[name="make_payment"]').attr('disabled',true);
            }
        } else {
            form_group.removeClass('has-error');
            form_group.find('p.text-danger').remove();
            $(this).parents('form').find('input[name="make_payment"]').attr('disabled',false);
        }
    });
});

function initDataTable(table,url,item_name,notsearchable,notsortable,defaultorder){

	if($(table).length == 0){
		return;
	}

    if (typeof(defaultorder) == 'undefined') {
        defaultorder = [0, 'ASC'];
    }

	var table = $(table).dataTable( {
        "language": {
            "emptyTable": dt_emptyTable.format(item_name),
            "info": dt_info.format(item_name),
            "infoEmpty": dt_infoEmpty.format(item_name),
            "infoFiltered": dt_infoFiltered.format(item_name),
            "lengthMenu": dt_lengthMenu.format(item_name),
            "loadingRecords": dt_loadingRecords,
            "processing": '<div class="dt-loader"></div>',
            "search": dt_search,
            "zeroRecords": dt_zeroRecords,
            "paginate": {
                "first": dt_paginate_first,
                "last": dt_paginate_last,
                "next": dt_paginate_next,
                "previous": dt_paginate_previous
            },
            "aria": {
                "sortAscending": ": " +dt_sortAscending,
                "sortDescending": ": " + dt_sortDescending
            }
        },
		"processing": true,
		"serverSide": true,
		'paginate':true,
		'searchDelay':300,
		'responsive':true,
		"bLengthChange": false,
		"pageLength": tables_pagination_limit,
        "order": [defaultorder],
		"columnDefs": [
		{ "searchable": false, "targets": notsearchable },
		{ "sortable": false, "targets": notsortable }
		],
		"ajax": {
			"url":url,
			"type":"POST",
		}
	} );
}
