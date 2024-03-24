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
        @for ($i=1; $i<=2; $i++)

        @if ($i == 1)
            <table style="margin-bottom: 45px;">
        @else
            <table>
        @endif
                <tr>
                    <td class="text-center" style="font-size: 40px; font-weight: 500; vertical-align=top; padding: 20px;">
                        <p style="margin:0 0 15px 0; padding:0;"><b>YAMAHA</b></p>
                        <p style="margin:0 0 15px 0; padding:0;"><b>{{ $part[$key]->order_no ?? null }}</b></p>
                        <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->model_code }}</b></p>
                        <p style="margin:0 0 15px 0; padding:0;"><b>{{ $item->destination }}</b></p>
                        <p style="margin:0 0 15px 0; padding:0;"><b>MADE IN INDONESIA</b></p>
                        <p style="margin:0 0 15px 0; padding:0;"><b>INV.No. {{ $invoice_no }}</b></p>
                        <p style="margin:0 0 15px 0; padding:0;"><b>C/No. : {{ $key+1 }}</b></p>
                    </td>
                    <td class="text-center" style="font-size: 25px; font-weight: 500; vertical-align=top;">
                        <p style="padding:0; margin: 0 0 10px 0;"><b>CUSTOMER :</b></p>
                        <b>{{ $entity_site }}</b>
                        <hr>
                        <p style="padding:0; margin: 0 0 10px 0;"><b>PART NO.</b></p>
                        <b>{{ $item->item_no }}</b>
                        <hr>
                        <table>
                            <tr>
                                <td class="no-bl no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>QTY</b></p> </td>
                                <td class="no-br no-bt no-bb text-center"><p style="padding:0; margin:0 0 20px 0;"><b>GW</b></p> </td>
                            </tr>
                            <tr>
                                <td class="no-bl text-center"><b>{{ $item->qty }}</b></td>
                                <td class="no-br text-center"><b>{{ $item->gross_weight }}</b></td>
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
    
</body>
