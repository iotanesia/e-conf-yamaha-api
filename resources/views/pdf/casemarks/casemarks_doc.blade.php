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
        @foreach ($box as $jml => $box_jml)
            @foreach ($box[$jml] as $box_item)
                <table style="margin-top: 30px;">
                    <tr>
                        <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                            YAMAHA <br>
                            {{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}  <br>
                            999999-9999 <br>
                            {{ $item->refPartOfDischarge->port ?? null }} <br>
                            MADE IN INDONESIA <br>
                            INV. No. {{ $item->no_packaging }} <br>
                            C/No. : {{ $number++ }}
                        </td>
                        <td class="text-center" style="font-size: 20px; font-weight: 500; vertical-align=top;">
                            CUSTOMER : <br>
                            {{ $item->refConsignee->nick_name }}
                            <hr>
                            PART NO <br>
                            {{ $box_item['ref_box']['item_no_series'] }}
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt text-center">QTY</td>
                                    <td class="no-br no-bt text-center">QTY</td>
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
            @endforeach
        @endforeach
    @endforeach 
    
</body>
