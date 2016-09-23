$(document).ready(function() {
    init_invoices_total();
    // Added in version 1.0.1
    // Recalculate price for all cases
    $('#invoice-form,#estimate-form').submit(function() {
        calculate_total();
        reorder_items();
        return true;
    });

    init_items_sortable();

    var invoiceUploadFile = Dropzone.options.invoiceUpload = {
        createImageThumbnails: false,
        sending: function(file, xhr, formData) {
            formData.append("invoiceid", $('body').find('input[name="_at_invoice_id"]').val());
        },
        complete: function(files) {
            if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                init_invoice($('body').find('input[name="_at_invoice_id"]').val());
            }
        },
        success: function(file, response) {
            response = $.parseJSON(response);
            var data = '';
            if (response.success == true) {
                data += '<div class="display-block invoice-attachment-wrapper invoice-attach-dropzone-preview">';
                data += '<div class="col-md-10">';
                data += '<div class="pull-left"><i class="attachment-icon-preview fa fa-file-o"></i></div>'
                data += '<a href="' + site_url + 'download/file/invoice_attachment/' + response.attachment_id + '">' + response.file_name + '</a>';
                data += '<p class="text-muted">' + response.filetype + '</p>';
                data += '</div>';
                data += '<div class="col-md-2 text-right">';
                data += '<a href="#" class="text-danger" onclick="delete_invoice_attachment(this,' + response.attachment_id + '); return false;"><i class="fa fa-trash-o"></i></a>';
                data += '</div>';
                data += '<div class="clearfix"></div><hr/>';
                data += '</div>';
                $('#invoice_uploaded_files_preview').append(data);
            }
        }
    };


    $('body.invoice').find('input[name="date"]').datepicker().on('change',function(){
       var date = $(this).val();
        if (date != '') {
            $.post(admin_url + 'invoices/calc_due_date', {
                date: date
            }).success(function(date) {
                $('input[name="duedate"]').val(date);
            });
        } else {
            $('input[name="duedate"]').val('');
        }
    });

    // remove the preview in the modal after hide
    $('#invoice_attach').on('hidden.bs.modal', function(e) {
        $('.dz-preview').remove();
        $('.invoice-attach-dropzone-preview').remove();
    });

    // Show send to email invoice modal
    $('body').on('click', '.invoice-send-to-client', function(e) {
        e.preventDefault();
        $('#invoice_send_to_client_modal').modal('show');
    });

    // Show send to email estimate modal
    $('body').on('click', '.estimate-send-to-client', function(e) {
        e.preventDefault();
        $('#estimate_send_to_client_modal').modal('show');
    });

   init_items_search();

    $('body').on('change', '#include_shipping', function() {
        if ($(this).prop('checked') == true) {
            $('#shipping_details').removeClass('hide');
        } else {
            $('#shipping_details').addClass('hide');
        }
    });

    $('body').on('click', '.save-shipping-billing', function(e) {
        init_billing_and_shipping_details();
    });
    // Since Version 1.0.1
    // When user record payment check if is online mode
    $('body').on('change', 'select[name="paymentmode"]', function() {
        var method = $(this).val();
        if (!$.isNumeric(method)) {
            $('.do_not_redirect').removeClass('hide');
        } else {
            $('.do_not_redirect').addClass('hide');
        }
    });

    $('body').on('change','select[name="clientid"]', function() {

        var val = $(this).val();
        clear_billing_and_shipping_details();
        if (val == '') {
            return false;
        }
        $.get(admin_url + 'clients/get_customer_billing_and_shipping_details/' + val, function(response) {
            $('input[name="billing_street"]').val(response[0]['billing_street']);
            $('input[name="billing_city"]').val(response[0]['billing_city']);
            $('input[name="billing_state"]').val(response[0]['billing_state']);
            $('input[name="billing_zip"]').val(response[0]['billing_zip']);
            $('select[name="billing_country"]').selectpicker('val', response[0]['billing_country']);
            if (!empty(response[0]['shipping_street'])) {
                $('input[name="include_shipping"]').prop("checked", true);
                $('input[name="include_shipping"]').change();
            }
            $('input[name="shipping_street"]').val(response[0]['shipping_street']);
            $('input[name="shipping_city"]').val(response[0]['shipping_city']);
            $('input[name="shipping_state"]').val(response[0]['shipping_state']);
            $('input[name="shipping_zip"]').val(response[0]['shipping_zip']);
            $('select[name="shipping_country"]').selectpicker('val', response[0]['shipping_country']);
            init_billing_and_shipping_details();
            $.get(admin_url + 'clients/get_customer_default_currency/' + val, function(client_currency) {
                client_currency = parseInt(client_currency);
                if (client_currency != 0) {
                    $('select[name=currency]').val(client_currency);
                    $('select[name="currency"]').selectpicker('refresh');
                    $('select[name="currency"]').change();
                }
            }, 'json');
        }, 'json');

    });

    $('body').on('click','#get_shipping_from_customer_profile', function(e) {
        e.preventDefault();
        var include_shipping = $('#include_shipping');
        if (include_shipping.prop('checked') == false) {
            include_shipping.prop('checked', true);
            $('#shipping_details').removeClass('hide');
        }

        var clientid = $('select[name="clientid"]').val();
        if (clientid == '') {
            return;
        }
        $.get(admin_url + 'clients/get_customer_billing_and_shipping_details/' + clientid, function(response) {
            $('input[name="shipping_street"]').val(response[0]['shipping_street']);
            $('input[name="shipping_city"]').val(response[0]['shipping_city']);
            $('input[name="shipping_state"]').val(response[0]['shipping_state']);
            $('input[name="shipping_zip"]').val(response[0]['shipping_zip']);
            $('select[name="shipping_country"]').selectpicker('val', response[0]['shipping_country']);
        }, 'json');
    });

    // On change currency recalculate price and change symbol
    $('body').on('change','select[name="currency"]', function() {
        init_currency_symbol($(this).val());
    });
    // Custom adjustment
    $('body').on('change','input[name="adjustment"]', function() {
        calculate_total();
    });

    $('body').on('change','select[name="discount_type"]' ,function() {
        var discount_type = $(this).val();
        if (discount_type == '') {
            $('input[name="discount_percent"]').val('');
        }
        calculate_total();
    });

    $('body').on('change', 'select.tax', function() {
        calculate_total();
    });

    // If tab is triggered so populate automatically
    $("input[name='description']").keydown(function(e) {
        if (e.which == 9)
            var first_item = $('body').find('li.item-auto-search').eq(0);
        if (typeof(first_item) !== 'undefined') {
            if (first_item.length == 1) {
                first_item.click();
            }
        }
    });

    $('body').on('change','input[name="discount_percent"]', function() {
        var discount_type = $('select[name="discount_type"]').val();
        if (discount_type == '') {
            alert('select discount type');
            $(this).val('');
            return false;
        }
        if ($(this).valid() == true) {
            calculate_total();
        }
    });

    $('body').on('change','select[name="item_select"]', function() {
        var itemid = $(this).selectpicker('val');
        if (itemid != '') {
            add_item_to_preview(itemid);
        }
    });

    // Used for formatting money
    accounting.settings.currency.decimal = decimal_separator;
    accounting.settings.currency.thousand = thousand_separator;
    // Used for numbers
    accounting.settings.number.thousand = thousand_separator;
    accounting.settings.number.decimal = decimal_separator;
    accounting.settings.number.precision = 2;
    calculate_total();
});
// Init single invoice
function init_invoice(id) {
    var _invoiceid = $('input[name="invoiceid"]').val();
    // Check if invoiec passed from url
    if (_invoiceid != '') {
        id = _invoiceid;
        // Clear the current invoice value in case user click on the left sidebar invoices
        $('input[name="invoiceid"]').val('');
    } else {
        if (typeof(id) == 'undefined' || id == '') {
            return;
        }
    }
    if (!$('body').hasClass('small-table')) {
        toggle_small_view('.tbl-invoices', '#invoice');
    }
    $('#invoice').load(admin_url + 'invoices/get_invoice_data_ajax/' + id);

     if (is_mobile()) {
          $('html, body').animate({
                scrollTop: $('#invoice').offset().top + 150
          }, 600);
     }
}
// Init single Estimate
function init_estimate(id) {
    var _estimateid = $('input[name="estimateid"]').val();

    // Check if estimate passed from url
    if (_estimateid != '') {
        id = _estimateid;
        // Clear the current estimate value in case user click on the left sidebar invoices
        $('input[name="estimateid"]').val('');
    } else {
        if (typeof(id) == 'undefined' || id == '') {
            return;
        }
    }
    if (!$('body').hasClass('small-table')) {
        toggle_small_view('.tbl-estimates', '#estimate');
    }
    $('#estimate').load(admin_url + 'estimates/get_estimate_data_ajax/' + id);

    if (is_mobile()) {
          $('html, body').animate({
                scrollTop: $('#estimate').offset().top + 150
          }, 600);
     }
}

