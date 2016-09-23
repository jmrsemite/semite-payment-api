/**
 * Created by mbicanin on 7/26/16.
 */
if (document.getElementById('min')) {
    $.fn.dataTableExt.afnFiltering.push(
        function (oSettings, aData, iDataIndex) {
            var iFini = document.getElementById('min').value;
            var iFfin = document.getElementById('max').value;
            var iStartDateCol = 2;
            var iEndDateCol = 2;

            iFini = iFini.substring(2, 10) + iFini.substring(3, 5) + iFini.substring(0, 2);
            iFfin = iFfin.substring(2, 10) + iFfin.substring(3, 5) + iFfin.substring(0, 2);

            var datofini = aData[iStartDateCol].substring(2, 10) + aData[iStartDateCol].substring(3, 5) + aData[iStartDateCol].substring(0, 2);
            var datoffin = aData[iEndDateCol].substring(2, 10) + aData[iEndDateCol].substring(3, 5) + aData[iEndDateCol].substring(0, 2);

            if (iFini === "" && iFfin === "") {
                return true;
            }
            else if (iFini <= datofini && iFfin === "") {
                return true;
            }
            else if (iFfin >= datoffin && iFini === "") {
                return true;
            }
            else if (iFini <= datofini && iFfin >= datoffin) {
                return true;
            }
            return false;
        }
    );
}

if (!document.getElementById('min')) {
    $(document).ready(function() {

        $.ajax({
            url: site_url + 'dashboard/transaction_home_map',
            dataType: 'json',
            success: function(json) {
                data = [];

                for (i in json) {
                    data[i] = json[i]['total'];
                }

                $('#vmap').vectorMap({
                    map: 'world_en',
                    backgroundColor: '#FFFFFF',
                    borderColor: '#FFFFFF',
                    color: '#9FD5F1',
                    hoverOpacity: 0.7,
                    selectedColor: '#666666',
                    enableZoom: true,
                    showTooltip: true,
                    values: data,
                    normalizeFunction: 'polynomial',
                    onLabelShow: function(event, label, code) {
                        if (json[code]) {
                            label.html('<strong>' + label.text() + '</strong><br />' + 'Transaction ' + json[code]['total'] + '<br />' + 'Amount ' + json[code]['amount']);
                        }
                    }
                });
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });
}
$(document).ready(function() {

    transaction_home_chart();

    var exRowTable = $('#transaction-log').DataTable({
        responsive: true,
        'ajax': site_url + 'dashboard',
        className: 'btn btn-default',
        'columns': [{
            'class': 'details-control',
            'orderable': false,
            'data': null,
            'defaultContent': ''
        },
            { 'data': 'transaction_id' },
            { 'data': 'date_added' },
            { 'data': 'type' },
            { 'data': 'card_type' },
            { 'data': 'processed_amount' },
            { 'data': 'status' },
        ],
        'order': [[2, 'desc']]
    });

    // Add event listener for opening and closing details
    $('#transaction-log tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = exRowTable.row( tr );

        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child( format(row.data()) ).show();
            getDetails(row.data());
            tr.addClass('shown');
        }
    });

    function format (d) {

        return '<div id="transaction-'+ d.transaction_id+'"><h4 class="loading text-center text-muted"><i class="fa fa-spinner fa-spin"></i>  Please wait</h4></div>';

    }
});

$(document).ready(function() {

    $( "#min" ).datepicker();
    $( "#max" ).datepicker();

    var exRowTable = $('#transaction-history').DataTable({
        responsive: true,
        'ajax': site_url + 'transactions',
        "dom": 'Bfrtip',
        "buttons": [
            'csv', 'excel', 'print'
        ],
        className: 'btn btn-default',
        'columns': [{
            'class': 'details-control',
            'orderable': false,
            'data': null,
            'defaultContent': ''
        },
            { 'data': 'transaction_id' },
            { 'data': 'date_added' },
            { 'data': 'type' },
            { 'data': 'card_type' },
            { 'data': 'processed_amount' },
            { 'data': 'status' },
        ],
        'order': [[2, 'desc']]
    });

    $('#min, #max').change( function() {
        exRowTable.draw();
    } );

    // Add event listener for opening and closing details
    $('#transaction-history tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = exRowTable.row( tr );

        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child( format(row.data()) ).show();
            getDetails(row.data());
            tr.addClass('shown');
        }
    });

    $('.export').on('click',function(){

        $("#output").html(getTableData($("#transaction-history")));
    });

    $('.reset-filter').on('click',function(){

        $( "#min" ).val('');
        $( "#max" ).val('');

        $("#method option:first").prop('selected','selected');
        $("#status option:first").prop('selected','selected');


        $('#method').change( function() { exRowTable.columns(3).search( this.value).draw() });
        $('#status').change( function() { exRowTable.columns(6).search( this.value).draw() });

        $("#min").change ( function() { exRowTable.draw(); } );
        $("#max").change ( function() { exRowTable.draw(); } );

        $( "#min" ).trigger('change');
        $( "#max" ).trigger('change');

        $( "#method" ).trigger('change');
        $( "#status" ).trigger('change');
        exRowTable.search( '', true ).draw();

    });

    $('.refresh-data').on('click',function(){
        exRowTable.ajax.reload();
    });

    $('#method').on('change', function() { exRowTable.columns(3).search( this.value).draw() });
    $('#status').on('change', function() { exRowTable.columns(6).search( this.value).draw() });

    function format (d) {

        return '<div id="transaction-'+ d.transaction_id+'"><h4 class="loading text-center text-muted"><i class="fa fa-spinner fa-spin"></i>  Please wait</h4></div>';

    }


});

function getDetails(data){

    setTimeout(function () {
        $.ajax({
            type: 'POST',
            url: site_url +'transactions/detail/'+data.transaction_id,
            data : data,
            dataType: 'html',
            success: function (html) {

                $('.loading').remove();

                $('#transaction-'+data.transaction_id).html(html);
            },
            error: function (xhr, ajaxOptions, thrownError) {
                if (xhr.status != 0) {
                    alert(xhr.status + "\r\n" +thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            }
        });
    },200);

}

function transaction_home_chart() {

    // Check if chart canvas exists.
    var chart = $('#transaction-home-chart');
    if(chart.length == 0){
        return;
    }

    if (typeof(salesChart) !== 'undefined') {
        salesChart.destroy();
    }

    var data = {};

    $.post(site_url + 'dashboard/transaction_home_chart', data).success(function(response) {
        response = $.parseJSON(response);
        var ctx = chart.get(0).getContext('2d');
        salesChart = new Chart(ctx).Line(response, {
            responsive: true,
            multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>"
        });
    });
}