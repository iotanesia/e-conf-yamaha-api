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

    @foreach ($data as $key => $item)
        <table style="margin-top: 30px;">
            <tr>
                <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                    YAMAHA <br>
                    {{ $item->order_no }}  <br>
                    9999-99999 <br>
                    {{ $item->refFixedActualContainerCreation->refMstLsp->name ?? null }} <br>
                    MADE IN INDONESIA <br>
                    {{ $item->refFixedActualContainer->no_packaging ?? null }} <br>
                    C/NO.20
                </td>
                <td class="text-center" style="font-size: 20px; font-weight: 500; vertical-align=top;">
                    CUSTOMER : <br>
                    {{ $item->refConsignee->nick_name }}
                    <hr>
                    PART NO <br>
                    {{ $item->item_no }}
                    <hr>
                    <table>
                        <tr>
                            <td class="no-bo text-center">QTY</td>
                            <td class="no-bo text-center">QTY</td>
                        </tr>
                        <tr>
                            <td class="no-bo text-center">{{ count($item->manyFixedQuantityConfirmationBox) == 0 ? null : $item->manyFixedQuantityConfirmationBox[0]->qty_pcs_box }}</td>
                            <td class="no-bo text-center">{{ count($item->manyFixedQuantityConfirmationBox) == 0 ? null : round($item->manyFixedQuantityConfirmationBox[0]->refMstBox->unit_weight_kg, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="no-bo text-center">PCS</td>
                            <td class="no-bo text-center">KG</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endforeach
    
</body>
