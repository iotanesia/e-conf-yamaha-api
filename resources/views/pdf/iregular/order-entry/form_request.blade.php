<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        #table1 td, #table2 td {
            vertical-align:top
        }

        body {
            font-size: 9pt; 
            /* font-family: 'Times New Roman', Times, serif; */
            font-family: Arial, Helvetica, sans-serif;
            /* margin-right: 4cm; */
        }
        input[type="checkbox"] {
            vertical-align: top;
        }

    </style>
</head>

<body>
    <table style="width: 100%">
        <tr>
            <td style="width: 50%">
                <strong style="font-size: 16px">PT. Yamaha Motor Parts Mfg Indonesia</strong>
            </td>
            <td rowspan="2" style="width: 50%; text-align: right;">
                <strong style="
                    font-size: 14px; 
                    color: red;
                    padding-left: 15px; 
                    padding-right: 15px; 
                    padding-top: 5px;
                    padding-bottom: 5px;
                    border: 2px solid red; 
                    border-radius: 5px">INTERNAL USE ONLY</strong>
            </td>
        </tr>
        <tr>
            <td>
                <strong style="font-size: 13px">Finance Division</strong>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: right;">
                <a style="font-size: 11px">01/FORM/REV-2/CC/2022</a>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; background-color:#275673; padding-top: 5px; padding-bottom: 5px;"><a style="color: white; font-size: 18px; font-weight: 600;"><i>FORM REQUEST FOR IRREGULAR EXPORT / IMPORT</i></a></td>
        </tr>
    </table>

    <div>
        <div style="padding: 5px">
            <span style="color: red;"><i>Please choose one</i></span>
        </div>

        <div style="padding-left: 25px; padding-right: 25px">
            <strong><i><u>Part 1 : to be filled by Requestor</u></i></strong>
            <table style="width: 100%" id="table1">
                <tr>
                    <td style="width: 2%">a. </td>
                    <td style="width: 25%">Requestor</td>
                    <td style="width: 2%">:</td>
                    <td>
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            <tr>
                                <td style="width: 45%">{{ $data->requestor }}</td>
                                <td style="width: 20%">Ext : {{ $data->ext }}</td>
                                <td style="width: 25%">Cost Center : {{ $data->cost_center }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">b. </td>
                    <td style="width: 25%">Section/Dept</td>
                    <td style="width: 2%">:</td>
                    <td>{{ $data->section }}</td>
                </tr>
                <tr>
                    <td style="width: 2%">c. </td>
                    <td style="width: 25%">Type Transaction</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countTypeTransaction = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["type_transaction"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countTypeTransaction  < sizeof($form["type_transaction"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_type_transaction == $form["type_transaction"][$countTypeTransaction]->id)
                                                    <input type="checkbox" checked> {{ $form["type_transaction"][$countTypeTransaction]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["type_transaction"][$countTypeTransaction]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countTypeTransaction = $countTypeTransaction+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">d. </td>
                    <td style="width: 25%">Commodities</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countComodities = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["comodities"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countComodities  < sizeof($form["comodities"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @php($comoditiesChecked = false)
                                                @foreach ($data->checkbox as $chk)
                                                    @if($chk->type == "comodities" && $chk->id_value == $form["comodities"][$countComodities]->id)
                                                        @php($comoditiesChecked = true)
                                                    @endif
                                                @endforeach
                                                @if($comoditiesChecked)
                                                    <input type="checkbox" checked> {{ $form["comodities"][$countComodities]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["comodities"][$countComodities]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countComodities = $countComodities+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>  
                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">e. </td>
                    <td style="width: 25%">Goods Condition</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countGoodCondition = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["good_condition"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countGoodCondition  < sizeof($form["good_condition"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @php($goodConditionChecked = false)
                                                @foreach ($data->checkbox as $chk)
                                                    @if($chk->type == "good_condition" && $chk->id_value == $form["good_condition"][$countGoodCondition]->id)
                                                        @php($goodConditionChecked = true)
                                                    @endif
                                                @endforeach
                                                @if($goodConditionChecked)
                                                    <input type="checkbox" checked> {{ $form["good_condition"][$countGoodCondition]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["good_condition"][$countGoodCondition]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countGoodCondition = $countGoodCondition+1)
                                    @endfor
                                </tr>
                            @endfor   
                        </table>                     
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">f. </td>
                    <td style="width: 25%">Goods Status</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countGoodStatus = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["good_status"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countGoodStatus  < sizeof($form["good_status"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @php($goodStatusChecked = false)
                                                @foreach ($data->checkbox as $chk)
                                                    @if($chk->type == "good_status" && $chk->id_value == $form["good_status"][$countGoodStatus]->id)
                                                        @php($goodStatusChecked = true)
                                                    @endif
                                                @endforeach
                                                @if($goodStatusChecked)
                                                    <input type="checkbox" checked> {{ $form["good_status"][$countGoodStatus]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["good_status"][$countGoodStatus]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countGoodStatus = $countGoodStatus+1)
                                    @endfor
                                </tr>
                            @endfor   
                        </table>                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%"></td>
                    <td style="width: 25%"></td>
                    <td style="width: 2%"></td>
                    <td>
                        <table cellpading="0" cellspacing="0" style="width: 100%; margin-top: 15px">
                            <tr>
                                <td style="width: 33%">
                                </td>
                                <td style="width: 33%; text-align: right">
                                    <span style="margin-right: 10px;">Actual Condition</span>
                                </td>
                                <td style="width: 33%">
                                    <div>
                                        @if ($data->actual_condition == "0-25")
                                            <input type="checkbox" checked> 0%-25%
                                        @else         
                                            <input type="checkbox"> 0%-25%
                                        @endif
                                    </div>
                                    <div>
                                        @if ($data->actual_condition == "26-50")
                                            <input type="checkbox" checked> 26%-50%
                                        @else         
                                            <input type="checkbox"> 26%-50%
                                        @endif
                                    </div>
                                    <div>
                                        @if ($data->actual_condition == "51-75")
                                            <input type="checkbox" checked> 51%-75%
                                        @else         
                                            <input type="checkbox"> 51%-75%
                                        @endif
                                    </div>
                                    <div>
                                        @if ($data->actual_condition == "76-99")
                                            <input type="checkbox" checked> 76%-99%
                                        @else         
                                            <input type="checkbox"> 76%-99%
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">g. </td>
                    <td style="width: 25%">Goods Payment</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countGoodPayment = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["good_payment"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countGoodPayment  < sizeof($form["good_payment"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_good_payment == $form["good_payment"][$countGoodPayment]->id)
                                                    <input type="checkbox" checked> {{ $form["good_payment"][$countGoodPayment]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["good_payment"][$countGoodPayment]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countGoodPayment = $countGoodPayment+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>                             
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%"></td>
                    <td style="width: 25%"></td>
                    <td style="width: 2%"></td>
                    <td>
                        <div style="margin-top: 10px;overflow-wrap: break-word;">
                            For FOC, please add the reason &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                            @if (isset($data->reason_foc))
                                <span style="border-bottom: 1px dotted black">{{ $data->reason_foc }}</span> <br>
                            @else
                                .......................................................................
                                ..................................................................................................................................
                                ..................................................................................................................................
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">h. </td>
                    <td style="width: 25%">Freight Charge</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countFreightCharge = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["freight_charge"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countFreightCharge  < sizeof($form["freight_charge"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_freight_charge == $form["freight_charge"][$countFreightCharge]->id)
                                                    <input type="checkbox" checked> {{ $form["freight_charge"][$countFreightCharge]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["freight_charge"][$countFreightCharge]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countFreightCharge = $countFreightCharge+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">i. </td>
                    <td style="width: 25%">Insurance</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countInsurance = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["insurance"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countInsurance  < sizeof($form["insurance"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_insurance == $form["insurance"][$countInsurance]->id)
                                                    <input type="checkbox" checked> {{ $form["insurance"][$countInsurance]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["insurance"][$countInsurance]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countInsurance = $countInsurance+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">j. </td>
                    <td style="width: 25%">Duty and Tax</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countDutyTax = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["duty_tax"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countDutyTax  < sizeof($form["duty_tax"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_duty_tax == $form["duty_tax"][$countDutyTax]->id)
                                                    <input type="checkbox" checked> {{ $form["duty_tax"][$countDutyTax]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["duty_tax"][$countDutyTax]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countDutyTax = $countDutyTax+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>                              
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">k. </td>
                    <td style="width: 25%">Inland Cost</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countInlandCost = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["inland_cost"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countInlandCost  < sizeof($form["inland_cost"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_inland_cost == $form["inland_cost"][$countInlandCost]->id)
                                                    <input type="checkbox" checked> {{ $form["inland_cost"][$countInlandCost]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["inland_cost"][$countInlandCost]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countInlandCost = $countInlandCost+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>                            
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">l. </td>
                    <td style="width: 25%">Shipped by</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countShippedBy = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["shipped_by"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countShippedBy  < sizeof($form["shipped_by"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_shipped == $form["shipped_by"][$countShippedBy]->id)
                                                    <input type="checkbox" checked> {{ $form["shipped_by"][$countShippedBy]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["shipped_by"][$countShippedBy]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countShippedBy = $countShippedBy+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>    
                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">m. </td>
                    <td style="width: 25%">Stuffing / Arrival Date Plan </td>
                    <td style="width: 2%">:</td>
                    <td>
                        {{ date('d/m/y', strtotime($data->stuffing_date)) }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">n. </td>
                    <td style="width: 25%">Pick Up Location</td>
                    <td style="width: 2%">:</td>
                    <td>
                        {{ $data->pick_up_location }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">o. </td>
                    <td colspan="3">Consignee / Shipper Information</td>
                </tr>
                <tr>
                    <td style="width: 2%"></td>
                    <td style="width: 25%">Name and Tittle</td>
                    <td style="width: 2%">:</td>
                    <td>
                        {{ $data->name_consignee }}                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%"></td>
                    <td style="width: 25%">Company Division</td>
                    <td style="width: 2%">:</td>
                    <td>
                        {{ $data->company_consignee }}                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%"></td>
                    <td style="width: 25%">Address</td>
                    <td style="width: 2%">:</td>
                    <td>
                        {{ $data->address_consignee }}                        
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%"></td>
                    <td style="width: 25%">Email Address</td>
                    <td style="width: 2%">:</td>
                    <td>
                        
                         <table cellpading="0" cellspacing="0" style="width: 100%">
                            <tr>
                                <td style="width: 40%">{{ $data->email_consignee }}</td>
                                <td style="width: 25%">Phone : {{ $data->phone_consignee }}</td>
                                <td style="width: 25%">Fax : {{ $data->fax_consignee }}</td>
                            </tr>
                        </table>
                                              
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">p. </td>
                    <td style="width: 25%">Description of Goods</td>
                    <td style="width: 2%">:</td>
                    <td>
                        {{ $data->description_goods }}
                    </td>
                </tr>
                
                <tr>
                    <td colspan="4">
                        <table id="table-item" border="1" cellpading="0" cellspacing="0" style="margin-top: 25px; margin-bottom: 25px; width: 100%;">
                            <tr>
                                <th style="font-size: 10px; width: 5%">NO</th>
                                <th style="font-size: 10px; width: 20%">ITEM CODE</th>
                                <th style="font-size: 10px; width: 20%">ITEM NAME</th>
                                <th style="font-size: 10px; width: 20%">PO. NUMBER</th>
                                <th style="font-size: 10px; width: 11%">NW (Gram/Pcs)</th>
                                <th style="font-size: 10px; width: 11%">GW (Kgs)</th>
                                <th style="font-size: 10px; width: 11%">MEASURE (mm)</th>
                            </tr>
                            @for ($i = 0; $i < sizeof($data->part); $i++)
                                <tr>
                                    <td style="text-align: center; padding: 3px">{{ $i+1 }}</td>
                                    <td style="padding: 3px">{{ $data->part[$i]->item_code }}</td>
                                    <td style="padding: 3px">{{ $data->part[$i]->item_name }}</td>
                                    <td style="padding: 3px">{{ $data->part[$i]->order_no }}</td>
                                    <td style="padding: 3px">{{ $data->part[$i]->net_weight }}</td>
                                    <td style="padding: 3px">{{ $data->part[$i]->gross_weight }}</td>
                                    <td style="padding: 3px">{{ $data->part[$i]->measurement }}</td>
                                </tr>
                            @endfor
                        </table>
                    </td>
                </tr>

                
                <tr>
                    <td style="width: 2%">q. </td>
                    <td style="width: 25%">Attachment Documents</td>
                    <td style="width: 2%">:</td>
                    <td>
                        <table cellpading="0" cellspacing="0" style="width: 100%;">
                            <tr>
                                <td style="width: 25%">
                                    <div><strong>Commercial Parts</strong></div>
                                    @foreach ($doc as $item)
                                        @if($item->id_doc_type == 1)
                                            <div>
                                                @php($checkedDoc = false)
                                                @foreach ($data->doc as $data_doc)
                                                    @if ($data_doc->id_doc == $item->id)
                                                        @php($checkedDoc = true)
                                                    @endif
                                                @endforeach
                                                @if($checkedDoc)
                                                    <input type="checkbox" checked> {{ $item->name }}
                                                @else
                                                    <input type="checkbox"> {{ $item->name }}
                                                @endif               
                                            </div>
                                        @endif
                                    @endforeach

                                </td>
                                <td style="width: 25%">
                                    <div><strong>Replacement Parts</strong></div>
                                    @foreach ($doc as $item)
                                        @if($item->id_doc_type == 2)
                                            <div>
                                                @php($checkedDoc = false)
                                                @foreach ($data->doc as $data_doc)
                                                    @if ($data_doc->id_doc == $item->id)
                                                        @php($checkedDoc = true)
                                                    @endif
                                                @endforeach
                                                @if($checkedDoc)
                                                    <input type="checkbox" checked> {{ $item->name }}
                                                @else
                                                    <input type="checkbox"> {{ $item->name }}
                                                @endif               
                                            </div>
                                        @endif
                                    @endforeach
                                </td>
                                <td style="width: 25%">
                                    <div><strong>Sample Parts</strong></div>
                                    @foreach ($doc as $item)
                                        @if($item->id_doc_type == 3)
                                            <div>
                                                @php($checkedDoc = false)
                                                @foreach ($data->doc as $data_doc)
                                                    @if ($data_doc->id_doc == $item->id)
                                                        @php($checkedDoc = true)
                                                    @endif
                                                @endforeach
                                                @if($checkedDoc)
                                                    <input type="checkbox" checked> {{ $item->name }}
                                                @else
                                                    <input type="checkbox"> {{ $item->name }}
                                                @endif               
                                            </div>
                                        @endif
                                    @endforeach
                                </td>
                                <td style="width: 25%">
                                    <div><strong>Others</strong></div>
                                    @foreach ($doc as $item)
                                        @if($item->id_doc_type == 4)
                                            <div>
                                                @php($checkedDoc = false)
                                                @foreach ($data->doc as $data_doc)
                                                    @if ($data_doc->id_doc == $item->id)
                                                        @php($checkedDoc = true)
                                                    @endif
                                                @endforeach
                                                @if($checkedDoc)
                                                    <input type="checkbox" checked> {{ $item->name }}
                                                @else
                                                    <input type="checkbox"> {{ $item->name }}
                                                @endif               
                                            </div>
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                
                <tr>
                    <td colspan="4">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="4"><i><u>must be filled:</u></i></td>
                    </td>
                </tr>
                
                <tr>
                    <td style="width: 2%"><input type="checkbox" checked></td>
                    <td colspan="3"><span style="color: red"><i>I have confirmed compliance with "Control Standard of Enviromentally Hazardous Substances YGK-A-119"</i></span></td>
                </tr>

                
                <tr>
                    <td style="width: 2%"></td>
                    <td colspan="3"><i>Hereby we declared that above data is correct</i></td>
                </tr>

                
                <tr>
                    <td style="width: 2%"></td>
                    <td colspan="3">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 55%">
                                    <table style="width: 100%" border="1" cellpading="0" cellspacing="0">
                                        <tr>
                                            <td colspan="4" style="text-align: center; font-size: 9px">REQUESTOR</td>
                                        </tr>
                                        <tr>
                                            <td><br><br><br><br><br></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: center; width: 25%; font-size: 9px">USER</td>
                                            <td style="text-align: center; width: 25%; font-size: 9px">SUPERVISOR</td>
                                            <td style="text-align: center; width: 25%; font-size: 9px">MANAGER</td>
                                            <td style="text-align: center; width: 25%; font-size: 9px">DIRECTOR***</td>
                                        </tr>

                                    </table>
                                </td>
                                <td style="width: 5%">

                                </td>
                                <td style="width: 40%">
                                    <table style="width: 100%" border="1" cellpading="0" cellspacing="0">
                                        <tr>
                                            <td colspan="3" style="text-align: center; font-size: 9px">CUSTOM CLEARANCE</td>
                                        </tr>
                                        <tr>
                                            <td><br><br><br><br><br></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: center; width: 33%; font-size: 9px">RECEIVED</td>
                                            <td style="text-align: center; width: 33%; font-size: 9px">CHECKED</td>
                                            <td style="text-align: center; width: 33%; font-size: 9px">APPROVED</td>
                                        </tr>

                                    </table>
                                </td>

                            </tr>
                            
                        </table>
                    </td>
                </tr>

                
                <tr>
                    <td colspan="4"><i>***Approval by Director is needed for Free of Charge shipment (for Export only)</i></td>
                </tr>

                <tr>
                    <td colspan="4"><br></td>
                </tr>             

            </table>
        </div>

        <div style="border-top: 1px solid black; border-bottom: 1px solid black; padding:1px">
        </div> 

        <div style="color: red; font-size: 9px; text-align: center;margin-top: 5px;"><strong><i>#For Export : Please submit this form with complete supporting document to CC within 3 days before stuffing date</i></strong></div>
        <div style="color: red; font-size: 9px; text-align: center; margin-top: 5px;"><strong><i>##For Import : Please submit this form with complete supporting document to CC within 7 days before ETD from Departure Port</i></strong></div>
        
        <div style="border-top: 1px solid black; border-bottom: 1px solid black; padding: 1px;margin-top: 5px;">
        </div> 

        
        <div style="padding: 5px">
            <span style="color: red;"><i>Please choose one</i></span>
        </div>

        <div style="padding-left: 25px; padding-right: 25px">
            <strong><i><u>Part 2 : to be filled by Custom Clearance Staff</u></i></strong>
            <table style="width: 100%" id="table2">
                <tr>
                    <td style="width: 2%">a. </td>
                    <td style="width: 25%">Request No. / Invoice No. </td>
                    <td style="width: 2%">:</td>
                    <td>
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            <tr>
                                @if($role == "dc")
                                    <td style="width: 30%">..............................</td>
                                    <td style="width: 70%">Receive Date : .......................... (DD/MM/YY)</td>
                                @elseif($role == "cc")
                                    <td style="width: 30%">{{ $data->invoice_no }}</td>
                                    <td style="width: 70%">Receive Date : {{ date('d/m/y', strtotime($data->receive_date)) }} </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">b. </td>
                    <td style="width: 25%">Entity Site</td>
                    <td style="width: 2%">:</td>
                    <td>
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            <tr>
                                @if($role == "dc")
                                    <td colspan="3">.........................................................................................................................................</td>
                                @elseif($role == "cc")
                                    <td colspan="3">{{ $data->entity_site }}</td>
                                @endif
                            </tr>
                                @if($role == "dc")
                                    <td style="width: 40%">Delivery Site : ..............................</td>
                                    <td style="width: 35%">Currency : ..........................</td>
                                    <td style="width: 25%">Rate : .......................</td>
                                @elseif($role == "cc")
                                    <td style="width: 40%">Delivery Site : {{ $data->delivery_site }}</td>
                                    <td style="width: 35%">Currency : {{ $data->currency }}</td>
                                    <td style="width: 25%">Rate : {{ $data->rate }}</td>
                                @endif
                            <tr>

                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">c. </td>
                    <td style="width: 25%">Incoterms</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countIncoterm = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["incoterms"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countIncoterm  < sizeof($form["incoterms"]))
                                            @php($incotermChecked = false)
                                            @foreach ($data->checkbox as $chk)
                                                @if($chk->type == "incoterms" && $chk->id_value == $form["incoterms"][$countIncoterm]->id)
                                                    @php($incotermChecked = true)
                                                @endif
                                            @endforeach
                                            <td style="width: 33%;" class="checkbox-container">
                                            
                                                @if($incotermChecked && $role == "cc")
                                                    <input type="checkbox" checked> {{ $form["incoterms"][$countIncoterm]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["incoterms"][$countIncoterm]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countIncoterm = $countIncoterm+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>         
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">d. </td>
                    <td style="width: 25%">Freight</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countFreight = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["freight"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countFreight  < sizeof($form["freight"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                                @if($data->id_freight == $form["freight"][$countFreight]->id && $role == "cc")
                                                    <input type="checkbox" checked> {{ $form["freight"][$countFreight]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["freight"][$countFreight]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countFreight = $countFreight+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table>    
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">e. </td>
                    <td style="width: 25%">Goods Criteria<br>(for import)</td>
                    <td style="width: 2%">:</td>
                    <td>
                        @php($countGoodCriteria = 0)
                        <table cellpading="0" cellspacing="0" style="width: 100%">
                            @for ($i = 0; $i < ceil(sizeof($form["good_criteria"])/3); $i++)
                                <tr>
                                    @for ($j = 0; $j < 3; $j++)
                                        @if($countGoodCriteria  < sizeof($form["good_criteria"]))
                                            <td style="width: 33%;" class="checkbox-container">
                                            
                                                @if($data->id_good_criteria == $form["good_criteria"][$countGoodCriteria]->id && $role == "cc")
                                                    <input type="checkbox" checked> {{ $form["good_criteria"][$countGoodCriteria]->name }}
                                                @else
                                                    <input type="checkbox"> {{ $form["good_criteria"][$countGoodCriteria]->name }}
                                                @endif
                                            </td>
                                        @else
                                            <td  style="width: 33%"></td>
                                        @endif
                                        @php($countGoodCriteria = $countGoodCriteria+1)
                                    @endfor
                                </tr>
                            @endfor
                        </table> 
                    </td>
                </tr>
                <tr>
                    <td style="width: 2%">f. </td>
                    <td style="width: 25%">ETD / ETA Date</td>
                    <td style="width: 2%">:</td>
                    @if($role == "dc")
                        <td> ................................................... (DD/MM/YY)</td>
                    @elseif($role == "cc")
                        <td> {{ date('d/m/y', strtotime($data->etd_date)) }}</td>
                    @endif
                </tr>
            </table>
        </div>
    </div>

    <div>
        <div style="padding: 5px">
            <span><i>Controled by: Custom Clearance</i></span>
        </div>
    </div>
</body>
