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
    </style>
</head>

<body>
        <table>
            <tr>
                <td class="text-center no-bo" style="font-size: 20px;" colspan="4"><b>SHIPPING INSTRUCTION</b> <br> <p style="font-size: 12px; padding: 0; margin: 0;">PT Yamaha Motor Parts Manufacturing Indonesia</p></td>
            </tr>
            <tr>
                <td class="no-bo" style="padding: 0; margin: 0;" width="10">To:</td>
                <td class="no-bo" style="padding: 0; margin: 0;" width="100">{{$data->to}}</td>
                <td class="no-bo" style="padding: 0; margin: 0;" width="10">CC</td>
                <td class="no-bo" style="padding: 0; margin: 0;" width="100">{{$data->cc}}</td>
            </tr>
            <tr>
                <td class="no-bo" style="padding: 0; margin: 0;">Attn:</td>
                <td class="no-bo" style="padding: 0; margin: 0;">{{$data->attn_to}}</td>
                <td class="no-bo" style="padding: 0; margin: 0;">Attn</td>
                <td class="no-bo" style="padding: 0; margin: 0;">{{$data->attn_cc}}</td>
            </tr>
        </table>
        
        <table style="margin-top: 15px;">
            <tr>
                <td style="padding: 3px;" class="no-bo">Please arrange our Shipment as per below Details</td>
                <td style="padding: 3px;" class="no-bo text-right">Date : <b>{{$data->instruction_date}}</b></td>
            </tr>
        </table>
        <table>
            <tr>
                <td style="vertical-align: top;" rowspan="2" width="240"><u>Shipper :</u> <br> {{$data->shipper}}</td>
                <td style="vertical-align: top;"><u>Si Number :</u> <br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;{{$data->si_number}}</td>
            </tr>
            <tr>
                <td style="vertical-align: top;"><u>Invoice No :</u> <br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;{{$data->invoice_no}}</td>
            </tr>
            <tr>
                <td style="vertical-align: top;"><u>Consignee:</u> 
                    <br> {{ $data->refMstConsignee->name }} 
                    <br> {{ $data->refMstConsignee->address1 }} 
                    <br> {{ $data->refMstConsignee->address2 }} 
                    <br> {{ $data->refMstConsignee->tel }} 
                    <br> {{ $data->refMstConsignee->fax }} 
                </td>
                <td style="vertical-align: top;"><u>Notify Part:</u> <br> {{$data->notify_part}}</td>
            </tr>
        </table>
        <table class="no-bt no-bb">
            <tr>
                <td>
                    <b><i><u>SHIPMENT INFORMATION</u></i></b>
                </td>
            </tr>
        </table>
        <table>
            <table style="border: 1px solid #000; border-top:hidden;">
                <table>
                    <tr>
                        <td width="80" class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Stuffing Date </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;" colspan="2"> : <b>{{$data->etd_wh}}</b></td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Shipped By </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;" colspan="2"> : {{$data->shipped_by}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">CONTAINER </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->description_of_goods_1}} x {{ $data->container_value }}</td>
                        <td width="80" class="no-bo" style="padding: 0 0 0 5px; margin: 0;">DO No</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->do_no}}</td>
                    </tr>
                    </br>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Port of Loading </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->pol ?? $data->port_of_loading}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Feeder Vessel</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->feeder_vessel}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Via </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->via}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Connecting Vessel</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->connecting_vessel}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Port of Destination </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->pod ?? $data->port_of_discharge}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">ETD Jakarta</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->etd_jkt}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Freight Charge</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->freight_charge}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Incoterm</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->incoterm}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">ETA Destination</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->eta_destination}}</td>
                    </tr>
    
                </table>
            </table>
            
            <table style="border: 1px solid #000; border-top:hidden; border-bottom:hidden;">
                <table>
                    <tr>
                        <td class="no-bo" colspan="4"><i><b><u>BL Information</u></b></i></td>
                    </tr>
                    <tr>
                        <td class="no-bo" colspan="4">Description of Goods :</td>
                    </tr>
                </table>
            </table>

            <table style="border: 1px solid #000; border-top:hidden; border-bottom:hidden;">
                <table>
                    <tr>
                        <td class="no-bo" colspan="2"><b>{{$data->description_of_goods_1}} {{$data->description_of_goods_1_detail}}</b> &nbsp; &nbsp; <b>{{$data->description_of_goods_2}}</b></td>
                        <td class="no-bo" colspan="2"><b>{{$data->description_of_goods_2_detail}}</b></td>
                        {{-- <td class="no-bo" colspan="2">CARTON BOXES &nbsp; &nbsp; {{$data->description_of_goods_2}}</td>
                        <td class="no-bo" colspan="2">OF PRODUCTION PARTS FOR YAMAHA OUTBOARD MOTORS</td> --}}
                    </tr>
                </table>
            </table>

            <table style="border: 1px solid #000; border-top:hidden;">
                <table>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Carton Box Qty</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->description_of_goods_1}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;" width="80">Carton Boxes</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"></td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Net Weight</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{number_format($data->net_weight,2)}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Kgs</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">PEB No.</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->peb}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Gross Weight </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{number_format($data->gross_weight,2)}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Kgs</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">NOPEN</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->no_open}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Measurement </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{number_format($data->measurement,3)}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Kgs</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">Container//Seal No.//Qty//GW//M3</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->seal_no}}</td>
                    </tr>
                    <tr>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">B/L </td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;" colspan="2"> : {{$data->bl}}</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;">HS CODE</td>
                        <td class="no-bo" style="padding: 0 0 0 5px; margin: 0;"> : {{$data->hs_code}}</td>
                    </tr>
                </table>
            </table>

            <table class="no-bt no-bb">
                <tr>
                    <td>
                        <b>CASE MARKS</b>
                    </td>
                </tr>
            </table>
            <table>
                <table style="border: 1px solid #000; border-top:hidden;">
                    <table style="padding-right: 570px"> 
                        @foreach ($actual_container as $key => $item)
                            @foreach ($box as $jml => $box_jml)
                                @foreach ($box[$jml] as $box_item)
                                    <tr>
                                        <td width="80" class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">YAMAHA</td>
                                    </tr>
                                    <tr>
                                        <td class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">{{ $item->manyFixedQuantityConfirmation[0]->order_no ?? null }}</td>
                                    </tr>
                                    <tr>
                                        <td class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">999999-9999</td>
                                    </tr>
                                    <tr>
                                        <td class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">{{ $item->refPartOfDischarge->port ?? null }}</td>
                                    </tr>
                                    <tr>
                                        <td class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">MADE IN INDONESIA</td>
                                    </tr>
                                    <tr>
                                        <td class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">INV. No. {{ $item->no_packaging }}</td>
                                    </tr>
                                    <tr>
                                        <td class="no-bo text-center" style="padding: 0 0 0 5px; margin: 0;">C/No. : {{ $loop->iteration }}</td>
                                    </tr>
                                @break
                                @endforeach
                            @break
                            @endforeach
                        @break
                        @endforeach 
                        <br>
                    </table>
                </table>
            
            {{-- <table>
                <tr>
                    <td>CASE MARKS</td>
                    @foreach ($actual_container as $key => $item)
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
                                            C/No. : {{ $loop->iteration }}
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
                </tr>
            </table> --}}
        </table>
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
                <td class="text-center">{{ $data->issued }}</td>
                <td class="text-center">{{ $data->checked }}</td>
                <td class="text-center">{{ $data->approved }}</td>
            </tr>
        </table>
    </body>
</html>