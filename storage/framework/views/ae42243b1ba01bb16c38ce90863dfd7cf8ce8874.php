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
            color: red;
        }
    </style>
</head>

<body>
    <h4 class="text-center">PACKING LIST SHEET</h4>

    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <table>
            <tr>
                <td class="no-bo">INVOICE NO</td>
                <td class="no-bo">:</td>
                <td class="no-bo"><?php echo e($item->no_packaging); ?></td>
                <td class="no-bo">SHIPPED BY</td>
                <td class="no-bo">:</td>
                <td class="no-bo"><?php echo e($item->manyFixedActualContainerCreation[0]->refMstLsp->name ?? null); ?></td>
            </tr>
            <tr>
                <td class="no-bo">Date</td>
                <td class="no-bo">:</td>
                <td class="no-bo"><?php echo e($item->created_at->format('d F Y')); ?></td>
                <td class="no-bo">ETD JAKARTA </td>
                <td class="no-bo">:</td>
                <td class="no-bo"><?php echo e(date('d F Y', strtotime($item->etd_jkt))); ?></td>
            </tr>
            <tr>
                <td class="no-bo">Container No</td>
                <td class="no-bo">:</td>
                <td class="no-bo"><?php echo e(count($data)); ?> (<?php echo e(count($item->manyFixedActualContainerCreation) !== 0 ? ($item->manyFixedActualContainerCreation[0]->refMstContainer->container_type) : null); ?>)</td>
                <td class="no-bo">ETD <?php echo e($item->refPartOfDischarge->port ?? null); ?></td>
                <td class="no-bo">:</td>
                <td class="no-bo"><?php echo e(date('d F Y', strtotime($item->etd_ypmi))); ?></td>
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
                <td class="no-bt no-bl no-br">Order No. <?php echo e($item->manyFixedQuantityConfirmation[0]->order_no ?? null); ?></td>
                <td class="no-bt no-bl no-br" colspan="6"></td>
            </tr>
            <tr>
                <td class="text-center"> Case Mark and Number</td>
                <td class="text-center"> Package Number</td>
                <td class="text-center"> Parts Number</td>
                <td class="text-center"> Qty <br> (PCS)</td>
                <td class="text-center"> Nett. W <br> (Kgs)</td>
                <td class="text-center"> Gross. W <br> (Kgs)</td>
                <td class="text-center"> Meas. <br> (M3) </td>
            </tr>
            
                <tr>
                    
                    <td class="text-center" style="vertical-align: top;" rowspan="<?php echo e(count($box) + 1); ?>" width="140">
                        YAMAHA <br>
                        <?php echo e($item->manyFixedQuantityConfirmation[0]->order_no ?? null); ?>  <br>
                        999999-9999 <br>
                        <?php echo e($item->refPartOfDischarge->port ?? null); ?> <br>
                        MADE IN INDONESIA <br>
                        INV. No. <?php echo e($item->no_packaging); ?> <br>
                        C/No. : 1 - <?php echo e(count($box)); ?>

                    </td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                    <td style="padding:0px; border-bottom:0px;"></td>
                </tr>
            <?php $__currentLoopData = $box; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $box_item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td style='border-top:0px; padding-bottom:5px;' class='text-center'><?php echo e($key+1); ?></td>
                    <td style='border-top:0px; padding-bottom:5px;' class='text-center'><?php echo e($box_item['ref_box']['item_no_series']); ?></td>
                    <td style='border-top:0px; padding-bottom:5px;' class='text-center'><?php echo e($box_item['qty_pcs_box'] ?? null); ?></td>
                    <td style='border-top:0px; padding-bottom:5px;' class='text-center'><?php echo e(round($box_item['ref_box']['unit_weight_kg'],1)); ?></td>
                    <td style='border-top:0px; padding-bottom:5px;' class='text-center'><?php echo e(round($box_item['ref_box']['total_gross_weight'],1)); ?></td>
                    <td style='border-top:0px; padding-bottom:5px;' class='text-center'><?php echo e(round((($box_item['ref_box']['length'] * $box_item['ref_box']['width'] * $box_item['ref_box']['height']) / 1000000000),3)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            
            
            <tr>
                <td colspan="3" class="text-center"> TOTAL</td>
                <td class="text-center"><?php echo e($count_qty); ?></td>
                <td class="text-center"><?php echo e(round($count_net_weight,1)); ?></td>
                <td class="text-center"><?php echo e(round($count_gross_weight,1)); ?></td>
                <td class="text-center"><?php echo e(round($count_meas,3)); ?></td>
            </tr>
        </table>

        <table style="margin-top: 20px;">
            <tr>
                <td class="no-bo" width="200px">Grand Total Number Of Cartons</td>
                <td class="no-bo" width="4">:</td>
                <td width="50px" class="text-right no-bo"><?php echo e(count($box)); ?></td>
                <td class="no-bo">Cartons Boxes</td>
            </tr>
            <tr>
                <td class="no-bo" width="200px">Grand Total Qty</td>
                <td class="no-bo" width="4">:</td>
                <td width="50px" class="text-right no-bo"><?php echo e($count_qty); ?></td>
                <td class="no-bo">(PCS)</td>
            </tr>
            <tr>
                <td class="no-bo" width="200px">Grand Total Nett Weights</td>
                <td class="no-bo" width="4">:</td>
                <td width="50px" class="text-right no-bo"><?php echo e(round($count_net_weight,1)); ?></td>
                <td class="no-bo">Kgs</td>
            </tr>
            <tr>
                <td class="no-bo" width="200px">Grand Total Gross Weights</td>
                <td class="no-bo" width="4">:</td>
                <td width="50px" class="text-right no-bo"><?php echo e(round($count_gross_weight,1)); ?></td>
                <td class="no-bo">Kgs</td>
            </tr>
            <tr>
                <td class="no-bo" width="200px">Grand Total Measurement</td>
                <td class="no-bo" width="4">:</td>
                <td width="50px" class="text-right no-bo"><?php echo e(round($count_meas,3)); ?></td>
                <td class="no-bo">M3</td>
            </tr>
        </table>
        <hr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    
</body>
<?php /**PATH /opt/e-conf-yamaha-api/resources/views/pdf/packaging/packaging_doc.blade.php ENDPATH**/ ?>