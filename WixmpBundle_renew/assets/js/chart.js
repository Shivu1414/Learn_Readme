'use strict'
import $, { data } from 'jquery';
import 'chart.js'; /* used in dashboard view to create charts */
import 'datatables.net';

$(document).ready(function() {

    //by month datatable
    $('#bymonth-table table').DataTable({
        dom: "Blfrtip",
        "language": {
            info: '_START_ ' + "to" + ' _END_ ' + "of" + ' _TOTAL_' + "fadsfadfasdfds",
            "paginate": {
                "previous": "<i class='fa fa-caret-left'></i>",
                "next": "<i class='fa fa-caret-right'></i>",
            },
            "emptyTable": wkMpTrans.no_data,
            "infoEmpty": wkMpTrans.no_data,
        },
        columnDefs: [
            { targets: 1, orderData: 0 },
            { targets: 0, visible: false }
        ],
        "searching": false,
        "order": [[1, 'desc']],
        // 1 array is for value and second for display/language
        "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ALL"]],
    });
    //  by seller datatable
    $('#byseller-table table').DataTable({
        dom: "Blfrtip",
        "language": {
            info: '_START_ ' + "to" + ' _END_ ' + "of" + ' _TOTAL_',
            "paginate": {
                "previous": "<i class='fa fa-caret-left'></i>",
                "next": "<i class='fa fa-caret-right'></i>",
            },
            "emptyTable": wkMpTrans.no_data,
            "infoEmpty": wkMpTrans.no_data,
        },
        
        "searching": false,
        "order": [[1, 'desc']],
        // 1 array is for value and second for display/language
        "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ALL"]],
    });

});