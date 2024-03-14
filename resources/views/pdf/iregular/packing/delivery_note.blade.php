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
    <h4 class="text-center" style="font-size: 18px;">DELIVERY NOTE</h4>
    <table>
        <tr>
            <td class="no-bo" width='90'>
                PT Yamaha Motor Part Indonesia <br>
                Jl Permata Raya Lot F2 & F6 <br>
                PO BOX 157 KIIC, Karawang, West Java - Indonesia. <br>
                Phone: +6221 890 4581. Fax: +6221 890 4541    
            </td>
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
                User Name : <br>
                {{ $data->username }}
            </td>
        </tr>
    </table>
    <table style="margin-top: 10px;">
        <tr>
            <td class="no-bo" width="70">Reff. Invoice No</td>
            <td class="no-bo" width="5">:</td>
            <td class="no-bo">{{ $data->ref_invoice_no }}</td>
            <td class="no-bo" width="50">Truck No</td>
            <td class="no-bo" width="5">:</td>
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
            <td class="no-bo">{{ $data->jenis_truck }}</td>
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
                <th class="text-center"> PO No </th>
                <th class="text-center"> Quantity <br> (pcs)</th>
                <th class="text-center"> No. Inv</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data->manyDetail as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}.</td>
                    <td class="text-center">{{ $item->item_no }}</td>
                    <td class="text-center">{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->po_no }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-center">{{ $item->invoice_no }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 80px;">
        <thead>
            <tr>
                <th class="text-center"> Receipt Sign</th>
                <th class="text-center"> Logistic Provider</th>
                <th class="text-center"> Approval</th>
                <th class="text-center"> Operator </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center" style="padding: 30px;" width="50"> </td>
                <td class="text-center" style="padding: 30px;" width="50"> </td>
                <td class="text-center" style="padding: 30px;" width="50"> </td>
                <td class="text-center" style="padding: 30px;" width="50"> </td>
            </tr>
        </tbody>
    </table>
</body>