// Init single Estimate
function init_proposal(id) {
    var _proposal_id = $('input[name="proposal_id"]').val();
    // Check if invoiec passed from url
    if (_proposal_id != '') {
        id = _proposal_id;
        // Clear the current proposal value in case user click on the left sidebar invoices
        $('input[name="proposal_id"]').val('');
    } else {
        if (typeof(id) == 'undefined' || id == '') {
            return;
        }
    }
    if (!$('body').hasClass('small-table')) {
        toggle_small_view('.tbl-proposals', '#proposal');
    }

    if (typeof(editor) != 'undefined' && editor.isEditing()) {
        editor.destroy();
    }

    $('#proposal').load(admin_url + 'proposals/get_proposal_data_ajax/' + id);

    if (is_mobile()) {
          $('html, body').animate({
                scrollTop: $('#proposal').offset().top + 150
          }, 600);
     }
}

function clear_billing_and_shipping_details() {
    $('input[name="billing_street"]').val('');
    $('input[name="billing_city"]').val('');
    $('input[name="billing_state"]').val('');
    $('input[name="billing_zip"]').val('');
    $('select[name="billing_country"]').selectpicker('val', '');
    $('input[name="include_shipping"]').prop("checked", false);
    $('input[name="include_shipping"]').change();
    $('input[name="shipping_street"]').val('');
    $('input[name="shipping_city"]').val('');
    $('input[name="shipping_state"]').val('');
    $('input[name="shipping_zip"]').val('');
    $('select[name="shipping_country"]').selectpicker('val', '');
    init_billing_and_shipping_details();
}

