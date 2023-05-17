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
    <h4 class="text-center">PACKING LIST SHEET</h4>

    @foreach ($data as $item)
        <table>
            <tr>
                <td class="no-bo" width='70'>INVOICE NO</td>
                <td class="no-bo" width='5'>:</td>
                <td class="no-bo" width='100'>{{ $item->no_packaging }}</td>
                <td class="no-bo" width='70'></td>
                <td class="no-bo" width='5'></td>
                <td class="no-bo" width='100'></td>
            </tr>
            <tr>
                <td class="no-bo">Date</td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ $item->created_at->format('Y-m-d') }}</td>
                <td class="no-bo">SHIPPED BY</td>
                <td class="no-bo">:</td>
                <td class="no-bo">YPMI</td>
            </tr>
            <tr>
                <td class="no-bo">Container No</td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ count($item->manyFixedQuantityConfirmation) }} + {{ count($item->manyFixedActualContainerCreation) !== 0 ? ($item->manyFixedActualContainerCreation[0]->refMstContainer->container_type.''.$item->manyFixedActualContainerCreation[0]->refMstContainer->container_value) : null }}</td>
                <td class="no-bo">ETD JAKARTA </td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ $item->etd_jkt }}</td>
            </tr>
            <tr>
                <td class="no-bo">Seal No</td>
                <td class="no-bo">:</td>
                <td class="no-bo"></td>
                <td class="no-bo">ETD {{ $item->manyFixedActualContainerCreation[0]->refMstLsp->name ?? null }}</td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ $item->etd_ypmi }}</td>
            </tr>
        </table>
        <hr>

        <table style="margin-top: 10px;">
            <tr>
                <td class="no-bt no-bl no-br">Order No. {{ $item->no_packaging }}</td>
                <td class="no-bt no-bl no-br" colspan="6"></td>
            </tr>
            <tr>
                <td class="text-center"> Case Mark and Number</td>
                <td class="text-center"> Package Number</td>
                <td class="text-center"> Parts Number</td>
                <td class="text-center"> Qty <br> (PCS)</td>
                <td class="text-center"> Nett. W <br> (Kgs)</td>
                <td class="text-center"> Gross. W <br> (Kgs)</td>
                <td class="text-center"> Meas. <br> (M3) </td>
            </tr>
            <tr>
                {{-- rowspan mengikuti jumlah baris  --}}
                <td class="text-center" style="vertical-align: top;" rowspan="{{ count($item->manyFixedQuantityConfirmation) }}" width="140">
                    YAMAHA <br>
                    {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                    9999-99999 <br>
                    {{ $item->manyFixedQuantityConfirmation[0]->refFixedActualContainerCreation->refMstLsp->name ?? null }} <br>
                    MADE IN INDONESIA <br>
                    {{ $item->no_packaging }} <br>
                    C/NO.20
                </td>
                <td style="padding:0px; border-bottom:0px;"></td>
                <td style="padding:0px; border-bottom:0px;"></td>
                <td style="padding:0px; border-bottom:0px;"></td>
                <td style="padding:0px; border-bottom:0px;"></td>
                <td style="padding:0px; border-bottom:0px;"></td>
                <td style="padding:0px; border-bottom:0px;"></td>
            </tr>
            @foreach ($item->manyFixedQuantityConfirmation as $qty_item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $qty_item->manyFixedQuantityConfirmation[0]->item_serial ?? null }}</td>
                    <td class="text-center">{{ $qty_item->manyFixedQuantityConfirmation[0]->qty ?? null }}</td>
                    <td class="text-center">77</td>
                    <td class="text-center">167.2</td>
                    <td class="text-center">03.211</td>
                </tr>
            @endforeach
        </table>
    @endforeach
    
</body>
