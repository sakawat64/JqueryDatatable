<div class="row" style="font-size:12px">
    <div class="col-md-12 table-responsive">
        <table class="table table-responsive table-bordered table-hover table-striped" id="datatable_view_due">
            <thead>
            <tr class="bg-teal-800">
                <th class="col-md-1">Invoice</th>
                <th class="col-md-1">Customer Name</th>
                <th class="col-md-1">Customer ID</th>
                <th class="col-md-1">Address</th>
                <th class="col-md-1">Zone</th>
                <th class="col-md-1">Mobile No</th>
                <th class="col-md-1">Speed</th>
                <th class="col-md-1">C. Date</th>
                <th class="col-md-1">Bill Date</th>
                <th class="col-md-1">Previous Due</th>
                <th class="col-md-1">Bill</th>
                <th class="col-md-1">Total<br>Dues</th>
                <th class="col-md-1">IP</th>
                <th class="d col-md-2">Action</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
        <div class="progress" id="progressbar">
            <div class="progress-label">Calculating Your Bill Data...</div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {

        var progressbar = $("#progressbar");
        var progressLabel = $(".progress-label");

        progressbar.progressbar({

            value: false,

            change: function () {

                progressLabel.text(progressbar.progressbar("value") + "%");
            },
            complete: function () {

            }
        });

        function progress() {

            var val = progressbar.progressbar("value") || 0;

            progressbar.progressbar("value", val + 2);

            if (val < 99) {

                setTimeout(progress, <?php echo $rowCount * 0.1; ?>);
            }
        }
        $('#datatable_view_due').on('click', 'tbody td div.btn-group a.paid', function (e) {

            e.preventDefault();
            $(this).removeClass('btn-primary');
            $(this).addClass('disabled btn-default');
            var agentName = $(this).data('name');
            var amountTaka = $(this).data('amount');

            url = $(this).attr('href');

            $.get(url, function (data, status) {

                if (status == 'success') {
                    alert('The Bill of ' + amountTaka + 'tk has been paid for ' + agentName);
                } else {
                    alert('Sorry can\'t add the bill info. Error occurred');
                }

            });
        });

        setTimeout(progress, 500);
        var table = $('#datatable_view_due').DataTable({
            "processing": false,
            "initComplete": function (settings, json) {
                $('#progressbar').hide();
            },
            "deferRender": true,
            "ajax": 'view/ajax_action/ajax_view_due_payment.php',
            "order": [[0, 'asc']],
            "columns": [
                {
                    "data": 'agent_id',
                    render: function (data, type, row) {
                        return '<?php if($obj->hasPermission($ty, 'invoice')){ ?><a target="_blank" href="./pdf/invoice.php?token1=' + data + '" class="btn btn-warning btn-xs"><span style="color:#000;" class="glyphicon glyphicon-print"></span></a><?php } ?>' +
                            '<button class="sms btn btn-primary btn-xs pull-right" data-agent='+ data +'><span class="glyphicon glyphicon-envelope"></span></button>'
                    }
                },

                {"data": 'agent_name'},

                {
                    "data": 'customer_id',
                    render: function (data, type, row) {
                        return '<a href="?q=view_customer_payment_individual&token2=' + row['agent_id'] + '">' + data + '</a>'
                    }
                },

                {"data": 'agent_address'},

                {
                    "data": 'zone',
                    render: function (data, type, row) {
                            return '<small>' + data + '</small>'
                        }
                },

                {"data": 'mobile'},

                {"data": 'speed'},

                {"data": 'connection_date'},
                {"data": 'bill_date'},

                {"data": 'previous_due'},

                {"data": 'bill'},

                {
                    "data": 'total_due',
                    render: function (data, type, row) {
                        if (row['previous_due'] > 0) {
                            return '<span class="text-danger" style="font-weight:800; font-size:12px">' + data + '</span>'
                        } else {
                            return '<span>' + data + '</span>'
                        }

                    }
                },

                {"data": 'ip'},

                {
                    "data": 'agent_id',
                    "render": function (data, type, row, meta) {
                        return '<div class="btn-group"><a href="view/ajax_action/add_ajax_data.php?token=' + data + '&amount=' + row['bill'] + '&flag=1" data-name="' + row['agent_name'] + '" data-amount="' + row['bill'] + '" class="btn btn-primary btn-sm paid" style="padding:5px">Pay</a>' +

                            '<a href="?q=add_payment&token1=' + data + '" style="padding:5px" class="btn btn-success btn-sm "> Payment </a></div>';
                    }
                },

            ],
            "fnDrawCallback": function (oSettings) {

                if (oSettings.json) {
                    var loadData = oSettings.json;
                    showZoneWiseBill(loadData.total_bill);
                }
            },

        });

        $('select[name="zone"]').on('change', function () {

            var zoneId = $(this).val();

            if (zoneId != 'x') {

                table.ajax.url('view/ajax_action/ajax_view_due_payment.php?zone=' + zoneId).load();
                $('a#print_link').attr('href', './pdf/index.php?zonePrint=' + zoneId);
                $('a#print_client_bill').attr('href', '?q=print_client_bill&zonePrint=' + zoneId);

            } else {

                table.ajax.url('view/ajax_action/ajax_view_due_payment.php').load();
                $('a#print_link').attr('href', '?q=view_report_paganition&flag=INVOICE');
                $('a#print_client_bill').attr('href', '?q=print_client_bill');
            }
        });