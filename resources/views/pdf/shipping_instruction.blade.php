<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            margin-top: 5px;
            margin-left: 26px;
            font-size: 9pt;
            font-size: 9pt;
        /* font-family: 'Times New Roman', Times, serif; */
        font-family: Arial, Helvetica, sans-serif;
        /* margin-right: 4cm; */
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        td,
        th {
            border: 1px solid #000;
            text-align: left;
            padding: 5px;
        }
        .no-br {
            border-right: hidden;
        }
        .no-bl {
            border-left: hidden;
        }
        .no-bt {
            border-top: hidden;
        }
        .no-bb {
            border-bottom: hidden;
        }
        .no-bo {
            border-right: hidden;
            border-left: hidden;
            border-top: hidden;
            border-bottom: hidden;
        }
        .no-pa {
            padding: 0px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .page_break {
            page-break-before: always;
        }
    </style>
</head>

<body>
        <table>
            <tr>
                <td class="text-center no-bo" style="font-size: 14px;" colspan="4"><b>SHIPPING INSTRUCTION</b> <br> <p style="font-size: 12px;">PT Yamaha Motor Parts Manufacturing Indonesia</p></td>
            </tr>
            <tr>
                <td class="no-bo" width="10">To:</td>
                <td class="no-bo" width="100">{{$data->to}}</td>
                <td class="no-bo" width="10">CC</td>
                <td class="no-bo" width="100">{{$data->cc}}</td>
            </tr>
            <tr>
                <td class="no-bo">Attn:</td>
                <td class="no-bo">{{$data->attn_to}}</td>
                <td class="no-bo">Attn</td>
                <td class="no-bo">{{$data->attn_cc}}</td>
            </tr>
        </table>

        <br>
        <br>
        <table>
            <tr>
                <td style="padding: 3px;" class="no-bo">Please arrange our Shipment as per below Details</td>
                <td style="padding: 3px;" class="no-bo text-right">Date : {{$data->instruction_date}}</td>
            </tr>
        </table>
        <table>
            <tr>
                <td rowspan="2" width="240"><u>Shipper :</u> <br> {{$data->shipper}}</td>
                <td><u>Si Number :</u> <br> {{$data->si_number}}</td>
            </tr>
            <tr>
                <td><u>Invoice No :</u> <br> {{$data->invoice_no}}</td>
            </tr>
            <tr>
                <td><u>Consignee:</u> 
                    <br> {{json_decode($data->consignee)->name}} 
                    <br> {{json_decode($data->consignee)->address1}} 
                    <br> {{json_decode($data->consignee)->address2}}
                </td>
                <td><u>Notify Party:</u> <br> {{$data->notify_part}}</td>
            </tr>
            <tr>
                <td>SHIPMENT INFORMATION</td>
                <td></td>
            </tr>
            <tr>
                <td>BL Information</td>
                <td></td>
            </tr>
            <tr>
                <td>CASE MARKS</td>
                <td></td>
            </tr>
        </table>
        <br>
        <p>Sincerelly Yours</p>
        <table style="width:320px">
            <tr>
                <td class="text-center">Issued</td>
                <td class="text-center">Checked</td>
                <td class="text-center">Approved</td>
            </tr>
            <tr>
                <td height="50"></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </body>
</html>