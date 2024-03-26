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

        @page {
            size: a4 landscape;
        }
    </style>
</head>

<body>
        <table style="width: 100%" cellpading="0" cellspacing="0">
            <tr>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Item Name</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Item Number</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Qty</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Lot Packing</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Packing Date</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Customer</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Case Number</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Period</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Packing</th>
                <th style="background-color: #303C9C; color: white; padding: 20px 5px; text-align: center">Box</th>
                <th rowspan="8" style="vertical-align: top !important;">
                    <table style="background-color: white; width: 100%" cellpading="0" cellspacing="0">
                        <tr style="">
                            <td style="width: 175px">
                                <img src="{{ $qrcode }}" style="width: 100%;" alt="qrcode">
                            </td>
                        </tr>
                        <tr style="">
                            <td style="text-align: center; background-color: #5BA9CF; margin-top: 10px">
                                <br>
                                <br>
                                <br>
                                <h3>CUSTOMER</h3>
                                <h1>{{ $customer }}</h1>
                                
                                <br>
                                <br>
                                <br>
                                <br>
                                <br>
                                <br>
                            </td>
                        </tr>
                    </table>
                </th>
            </tr>
            
            @for ($i = 0; $i < sizeof($data); $i++)
                @if($i % 2 == 0)
                    <tr style="background-color: white">
                @else
                    <tr style="background-color: #E0E0E0">
                @endif

                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["item_name"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["item_no"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["qty"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["lot_packing"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ date_format(date_create($data[$i]["packing_date"]), "d F y") }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["name_consignee"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["case_number"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["period"] }}</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">OK</td>
                    <td style="text-align: center; padding: 20px 5px; color: #303C9C">{{ $data[$i]["box"] }}</td>
                </tr>
            @endfor

            @for($i = sizeof($data); $i < 7; $i++)
                @if($i % 2 == 0)
                    <tr style="background-color: white">
                @else
                    <tr style="background-color: #E0E0E0">
                @endif
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                    <td style="text-align: center; padding: 20px 5px">&nbsp;</td>
                </tr>
            @endfor
            <tr style="border: 1px solid black">
                <td colspan="11" style="text-align: right; padding: 20px 5px; color: #303C9C">
                    <span style="margin-right: 10px">PACKING OK</span>
                </td>
            </tr>
            <tr style="border: 1px solid black">
                <td colspan="3" style="padding: 20px 5px; color: #303C9C">
                    <span style="margin-left: 10px">VERIFIKATOR NAME :</span>
                </td>
                <td colspan="8" style="padding: 20px 5px; color: #303C9C">
                    <span style="">PARAF :</span>
                </td>
            </tr>
        </table>
        
    </body>
</html>