function init_billing_and_shipping_details() {

    var billing_street = $('input[name="billing_street"]').val();
    billing_street = (billing_street != '' ? billing_street : '--');

    var billing_city = $('input[name="billing_city"]').val();
    billing_city = (billing_city != '' ? billing_city : '--');

    var billing_state = $('input[name="billing_state"]').val();
    billing_state = (billing_state != '' ? billing_state : '--');

    var billing_zip = $('input[name="billing_zip"]').val();
    billing_zip = (billing_zip != '' ? billing_zip : '--');

    var billing_country = $("#billing_country option:selected").data('subtext');
    if (typeof(billing_country) == 'undefined') {
        billing_country = '--';
    }

    var include_shipping = $('input[name="include_shipping"]').prop('checked');

    var shipping_street = '';
    if (include_shipping) {
        shipping_street = $('input[name="shipping_street"]').val();
    }
    shipping_street = (shipping_street != '' ? shipping_street : '--');

    var shipping_city = '';
    if (include_shipping) {
        shipping_city = $('input[name="shipping_city"]').val();
    }
    shipping_city = (shipping_city != '' ? shipping_city : '--');

    var shipping_state = '';
    if (include_shipping) {
        shipping_state = $('input[name="shipping_state"]').val();
    }
    shipping_state = (shipping_state != '' ? shipping_state : '--');

    var shipping_zip = '';
    if (include_shipping) {
        shipping_zip = $('input[name="shipping_zip"]').val();
    }
    shipping_zip = (shipping_zip != '' ? shipping_zip : '--');

    var shipping_country = '';
    if (include_shipping) {
        var shipping_country = $("#shipping_country option:selected").data('subtext');
    }
    if (typeof(shipping_country) == 'undefined' || shipping_country == '') {
        shipping_country = '--';
    }

    $('.billing_street').text(billing_street);
    $('.billing_city').text(billing_city);
    $('.billing_state').text(billing_state);
    $('.billing_zip').text(billing_zip);
    $('.billing_country').text(billing_country);

    $('.shipping_street').text(shipping_street);
    $('.shipping_city').text(shipping_city);
    $('.shipping_state').text(shipping_state);
    $('.shipping_zip').text(shipping_zip);
    $('.shipping_country').text(shipping_country);
    $('#billing_and_shipping_details').modal('hide');
}
// Record payment function
function record_payment(id) {
    if (typeof(id) == 'undefined' || id == '') {
        return;
    }
    $('#invoice').load(admin_url + 'invoices/record_invoice_payment_ajax/' + id);
}

