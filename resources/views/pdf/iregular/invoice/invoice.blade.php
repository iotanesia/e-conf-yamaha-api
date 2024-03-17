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
                    <P style="font-size: 24px; padding: 0 0 0 60px; margin: 0;"><b>INVOICE</b></P>
                    <P style="font-size: 10px; padding: 0 0 0 60px; margin: 0;">FACTURA/FACTURE</P>
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
                    BILL TO : <br>{{ $invoice_data->bill_to }}<br><br><br><br>
                    SHIPPED TO : {{ $invoice_data->shipped_to }}<br>
                    City : {{ $invoice_data->city }}<br>
                    LOGISTIC DIVISION <br>
                    PHONE NO : {{ $invoice_data->phone_no }}<br>
                    FAX : {{ $invoice_data->fax }}
                </td>
                <td style="vertical-align: top;">Date : {{ $invoice_data->date }}&nbsp; &nbsp; &nbsp; INVOICE NO. : {{ $invoice_data->invoice_no }}</td>
            </tr>
            <tr>
                <td style="vertical-align: top;">
                    SHIPPED BY : {{ $invoice_data->shipped_by }}&nbsp; &nbsp; &nbsp; ETD JAKARTA : <br><br>
                    <p style="padding: 0 0 0 165px; margin:0;">ETA MANAUS :</p>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top;">FROM : &nbsp; &nbsp; &nbsp; TO : {{ $invoice_data->to }}</td>
            </tr>
            <tr>
                <td style="vertical-align: top;">
                    TRADING TERM : {{ $invoice_data->trading_term }}<br><br><br>
                    PAYMENT : {{ $invoice_data->payment }} <br><br>
                </td>
            </tr>
        </table>

        <table style="margin-top: 30px;">
            <tr>
                <td style="text-align: center;">Order No.</td>
                <td style="text-align: center;">No. of Packages</td>
                <td style="text-align: center;">Description</td>
                <td style="text-align: center;">Quantity <br> (PCS)</td>
                <td style="text-align: center;">Unit Price <br> (USD)</td>
                <td style="text-align: center;">Amount <br> (USD)</td>
            </tr>
            <tr>
                <td class="no-br no-bl no-bb"></td>
                <td style="padding: 20px 0 20px 0;" class="no-br no-bl no-bb" colspan="2">PRODUCTION PARTS FOR YAMAHA MOTORCYCLES</td>
                <td class="no-br no-bl no-bb"></td>
                <td class="no-br no-bl no-bb"></td>
                <td class="no-br no-bl no-bb"></td>
            </tr>
            @foreach ($data as $key => $item)
                <tr>
                    <td class="no-br no-bl no-bt">{{ $key == 0 ? $item->order_no : null }}</td>
                    <td class="no-br no-bl no-bt">{{ $item->no_package }} {{ $item->refDeliveryPlanInvoice->type_package ?? null }}</td>
                    <td class="no-br no-bl no-bt">{{ $item->description }}</td>
                    <td class="no-br no-bl no-bt">{{ $item->qty }}</td>
                    <td class="no-br no-bl no-bt">{{ $item->unit_price }}</td>
                    <td class="no-br no-bl no-bt">{{ $item->amount }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="no-br no-bl">Total</td>
                <td class="no-br no-bl" colspan="2">{{ $total['packages'] }}</td>
                <td class="no-br no-bl">{{ $total['qty'] }}</td>
                <td class="no-br no-bl">{{ number_format($total['unit_price'], 2) }}</td>
                <td class="no-br no-bl">{{ number_format($total['amount'], 2) }}</td>
            </tr>
        </table>

        <div style="position: absolute; right: 0; text-align: center; margin-top: 30px;">
            <p style="margin-bottom: 80px;">PT. YAMAHA MOTOR PARTS MANUFACTURING INDONESIA</p>
            <p><u>TRI YUNARTI</u></p>
            <p>DEPUTY DIRECTOR - CORPORATE</p>
        </div>
    </body>
</html>