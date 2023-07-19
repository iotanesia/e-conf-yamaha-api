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
        .vertical {
            border-left: 2px solid blue;
            height: 20px;
            position:absolute;
            /* left: 50%; */
        }
    </style>
</head>

<body>

    @foreach ($data as $key => $item) 
    @if ($check == null) {{-- part set --}}
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)
                <table style="margin-top: 30px; padding:50px;">
                    <tr>
                        <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                            YAMAHA <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }} <br>
                            {{ $item->refPartOfDischarge->port ?? null }} <br>
                            MADE IN INDONESIA <br>
                            INV.No. {{ $item->no_packaging }} <br>
                            C/No. : {{ $jml+1 }}
                        </td>
                        <td class="text-center" style="font-size: 20px; font-weight: 500; vertical-align=top;">
                            CUSTOMER : <br>
                            {{ $item->refConsignee->nick_name }}
                            <hr>
                            PART NO. <br>
                            @foreach ($box_item['item_no_series'] as $item_no_series)
                            {{ substr($item_no_series,0,-3) }} <br>
                            @endforeach
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center">QTY</td>
                                    <td class="no-br no-bt no-bb text-center">GW</td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center">{{ $box_item['qty_pcs_box'][$jml] ?? null }}</td>
                                    <td class="no-br text-center">{{ round($box_item['total_gross_weight'],1) }}</td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center">PCS</td>
                                    <td class="no-br no-bb text-center">KG</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endfor
        @endforeach
    @elseif (substr($box[0]['ref_box']['item_no_series'],13,2) !== 00) {{-- wheel cast --}}
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)
                <table style="margin-top: 0px; padding:50px;">
                    <tr>
                        <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                            YAMAHA <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }} <br>
                            {{ $item->refPartOfDischarge->port ?? null }} <br>
                            MADE IN INDONESIA <br>
                            INV.No. {{ $item->no_packaging }} <br>
                            C/No. : {{ $jml+1 }}
                        </td>
                        <td class="text-center" style="font-size: 20px; font-weight: 500; vertical-align=top;">
                            <p style="text-align: left; padding-bottom:0; margin:0;"><u>PART NAME. :</u></p> 
                            <p style="text-align: left; padding-bottom:0; margin:0;">{{ $box_item['ref_box']['ref_part']['description'] }}</p>
                            <br> 
                            <p style="text-align: left; padding-bottom:0; margin:0;"><u>PART NO.</u></p>
                            <p style="text-align: left; padding-bottom:0; margin:0;">{{ substr($box_item['ref_box']['item_no_series'],0,-3) }}</p>
                            <hr>
                            <u>CUSTOMER :</u> <br>
                            {{ $item->refConsignee->nick_name }}
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center">QTY</td>
                                    <td class="no-br no-bt no-bb text-center">GW</td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center">{{ $box_item['qty_pcs_box'] ?? null }}</td>
                                    <td class="no-br text-center">{{ round($box_item['ref_box']['total_gross_weight'],1) }}</td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center">PCS</td>
                                    <td class="no-br no-bb text-center">KG</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endfor
        @endforeach
    @else
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)
                <table style="margin-top: 30px; padding:50px;">
                    <tr>
                        <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                            YAMAHA <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }} <br>
                            {{ $item->refPartOfDischarge->port ?? null }} <br>
                            MADE IN INDONESIA <br>
                            INV.No. {{ $item->no_packaging }} <br>
                            C/No. : {{ $jml+1 }}
                        </td>
                        <td class="text-center" style="font-size: 20px; font-weight: 500; vertical-align=top;">
                            CUSTOMER : <br>
                            {{ $item->refConsignee->nick_name }}
                            <hr>
                            PART NO. <br>
                            {{ substr($box_item['ref_box']['item_no_series'],0,-3) }}
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center">QTY</td>
                                    <td class="no-br no-bt no-bb text-center">GW</td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center">{{ $box_item['qty_pcs_box'] ?? null }}</td>
                                    <td class="no-br text-center">{{ round($box_item['ref_box']['total_gross_weight'],1) }}</td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center">PCS</td>
                                    <td class="no-br no-bb text-center">KG</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endfor
        @endforeach
    @endif
        
    @endforeach 
    
</body>