function add_item_to_preview(itemid) {
    $.get(admin_url + 'invoice_items/get_item_by_id/' + itemid, function(response) {
        if (!response.taxid) {
            response.taxid = 0;
        }
        $('input[name="description"]').val(response.description);
        $('input[name="long_description"]').val(response.long_description);
        $('input[name="quantity"]').val(1);
        $('.main select.tax').selectpicker('val', response.taxid);
        $('input[name="rate"]').val(response.rate);
        $('.item-search .dropdown-menu').removeClass('display-block');
    }, 'json');
}

function clear_main_values() {
    $('input[name="description"]').val('');
    $('input[name="long_description"]').val('');
    $('input[name="quantity"]').val('');
    $('.main select.tax').selectpicker('val', '0');
    $('input[name="rate"]').val('');
    $('input[name="item-search"]').val('');
    $('.item-search .dropdown-menu').html('');
}

function add_item_to_table(data, itemid) {
    if (typeof(data) == 'undefined' || data == 'undefined') {
        data = get_main_values();
    }
    var table_row = '';
    var item_key = $('body').find('tbody .item').length + 1;

    table_row += '<tr class="sortable item">';
    table_row += '<td class="dragger">';

    if (data.qty == '' || data.qty == 0) {
        data.qty = 1;
    }

    if (data.rate == '' || isNaN(data.rate)) {
        data.rate = 0;
    }

    var amount = data.rate * data.qty;
    amount = accounting.formatNumber(amount);
    var tax_name = 'newitems[' + item_key + '][taxid]';
    $.when(get_taxes_dropdown_template(tax_name, data.taxid)).then(function(tax_dropdown) {
        // order input
        table_row += '<input type="hidden" class="order" name="newitems[' + item_key + '][order]">';
        table_row += '</td>';
        table_row += '<td class="bold description"><input type="text" name="newitems[' + item_key + '][description]" class="form-control input-transparent" value="' + data.description + '"></td>';
        table_row += '<td><textarea name="newitems[' + item_key + '][long_description]" class="form-control input-transparent">' + data.long_description + '</textarea></td>';
        table_row += '<td><input type="number" min="0" onblur="calculate_total();" onchange="calculate_total();" data-quantity name="newitems[' + item_key + '][qty]" value="' + data.qty + '" class="form-control input-transparent"></td>';
        table_row += '<td class="rate"><input type="text" data-toggle="tooltip" title="' + item_field_not_formated + '" onblur="calculate_total();" onchange="calculate_total();" name="newitems[' + item_key + '][rate]" value="' + data.rate + '" class="form-control input-transparent"></td>';
        table_row += '<td class="taxrate">' + tax_dropdown + '</td>';
        table_row += '<td class="amount">' + amount + '</td>';
        table_row += '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' + itemid + '); return false;"><i class="fa fa-trash"></i></a></td>';
        table_row += '</tr>';
        $.when($('table.items tbody').append(table_row)).then(calculate_total);
        init_selectpicker();
        clear_main_values();
        reorder_items();
        return true;
    });
    return false;
}

