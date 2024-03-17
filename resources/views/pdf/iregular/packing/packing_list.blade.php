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
        <table style="margin-bottom: 50px;">
            <tr>
                <td class="no-bo" width="230">
                    <P style="font-size: 24px; padding: 0 0 0 40px; margin: 0;"><b>PACKING LIST</b></P>
                    <P style="font-size: 10px; padding: 0 0 0 40px; margin: 0;">LIATE DE ENPAQUELIST DE COLISAGE</P>
                    <P style="font-size: 14px; padding: 0; margin: 0;">CUSTOMER CODE :</P>
                    <P style="font-size: 10px; padding: 0; margin: 0;">CLIENTE CODIGO/CLIENT CODE</P>
                </td>
                <td class="no-bo">
                    <p style="font-size: 13px; padding: 0; margin: 0;"><b>PT. YAMAHA MOTOR PARTS MANUFACTURING INDONESIA</b></p>
                    Jl Permata Raya Lot F2 & F6 <br>
                    Kawasan Industri KIIC, Karawang 41361, <br>
                    PO BOX 157, West Java - Indonesia. <br>
                    Phone: +6221 890 4581. Fax: +6221 890 4541
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
    </body>
</html>