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
    <h4 class="text-center">DELIVERY NOTE</h4>
    <table>
        <tr>
            <td class="no-bo" width='70'>{{ $data->shipper }}</td>
            <td class="no-bo" width='5'></td>
            <td class="no-bo text-right" width='100'>
                Kepada Yth, <br>
                {{ $data->yth }}
            </td>
        </tr>
        <tr>
            <td class="no-bo" width='70'></td>
            <td class="no-bo" width='5'></td>
            <td class="no-bo text-right" width='100'>
                User Name, <br>
                {{ $data->consignee }}
            </td>
        </tr>
    </table>
    <table style="margin-top: 10px;">
        <tr>
            <td class="no-bo">Surat Jalan No</td>
            <td class="no-bo">:</td>
            <td class="no-bo">{{ $data->no_letters }}</td>
            <td class="no-bo">Truck No</td>
            <td class="no-bo">:</td>
            <td class="no-bo">{{ $data->truck_no }}</td>
        </tr>
        <tr>
            <td class="no-bo">Delivery Date</td>
            <td class="no-bo">:</td>
            <td class="no-bo">{{ $data->delivery_date }}</td>
            <td class="no-bo"></td>
            <td class="no-bo"></td>
            <td class="no-bo"></td>
        </tr>
        <tr>
            <td class="no-bo">Jenis Truck</td>
            <td class="no-bo">:</td>
            <td class="no-bo">LCL</td>
            <td class="no-bo"></td>
            <td class="no-bo"></td>
            <td class="no-bo"></td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <thead>
            <tr>
                <th class="text-center"> No.</th>
                <th class="text-center"> Item Number</th>
                <th class="text-center"> Item Name</th>
                <th class="text-center"> Order No </th>
                <th class="text-center"> Quantity <br> (pcs)</th>
                <th class="text-center"> No. Packing List</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data->manyRegularStockConfirmationOutstockNoteDetail as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}.</td>
                    <td class="text-center">{{ $item->item_no }}</td>
                    <td class="text-center">{{ $item->refMstPart->description }}</td>
                    <td class="text-center">{{ $item->order_no }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-center">{{ $item->no_packing }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