function get_taxes_dropdown_template(name, taxid) {
    return $.post(admin_url + 'misc/get_taxes_dropdown_template/', {
        name: name,
        taxid: taxid
    });
}

function fixHelperTableHelperSortable(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
}
function init_items_search(){
     // Items search
    $.ajax({
        type: "POST",
        url: admin_url + 'invoice_items/get_all_items_ajax',
        dataType: 'json',
        success: function(response) {
            $('#autocomplete_main').autocomplete({
                source: response
            }).autocomplete('instance')._renderItem = function(ul, item) {
                return $("<li class='item-auto-search' onclick='add_item_to_preview(" + item.itemid + "); return false;'>")
                    .append("<a href='#' class='bold'>" + item.label + "<br><span class='text-muted'>" + item.long_description + "</span></a>")
                    .appendTo(ul);
            }
        }
    });
}
function init_items_sortable(){
        $("body").find('.items tbody').sortable({
        helper: fixHelperTableHelperSortable,
        handle: '.dragger',
        placeholder: 'ui-placeholder',
        itemPath: '> tbody',
        itemSelector: 'tr.sortable',
        items: "tr.sortable",
        update: function() {
            reorder_items();
        },
        sort: function(event, ui) {
            // Firefox fixer when dragging
            var $target = $(event.target);
            if (!/html|body/i.test($target.offsetParent()[0].tagName)) {
                var top = event.pageY - $target.offsetParent().offset().top - (ui.helper.outerHeight(true) / 2);
                ui.helper.css({
                    'top': top + 'px'
                });
            }
        }
    });
}

function reorder_items() {
    var rows = $('.table.table-main-invoice-edit tbody tr.item,.table.table-main-estimate-edit tbody tr.item');
    var i = 1;
    $.each(rows, function() {
        $(this).find('input.order').val(i);
        i++;
    });
}

