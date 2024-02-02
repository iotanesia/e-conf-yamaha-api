<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
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
        tr:nth-child(2) td.first-row{
            color: red;
        }
        .flying-text {
            position: absolute;
            left: 25px;
            top: 230px;
            z-index: -1;
        }
        td.count {
            height: 40px;
        }
    </style>
</head>

<body>
    <h2 class="text-center">PACKING LIST SHEET</h2>

    @foreach ($data as $key => $item)
        <table>
            <tr>
                <td class="no-bo">INVOICE NO</td>
                <td class="no-bo">:</td>
                <td class="no-bo" width="250">{{ $item->no_packaging }}</td>
                <td class="no-bo">SHIPPED BY</td>
                <td class="no-bo">:</td>
                @if ($item->id_mot == 2)
                    <td class="no-bo">-</td>
                @else
                    <td class="no-bo">{{ $item->manyFixedActualContainerCreation[0]->refMstLsp->name ?? null }}</td>
                @endif
            </tr>
            <tr>
                <td class="no-bo">Date</td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ $item->created_at->format('d F Y') }}</td>
                <td class="no-bo">ETD JAKARTA </td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ date('d F Y', strtotime($item->etd_jkt)) }}</td>
            </tr>
            <tr>
                <td class="no-bo">Container No</td>
                <td class="no-bo">:</td>
                @if ($item->id_mot == 2)
                    <td class="no-bo">-</td>
                @else
                    <td class="no-bo">{{ count($data) }} ({{ count($item->manyFixedActualContainerCreation) !== 0 ? ($item->manyFixedActualContainerCreation[0]->refMstContainer ? $item->manyFixedActualContainerCreation[0]->refMstContainer->container_type : null) : null }})</td>
                @endif
                <td class="no-bo">ETD {{ $item->refPartOfDischarge->port ?? null }}</td>
                <td class="no-bo">:</td>
                <td class="no-bo">{{ date('d F Y', strtotime($item->etd_ypmi)) }}</td>
            </tr>
            <tr>
                <td class="no-bo" width='70'>Seal No</td>
                <td class="no-bo" width='5'>:</td>
                <td class="no-bo" width='100'></td>
                <td class="no-bo" width='70'></td>
                <td class="no-bo" width='5'></td>
                <td class="no-bo" width='100'></td>
            </tr>
        </table>
        <hr>

            <table style="margin-top: 10px;">
                <tr>
                    <td class="no-bt no-bl no-br">Order No. {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</td>
                    <td class="no-bt no-bl no-br" colspan="6"></td>
                </tr>
                <tr>
                    <td class="text-center"> Case Mark and Number</td>
                    <td class="text-center" width="20"> Package Number</td>
                    <td class="text-center"> Parts Number</td>
                    <td class="text-center" width="40"> Qty <br> (PCS)</td>
                    <td class="text-center" width="40"> Nett. W <br> (Kgs)</td>
                    <td class="text-center" width="40"> Gross. W <br> (Kgs)</td>
                    <td class="text-center" width="40"> Meas. <br> (M3) </td>
                </tr>
                
                <p class="flying-text text-center">
                    YAMAHA <br>
                    {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                    999999-9999 <br>
                    {{ $item->refPartOfDischarge->port ?? null }} <br>
                    MADE IN INDONESIA <br>
                    INV. No. {{ $item->no_packaging }} <br>
                    C/No. : 1 - {{ count($box) }}
                </p>
                    {{-- <tr>
                        <td class="text-center" style="vertical-align: top;" rowspan="{{ (count($box) * $set_count) + 1 }}" width="140">
                            YAMAHA <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                            999999-9999 <br>
                            {{ $item->refPartOfDischarge->port ?? null }} <br>
                            MADE IN INDONESIA <br>
                            INV. No. {{ $item->no_packaging }} <br>
                            C/No. : 1 - {{ count($box) }}
                        </td>
                        <td style="padding:0px; border-bottom:0px;"></td>
                        <td style="padding:0px; border-bottom:0px;"></td>
                        <td style="padding:0px; border-bottom:0px;"></td>
                        <td style="padding:0px; border-bottom:0px;"></td>
                        <td style="padding:0px; border-bottom:0px;"></td>
                        <td style="padding:0px; border-bottom:0px;"></td>
                    </tr> --}}
                
                @foreach ($box as $key => $box_item)
                    @for ($i = 0; $i < count($box_item['item_no_series']); $i++)
                        <tr>
                            <td class="no-bt"></td>
                            @if ($i % 2 == 0 && $i == 0)
                                <td style='padding-bottom:5px;' class='text-center'>{{ $key+1 }}</td>
                            @else
                                <td class="no-bt"></td>
                            @endif
                            @if ($count_data == 1)
                                <td style='padding:55px 0 55px 0;' class='text-center'>{{ $box_item['item_no_series'][$i] }}</td>
                            @elseif ($count_data == 2)
                                <td style='padding:23px;' class='text-center'>{{ $box_item['item_no_series'][$i] }}</td>
                            @elseif ($count_data == 3)
                                <td style='padding:15px;' class='text-center'>{{ $box_item['item_no_series'][$i] }}</td>
                            @elseif ($count_data == 4)
                                <td style='padding:8px;' class='text-center'>{{ $box_item['item_no_series'][$i] }}</td>
                            @else
                                <td style='padding-bottom:5px;' class='text-center'>{{ $box_item['item_no_series'][$i] }}</td>
                            @endif
                            <td style='padding-bottom:5px;' class='text-center'>{{ round($box_item['qty_pcs_box'][$i], 2) }}</td>
                            <td style='padding-bottom:5px;' class='text-center'>{{ number_format($box_item['unit_weight_kg'][$i], 2) }}</td>
                            <td style='padding-bottom:5px;' class='text-center'>{{ $i % 2 == 0 && $i == 0 ?  number_format(array_sum($gross_weight_per_part[$key]), 2) : null }}</td>
                            <td style='padding-bottom:5px;' class='text-center'>{{ $i % 2 == 0 && $i == 0 ? number_format((($box_item['length'] * $box_item['width'] * $box_item['height']) / 1000000000), 3) : null }}</td>
                        </tr>
                    @endfor
                @endforeach
                
                {{-- total --}}
                <tr>
                    <td colspan="3" class="text-center"> TOTAL</td>
                    <td class="text-center">{{ $count_qty }}</td>
                    <td class="text-center">{{ number_format($count_net_weight,2) }}</td>
                    <td class="text-center">{{ number_format($count_gross_weight,2) }}</td>
                    <td class="text-center">{{ number_format($count_meas,3) }}</td>
                </tr>
            </table>

            <table style="margin-top: 20px;">
                <tr>
                    <td class="no-bo" width="200px">Grand Total Number Of Cartons</td>
                    <td class="no-bo" width="4">:</td>
                    <td width="50px" class="text-right no-bo">{{ count($box) }}</td>
                    <td class="no-bo">Cartons Boxes</td>
                </tr>
                <tr>
                    <td class="no-bo" width="200px">Grand Total Qty</td>
                    <td class="no-bo" width="4">:</td>
                    <td width="50px" class="text-right no-bo">{{ $count_qty }}</td>
                    <td class="no-bo">(PCS)</td>
                </tr>
                <tr>
                    <td class="no-bo" width="200px">Grand Total Nett Weights</td>
                    <td class="no-bo" width="4">:</td>
                    <td width="50px" class="text-right no-bo">{{ number_format($count_net_weight,2) }}</td>
                    <td class="no-bo">Kgs</td>
                </tr>
                <tr>
                    <td class="no-bo" width="200px">Grand Total Gross Weights</td>
                    <td class="no-bo" width="4">:</td>
                    <td width="50px" class="text-right no-bo">{{ number_format($count_gross_weight,2) }}</td>
                    <td class="no-bo">Kgs</td>
                </tr>
                <tr>
                    <td class="no-bo" width="200px">Grand Total Measurement</td>
                    <td class="no-bo" width="4">:</td>
                    <td width="50px" class="text-right no-bo">{{ number_format($count_meas,3) }}</td>
                    <td class="no-bo">M3</td>
                </tr>
            </table>

        <hr>
    @endforeach
    
</body>
