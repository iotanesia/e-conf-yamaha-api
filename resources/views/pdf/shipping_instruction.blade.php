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
                <td style="vertical-align: top;"><u>Consignee:</u> 
                    <br> {{ $data->refMstConsignee->name }} 
                    <br> {{ $data->refMstConsignee->address1 }} 
                    <br> {{ $data->refMstConsignee->address2 }} 
                    <br> {{ $data->refMstConsignee->tel }} 
                    <br> {{ $data->refMstConsignee->fax }} 
                </td>
                <td style="vertical-align: top;"><u>Notify Party:</u> <br> {{$data->notify_part}}</td>
            </tr>
        </table>
        <br>
        <table>
            <tr>
                <td class="no-bo"><b><i><u>SHIPMENT INFORMATION</u></i></b></td>
                <td class="no-bo"></td>
            </tr>
            <table>
                <tr>
                    <td width="80" class="no-bo">Stuffing Date </td>
                    <td class="no-bo" colspan="2"> : {{$data->etd_wh}}</td>
                </tr>
                <tr>
                    <td class="no-bo">Shipped By </td>
                    <td class="no-bo" colspan="2"> : {{$data->shipped_by}}</td>
                </tr>
                <tr>
                    <td class="no-bo">CONTAINER </td>
                    <td class="no-bo"> : {{$data->container_count}} x {{ $data->container_value }}</td>
                    <td width="80" class="no-bo">DO No</td>
                    <td class="no-bo"> : {{$data->do_no}}</td>
                </tr>
                </br>
                <tr>
                    <td class="no-bo">Port of Loading </td>
                    <td class="no-bo"> : {{$data->pol}}</td>
                    <td class="no-bo">Feeder Vessel</td>
                    <td class="no-bo"> : {{$data->feeder_vessel}}</td>
                </tr>
                <tr>
                    <td class="no-bo">Via </td>
                    <td class="no-bo"> : {{$data->via}}</td>
                    <td class="no-bo">Connecting Vessel</td>
                    <td class="no-bo"> : {{$data->connecting_vessel}}</td>
                </tr>
                <tr>
                    <td class="no-bo">Port of Destination </td>
                    <td class="no-bo"> : {{$data->pod}}</td>
                    <td class="no-bo"></td>
                    <td class="no-bo"></td>
                </tr>
                <tr>
                    <td class="no-bo"></td>
                    <td class="no-bo"></td>
                    <td class="no-bo">ETD Jakarta</td>
                    <td class="no-bo"> : {{$data->etd_jkt}}</td>
                </tr>
                <tr>
                    <td class="no-bo">Freight Charge</td>
                    <td class="no-bo"> : {{$data->freight_charge}}</td>
                    <td class="no-bo"></td>
                    <td class="no-bo"></td>
                </tr>
                <tr>
                    <td class="no-bo">Incoterm</td>
                    <td class="no-bo"> : {{$data->incoterm}}</td>
                    <td class="no-bo">ETA Destination</td>
                    <td class="no-bo"> : {{$data->eta_destination}}</td>
                </tr>

            </table>
            </br>
            <table>
                <tr>
                    <td class="no-bo" colspan="4"><i><b><u>BL Information</u></b></i></td>
                </tr>
                <tr>
                    <td class="no-bo" colspan="4">Description of Goods :</td>
                </tr>
                <tr>
                    <td class="no-bo" colspan="2">CARTON BOXES &nbsp; &nbsp; {{$data->description_of_goods_2}}</td>
                    <td class="no-bo" colspan="2">OF PRODUCTION PARTS FOR YAMAHA OUTBOARD MOTORS</td>
                </tr>
                <tr>
                    <td class="no-bo">Carton Box Qty</td>
                    <td class="no-bo"> : {{$data->description_of_goods_1}}</td>
                    <td class="no-bo" width="80">Carton Boxes</td>
                    <td class="no-bo"></td>
                    <td class="no-bo"></td>
                </tr>
                <tr>
                    <td class="no-bo">Net Weight</td>
                    <td class="no-bo"> : {{round($data->net_weight,2)}}</td>
                    <td class="no-bo">Kgs</td>
                    <td class="no-bo">PEB No.</td>
                    <td class="no-bo"> : {{$data->peb}}</td>
                </tr>
                <tr>
                    <td class="no-bo">Gross Weight </td>
                    <td class="no-bo"> : {{round($data->gross_weight,2)}}</td>
                    <td class="no-bo">Kgs</td>
                    <td class="no-bo">NOPEN</td>
                    <td class="no-bo"> : {{$data->no_open}}</td>
                </tr>
                <tr>
                    <td class="no-bo">Measurement </td>
                    <td class="no-bo"> : {{round($data->measurement,2)}}</td>
                    <td class="no-bo">Kgs</td>
                    <td class="no-bo">Container//Seal No.//Qty//GW//M3</td>
                    <td class="no-bo"> : {{$data->seal_no}}</td>
                </tr>
                <tr>
                    <td class="no-bo">B/L </td>
                    <td class="no-bo" colspan="2"> : {{$data->bl}}</td>
                    <td class="no-bo">HS CODE</td>
                    <td class="no-bo"> : {{$data->hs_code}}</td>
                </tr>
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
                <td class="text-center"></td>
                <td class="text-center"></td>
            </tr>
        </table>
    </body>
</html>