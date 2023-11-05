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
        .vertical {
            border-left: 2px solid blue;
            height: 20px;
            position:absolute;
            /* left: 50%; */
        }
    </style>
</head>

<body style="font-family: Arial, Helvetica, sans-serif !important;">

    @foreach ($data as $key => $item) 
    {{-- @if ($check == null) part set --}}
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)

            @if ($i == 1)
                <table style="margin-bottom: 45px;">
            @else
                <table>
            @endif
                    <tr>
                        <td class="text-center" style="font-size: 40px; font-weight: 500; vertical-align=top; padding: 20px;">
                            <p style="margin:0 0 15px 0; padding:0;"><b>YAMAHA</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }}</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->refPartOfDischarge->port ?? null }}</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>MADE IN INDONESIA</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>INV.No. {{ $item->no_packaging }}</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>C/No. : {{ $jml+1 }}</b></p>
                        </td>
                        <td class="text-center" style="font-size: 25px; font-weight: 500; vertical-align=top;">
                            <p style="padding:0; margin: 0 0 10px 0;"><b>CUSTOMER :</b></p>
                            <b>{{ $item->refConsignee->nick_name }}</b>
                            <hr>
                            <p style="padding:0; margin: 0 0 10px 0;"><b>PART NO.</b></p>
                            @foreach ($box_item['item_no_series'] as $item_no_series)
                            <b>{{ substr($item_no_series,0,-3) }}</b> <br>
                            @endforeach
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>QTY</b></p> </td>
                                    <td class="no-br no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>GW</b></p> </td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center"><b>{{ $box_item['qty_pcs_box'][0] ?? null }}</b></td>
                                    <td class="no-br text-center"><b>{{ round($box_item['total_gross_weight'],1) }}</b></td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center"><p style="padding:0; margin:15px 0 0 0;"><b>{{ count($box_item['qty_pcs_box']) > 1 ? 'SET' : 'PCS' }}</b></p></td>
                                    <td class="no-br no-bb text-center"><p style="padding:0; margin:15px 0 0 0;"><b>KG</b></p></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endfor
        @endforeach
    {{-- @elseif ($item->refConsignee->nick_name == 'YMC') YMC
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)

                @if ($i == 1)
                    <table style="margin-bottom: 45px;">
                @else
                    <table>
                @endif
                    <tr>
                        <td class="text-center" style="font-size: 40px; font-weight: 500; vertical-align=top; padding: 20px;">
                            <p style="margin:0 0 15px 0; padding:0;"><b>YAMAHA</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</b></p>  
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->refPartOfDischarge->port ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>MADE IN INDONESIA</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>INV.No. {{ $item->no_packaging }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>C/No. : {{ $jml+1 }}</b></p>
                        </td>
                        <td class="text-center" style="font-size: 25px; font-weight: 500; vertical-align=top;">
                            <p style="text-align: left; padding:0; margin:0 0 15px 0; font-weight:400;"><b><u>PART NO. :</u></b></p>
                            <p style="text-align: left; padding:0; margin:0 0 35px 0; font-size: 30px;"><b>{{ substr($box_item['ref_box']['item_no_series'],0,-3) }}</b></p>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb" style="font-weight:400;"><b><u>QTY :</u></b></td>
                                    <td class="no-br no-bt no-bb" style="font-weight:400;"><b><u>GW :</u></b></td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center"><p style="font-size: 28px;"><b>{{ $box_item['qty_pcs_box'] ?? null }} PCS</b></p></td>
                                    <td class="no-br text-center"><p style="font-size: 28px;"><b>{{ round($box_item['ref_box']['total_gross_weight'],1) }} KG</b></p></td>
                                </tr>
                            </table>
                            <p style="margin:15px 0 0 0; padding:0; font-weight:400;"><b><u>CUSTOMER :</u></b></p>
                            <p style="font-size: 40px;"><b>{{ $item->refConsignee->nick_name }}</b></p>
                        </td>
                    </tr>
                </table>
            @endfor
        @endforeach
    @elseif (substr($box[0]['ref_box']['item_no_series'],13,2) !== 00) wheel cast
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)

            @if ($i == 1)
                <table style="margin-bottom: 45px;">
            @else
                <table>
            @endif
                    <tr>
                        <td class="text-center" style="font-size: 40px; font-weight: 500; vertical-align=top; padding: 20px;" width="420px">
                            <p style="margin:0 0 15px 0; padding:0;"><b>YAMAHA</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</b></p>  
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->refPartOfDischarge->port ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>MADE IN INDONESIA</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>INV.No. {{ $item->no_packaging }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>C/No. : {{ $jml+1 }}</b></p>
                        </td>
                        <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                            <p style="padding:0; margin:0 0 15px 0;"><b>CUSTOMER :</b></p> 
                            <p style="padding:0; margin:0 0 15px 0;"><b>{{ $item->refConsignee->nick_name }}</b></p> 
                            <hr>
                            <p style="padding:0; margin:0 0 15px 0;"><b>PART NO.</b></p> 
                            <p style="padding:0; margin:0 0 15px 0;"><b>{{ substr($box_item['ref_box']['item_no_series'],0,-3) }}</b></p> 
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>QTY</b></p> </td>
                                    <td class="no-br no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>GW</b></p> </td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center"><b>{{ $box_item['qty_pcs_box'] ?? null }}</b></td>
                                    <td class="no-br text-center"><b>{{ round($box_item['ref_box']['total_gross_weight'],1) }}</b></td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center"><p style="padding:0; margin:15px 0 0 0;"><b>PCS</b></p></td>
                                    <td class="no-br no-bb text-center"><p style="padding:0; margin:15px 0 0 0;"><b>KG</b></p></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table> --}}

                {{-- <table style="margin-top:35px;">
                    <tr>
                        <td class="text-center" style="font-size: 40px; font-weight: 500; vertical-align=top;" width="420px">
                            <p style="margin:0 0 15px 0; padding:0;"><b>YAMAHA</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</b></p>  
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->refPartOfDischarge->port ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>MADE IN INDONESIA</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>INV.No. {{ $item->no_packaging }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>C/No. : {{ $jml+1 }}</b></p>
                        </td>
                        <td class="text-center" style="font-size: 25px; font-weight: 500; vertical-align=top;">
                            <p style="text-align: left; padding:0; margin:0 0 15px 0;"><b><u>PART NAME. :</u></b></p> 
                            <p style="text-align: left; padding:0; margin:0 0 0 0;"><b>{{ $box_item['ref_box']['ref_part']['description'] }}</b></p>
                            <br> 
                            <p style="text-align: left; padding:0; margin:0 0 15px 0;"><b><u>PART NO.</u></b></p>
                            <p style="text-align: left; padding:0; margin:0 0 15px 0;"><b>{{ substr($box_item['ref_box']['item_no_series'],0,-3) }}</b></p>
                            <hr>
                            <p style="margin:0 0 5px 0; padding:0; font-weight:400; font-size:20px;"><b><u>CUSTOMER :</u></b></p>
                            <b>{{ $item->refConsignee->nick_name }}</b>
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center"><b>QTY</b></td>
                                    <td class="no-br no-bt no-bb text-center"><b>GW</b></td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center"><b>{{ $box_item['qty_pcs_box'] ?? null }}</b></td>
                                    <td class="no-br text-center"><b>{{ round($box_item['ref_box']['total_gross_weight'],1) }}</b></td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center"><b>PCS</b></td>
                                    <td class="no-br no-bb text-center"><b>KG</b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table> --}}
            {{-- @endfor
        @endforeach
    @else
        @foreach ($box as $jml => $box_item)
            @for ($i=1; $i<=2; $i++)

                @if ($i == 1)
                    <table style="margin-bottom: 45px;">
                @else
                    <table>
                @endif
                    <tr>
                        <td class="text-center" style="font-size: 40px; font-weight: 500; vertical-align=top; padding: 20px;" width="420px">
                            <p style="margin:0 0 15px 0; padding:0;"><b>YAMAHA</b></p>
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</b></p>  
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->manyFixedQuantityConfirmation[0]->cust_item_no ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->refPartOfDischarge->port ?? null }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>MADE IN INDONESIA</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>INV.No. {{ $item->no_packaging }}</b></p> 
                            <p style="margin:0 0 15px 0; padding:0;"><b>C/No. : {{ $jml+1 }}</b></p>
                        </td>
                        <td class="text-center" style="font-size: 30px; font-weight: 500; vertical-align=top;">
                            <p style="padding:0; margin:0 0 15px 0;"><b>CUSTOMER :</b></p> 
                            <p style="padding:0; margin:0 0 15px 0;"><b>{{ $item->refConsignee->nick_name }}</b></p> 
                            <hr>
                            <p style="padding:0; margin:0 0 15px 0;"><b>PART NO.</b></p> 
                            <p style="padding:0; margin:0 0 15px 0;"><b>{{ substr($box_item['ref_box']['item_no_series'],0,-3) }}</b></p> 
                            <hr>
                            <table>
                                <tr>
                                    <td class="no-bl no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>QTY</b></p> </td>
                                    <td class="no-br no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>GW</b></p> </td>
                                </tr>
                                <tr>
                                    <td class="no-bl text-center"><b>{{ $box_item['qty_pcs_box'] ?? null }}</b></td>
                                    <td class="no-br text-center"><b>{{ round($box_item['ref_box']['total_gross_weight'],1) }}</b></td>
                                </tr>
                                <tr>
                                    <td class="no-bl no-bb text-center"><p style="padding:0; margin:15px 0 0 0;"><b>PCS</b></p></td>
                                    <td class="no-br no-bb text-center"><p style="padding:0; margin:15px 0 0 0;"><b>KG</b></p></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endfor
        @endforeach
    @endif --}}
        
    @endforeach 
    
</body>
