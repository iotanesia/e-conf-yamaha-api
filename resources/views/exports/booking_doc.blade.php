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
        tr:nth-child(2) td.first-row{
            padding-top: 2px;
        }
    </style>
</head>

<body>
    @foreach ($data as $key => $item)
        <table style="margin-top: 10px; margin-bottom: 30px;">
            <thead>
                <tr>
                    <th class="text-center" rowspan="2"> CUSTOMER</th>
                    <th class="text-center" rowspan="2"> Stuffing Plan</th>
                    <th class="text-center" rowspan="2"> ETD JKT <br> Plan</th>
                    <th class="text-center" colspan="4"> Booking Plan</th>
                    <th class="text-center" colspan="2"> Booking Confirmed</th>
                    <th class="text-center" colspan="2"> LSP</th>
                </tr>
                <tr>
                    <th class="text-center">FCL</th>
                    <th class="text-center">LCL</th>
                    <th class="text-center">20'</th>
                    <th class="text-center">40'/40HC</th>
                    <th class="text-center">20'</th>
                    <th class="text-center">40'/40HC</th>
                    <th class="text-center">Booking No.</th>
                    <th class="text-center">Target Vessel</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:0px; border-bottom:0px;" class="text-center" rowspan="{{ count($item['etd_ypmi']) + 1 }}">{{ $item['customer'] }}</td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                </tr>
                
                @foreach ($item['etd_ypmi'] as $key => $val)
                    <tr>
                        <td style="border-top:0px;" class="text-center first-row">{{ $item['etd_ypmi'][$key] }}</td>
                        <td style="border-top:0px;" class="text-center first-row">{{ $item['etd_jkt'][$key] }}</td>
                        <td style="border-top:0px;" class="text-center first-row">
                            @if ($item['booking_plan']['fcl_lcl'][$key] == 'SEA FCL')
                                x
                            @endif
                        </td>
                        <td style="border-top:0px;" class="text-center first-row">{{ str_contains($item['booking_plan']['fcl_lcl'][$key], 'SEA LCL') ? 'x' : '' }}</td>
                        <td style="border-top:0px;" class="text-center first-row"></td>
                        <td style="border-top:0px;" class="text-center first-row">{{ $item['booking_plan']['hc40'] }}</td>
                        <td style="border-top:0px;" class="text-center first-row"></td>
                        <td style="border-top:0px;" class="text-center first-row"></td>
                        <td style="border-top:0px;" class="text-center first-row"></td>
                        <td style="border-top:0px;" class="text-center first-row">{{ $item['lsp']['target_vessel'][$key] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="text-center"> Summary</td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center">{{ $item['booking_plan']['hc40'] }}</td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                </tr>
            </tbody>
        </table>    
    @endforeach
    
    <table style="margin-top: 50px; padding-left: 350px">
        <tr>
            <td style="border-bottom: 0px;" class="no-bt no-bl no-br">{{ ucfirst(strtolower($data[0]['city'])) }}, {{ $data[0]['date']->format('Y-m-d') }}</td>
            <td style="border-bottom: 0px;" class="no-bt no-bl no-br"></td>
            <td style="border-bottom: 0px;" class="no-bt no-bl no-br"></td>
        </tr>
        <table>
            <tr>
                <td class="text-center">Issue</td>
                <td class="text-center">Checked</td>
                <td class="text-center">Approved</td>
            </tr>
            <tr>
                <td class="text-center" style="height:50pt; width:50pt;"></td>
                <td class="text-center" style="height:50pt; width:50pt;"></td>
                <td class="text-center" style="height:50pt; width:50pt;"></td>
            </tr>
        
            <tr>
                <td class="text-center">{{ $data[0]['issued'] }}</td>
                <td class="text-center">{{ $data[0]['checked'] }}</td>
                <td class="text-center">{{ $data[0]['approved'] }}</td>
            </tr>
        </table>
    </table>
</body>