function get_main_values() {
    var response = {};
    response.description = $('input[name="description"]').val();
    response.long_description = $('input[name="long_description"]').val();
    response.qty = $('input[name="quantity"]').val();
    response.taxid = $('.main select.tax').selectpicker('val');
    response.rate = $('input[name="rate"]').val();
    return response;
}
// Calculate invoice total
function calculate_total() {

    var taxes = {};
    var subtotal = 0;
    var rows = $('.table.table-main-invoice-edit tbody tr.item,.table.table-main-estimate-edit tbody tr.item');
    var discount_area = $('tr#discount_percent');
    var discount_percent = $('input[name="discount_percent"]').val();
    var total_discount_calculated = 0;

    var discount_type = $('select[name="discount_type"]').val();
    $('.tax-area').remove();
    $.each(rows, function() {
        var quantity = $(this).find('[data-quantity]').val();
        if (quantity == '') {
            quantity = 1;
            $(this).find('[data-quantity]').val(1);
        }
        var _amount = parseFloat($(this).find('td.rate input').val()) * quantity;
        $(this).find('td.amount').html(accounting.formatNumber(_amount));
        subtotal += _amount;

        var taxid = $(this).find('select.tax').selectpicker('val');
        var taxname = $(this).find('select.tax [value="' + taxid + '"]').data('taxname');
        var taxrate = $(this).find('select.tax [value="' + taxid + '"]').data('taxrate');

        var calculated_tax = (_amount / 100 * taxrate);
        if (!taxes.hasOwnProperty(taxid)) {
            if (taxrate != 0) {
                $(discount_area).after('<tr class="tax-area"><td>' + taxname + '(' + taxrate + '%)</td><td id="tax_id_' + taxid + '"></td></tr>');
                var calculated_tax = (_amount / 100 * taxrate);
                taxes[taxid] = calculated_tax;
            }
        } else {
            // Increment total from this tax
            var __calculated_tax = taxes[taxid];
            __calculated_tax += calculated_tax;
            taxes[taxid] = __calculated_tax;
        }
    });

    if (discount_percent != '' && discount_type == 'before_tax') {
        // Calculate the discount total
        total_discount_calculated = (subtotal * discount_percent) / 100;
    }
    var total = 0;
    $.each(taxes, function(taxid, total_tax) {
        if (discount_percent != '' && discount_type == 'before_tax') {
            total_tax_calculated = (total_tax * discount_percent) / 100;
            total_tax = (total_tax - total_tax_calculated);
        }
        total += total_tax;
        total_tax = accounting.formatNumber(total_tax)
        $('#tax_id_' + taxid).html(total_tax);
    });

    total = (total + subtotal);

    if (discount_percent != '' && discount_type == 'after_tax') {
        // Calculate the discount total
        var total_discount_calculated = (total * discount_percent) / 100;
    }

    total = total - total_discount_calculated;
    var adjustment = $('input[name="adjustment"]').val();
    adjustment = parseFloat(adjustment);

    // Check if adjustment not empty
    if (!isNaN(adjustment)) {
        total = total + adjustment;
    }
    // Append, format to html and display
    $('.discount_percent').html('-' + accounting.formatNumber(total_discount_calculated) + hidden_input('discount_percent', discount_percent) + hidden_input('discount_total', total_discount_calculated));
    $('.adjustment').html(accounting.formatNumber(adjustment) + hidden_input('adjustment', adjustment.toFixed(2)))
    $('.subtotal').html(subtotal = accounting.formatNumber(subtotal) + hidden_input('subtotal', subtotal.toFixed(2)));
    $('.total').html(format_money(total) + hidden_input('total', total.toFixed(2)));
}
// Deletes invoice items
function delete_item(row, itemid) {
    $(row).parents('tr').addClass('animated fadeOut', function() {
        setTimeout(function() {
            $(row).parents('tr').remove();
            calculate_total();
        }, 300)
    });
    var isedit = $('input[name="isedit"]');
    if (isedit.length > 0) {
        $('#removed-items').append(hidden_input('removed_items[]', itemid));
    }
}
// Format money functions
function format_money(total) {
    if (currency_placement === 'after') {
        return accounting.formatMoney(total, {
            format: "%v %s"
        });
    } else {
        return accounting.formatMoney(total);
    }
}

// Filter datatable invoice by status
function show_invoices_by_status(id) {
    $('input[name="status"]').val(id);
    $('.table-invoices').DataTable().ajax.reload();
    $('input[name="status"]').val('');
}

function show_estimates_by_status(id) {
    $('input[name="status"]').val(id);
    $('.table-estimates').DataTable().ajax.reload();
    $('input[name="status"]').val('');
}

function init_currency_symbol(id) {
    $.get(admin_url + 'currencies/get_currency_symbol/' + id, function(response) {
        accounting.settings.currency.symbol = response.symbol;
        calculate_total();
    }, 'json');
}

function delete_invoice_attachment(field, id) {
    $.get(admin_url + 'invoices/delete_attachment/' + id, function(success) {
        if (success == 1) {
            $(field).parents('.invoice-attachment-wrapper').remove();
            init_invoice($('body').find('input[name="_at_invoice_id"]').val());
        }
    });
}

function init_invoices_total() {
    if ($('#invoices_total').length == 0) {
        return;
    }
    var currency = $('select[name="estimate_total_currency"]').val();
    $.post(admin_url + 'invoices/get_invoices_total', {
        currency: currency,
        init_total: true
    }).success(function(response) {
        $('#invoices_total').html(response);
    });
}
