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
        .flying-text {
            position: absolute;
            left: 15px;
            top: 230px;
            z-index: -1;
        }
    </style>
</head>

<body>
    <table style="margin-bottom: 20px;">
        <tr>
            <td class="no-bo" width="230">
                <P style="font-size: 24px; padding: 0 0 0 40px; margin: 0;"><b>PACKING LIST</b></P>
                <P style="font-size: 10px; padding: 0 0 0 40px; margin: 0;">LIATE DE ENPAQUELIST DE COLISAGE</P>
            </td>
        </tr>
    </table>
    
    <table>
        <tr>
            <td style="vertical-align: top;" rowspan="4" width="240">
                Messrs : <br><br><br>
                BILL TO : <br>{{ $packing_data->bill_to }}<br><br><br><br>
                SHIPPED TO : {{ $packing_data->shipped_to }}<br>
                City : {{ $packing_data->city }}<br>
                PHONE NO : {{ $packing_data->phone_no }}<br>
                FAX : {{ $packing_data->fax }}
            </td>
            <td style="vertical-align: top;">Date : {{ $packing_data->date }}&nbsp; &nbsp; &nbsp; INVOICE NO. : {{ $packing_data->invoice_no }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top;">
                SHIPPED BY : {{ $packing_data->shipped_by }}&nbsp; &nbsp; &nbsp; ETD JAKARTA : <br><br>
                <p style="padding: 0 0 0 165px; margin:0;">ETA MANAUS :</p>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top;">FROM : &nbsp; &nbsp; &nbsp; TO : {{ $packing_data->to }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top;">
                MARK AND NUMBER <br><br>
                <p style="padding: 0 0 0 110px; margin:0;">SEE ATTACHED SHEET</p> <br><br>
            </td>
        </tr>
    </table>

    <table style="margin-top: 30px;">
        <tr>
            <td style="text-align: center;" class="no-br">DESCRIPTION</td>
            <td style="text-align: center;" class="no-br no-bl">QUANTITY</td>
            <td style="text-align: center;" class="no-br no-bl">NETT WEIGHT</td>
            <td style="text-align: center;" class="no-br no-bl">GROSS WEIGHT</td>
            <td style="text-align: center;" class="no-bl">MEASUREMENT</td>
        </tr>
        <tr>
            <td style="padding: 20px 0 20px 0;" class="no-br no-bl no-bb">PRODUCTION PARTS FOR YAMAHA MOTORCYCLES</td>
            <td class="no-br no-bl no-bb"></td>
            <td class="no-br no-bl no-bb"></td>
            <td class="no-br no-bl no-bb"></td>
            <td class="no-br no-bl no-bb"></td>
        </tr>
        <tr>
            <td class="no-br no-bl no-bb"></td>
            <td class="no-br no-bl no-bb">(PCS)</td>
            <td class="no-br no-bl no-bb">Kgs</td>
            <td class="no-br no-bl no-bb">Kgs</td>
            <td class="no-br no-bl no-bb">M3</td>
        </tr>
        @foreach ($data as $key => $item)
            <tr>
                <td class="no-bo">{{ $item->description }}</td>
                <td class="no-bo" style="text-align: center;">{{ $item->qty }}</td>
                <td class="no-bo" style="text-align: center;">{{ $item->nett_weight }}</td>
                <td class="no-bo" style="text-align: center;">{{ $item->gross_weight }}</td>
                <td class="no-bo" style="text-align: right;">{{ $item->measurement }}</td>
            </tr>
        @endforeach
    </table>

    <div class="page_break"></div>

    <h2 class="text-center">PACKING LIST SHEET</h2>
    <table>
        <tr>
            <td class="no-bo">INVOICE NO</td>
            <td class="no-bo">:</td>
            <td class="no-bo" width="250">{{ $packing_data->invoice_no }}</td>
            <td class="no-bo">SHIPPED BY</td>
            <td class="no-bo">:</td>
            <td class="no-bo"></td>
        </tr>
        <tr>
            <td class="no-bo">Date</td>
            <td class="no-bo">:</td>
            <td class="no-bo">{{ $packing_data->date }}</td>
            <td class="no-bo">ETD JAKARTA </td>
            <td class="no-bo">:</td>
            <td class="no-bo"></td>
        </tr>
        <tr>
            <td class="no-bo">Container No</td>
            <td class="no-bo">:</td>
            <td class="no-bo"></td>
            <td class="no-bo">ETA MANAUS</td>
            <td class="no-bo">:</td>
            <td class="no-bo"></td>
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
            <td class="no-bt no-bl no-br">Order No. </td>
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
            <br>
            999999-9999 <br>
                <br>
            MADE IN INDONESIA <br>
            INV. No. {{ $packing_data->invoice_no }} <br>
            C/No. : 1 - {{ count($data) }}
        </p>
            
        @foreach ($data as $key => $item)
            <tr>
                <td class="no-bt"></td>
                @if ($key % 2 == 0 && $key == 0)
                    <td style='padding-bottom:5px;' class='text-center'>{{ $key+1 }}</td>
                @else
                    <td class="no-bt"></td>
                @endif
                @if (count($data) == 1)
                    <td style='padding:55px 0 55px 0;' class='text-center'>{{ $item->description }}</td>
                @elseif (count($data) == 2)
                    <td style='padding:23px;' class='text-center'>{{ $item->description }}</td>
                @elseif (count($data) == 3)
                    <td style='padding:15px;' class='text-center'>{{ $item->description }}</td>
                @elseif (count($data) == 4)
                    <td style='padding:8px;' class='text-center'>{{ $item->description }}</td>
                @else
                    <td style='padding-bottom:5px;' class='text-center'>{{ $item->description }}</td>
                @endif
                <td style='padding-bottom:5px;' class='text-center'>{{ $item->qty }}</td>
                <td style='padding-bottom:5px;' class='text-center'>{{ $item->nett_weight }}</td>
                <td style='padding-bottom:5px;' class='text-center'>{{ $item->gross_weight }}</td>
                <td style='padding-bottom:5px;' class='text-center'>{{ number_format(($item->length * $item->width * $item->height / 1000000000), 3) }}</td>
            </tr>
        @endforeach
            
        {{-- total --}}
        <tr>
            <td colspan="3" class="text-center"> TOTAL</td>
            <td class="text-center">{{ $total['qty'] }}</td>
            <td class="text-center">{{ $total['nett_weight'] }}</td>
            <td class="text-center">{{ $total['gross_weight'] }}</td>
            <td class="text-center">{{ number_format($total['measurement'], 3) }}</td>
        </tr>
    </table>

    <table style="margin-top: 20px;">
        <tr>
            <td class="no-bo" width="200px">Grand Total Number Of Cartons</td>
            <td class="no-bo" width="4">:</td>
            <td width="50px" class="text-right no-bo">{{ count($data) }}</td>
            <td class="no-bo">Cartons Boxes</td>
        </tr>
        <tr>
            <td class="no-bo" width="200px">Grand Total Qty</td>
            <td class="no-bo" width="4">:</td>
            <td width="50px" class="text-right no-bo">{{ $total['qty'] }}</td>
            <td class="no-bo">(PCS)</td>
        </tr>
        <tr>
            <td class="no-bo" width="200px">Grand Total Nett Weights</td>
            <td class="no-bo" width="4">:</td>
            <td width="50px" class="text-right no-bo">{{ $total['nett_weight'] }}</td>
            <td class="no-bo">Kgs</td>
        </tr>
        <tr>
            <td class="no-bo" width="200px">Grand Total Gross Weights</td>
            <td class="no-bo" width="4">:</td>
            <td width="50px" class="text-right no-bo">{{ $total['gross_weight'] }}</td>
            <td class="no-bo">Kgs</td>
        </tr>
        <tr>
            <td class="no-bo" width="200px">Grand Total Measurement</td>
            <td class="no-bo" width="4">:</td>
            <td width="50px" class="text-right no-bo">{{ number_format($total['measurement'], 3) }}</td>
            <td class="no-bo">M3</td>
        </tr>
    </table>
    <hr>
</body>
</html>