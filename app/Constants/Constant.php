<?php

namespace App\Constants;


class Constant
{
    const IS_ACTIVE = 1;
    const STS_PROCESSED = 1;
    const STS_PROCESS_FINISHED = 2;
    const STS_PROCESS_SEND_TO_PC = 3;
    const STS_PROCESS_REVISION = 4;
    const STS_PROCESS_APPROVED = 5;
    const STS_ERROR = 6;
    const STS_SEND_TO_DC_MANAGER = 7;
    const STS_FINISH = 8;
    const STS_PROCESS_REJECTED = 9;
    const STS_REVISION_UPLOAD = 10;

    const DRAFT = 1;
    const FINISH = 2;
    const STS_BOOK_FINISH = 1;
    const STS_STOK = 1;
    const INSTOCK = 'INSTOCK';
    const OUTSTOCK = 'OUTSTOCK';
    const IS_NOL = 0;
    const IS_NULL = null;
    const STS_PROCESS_RG_ENTRY = [
        1 => 'Proses',
        2 => 'Done Upload',
        3 => 'Send To PC',
        4 => 'Revisi',
        5 => 'Approved',
        6 => 'Error',
        7 => 'Send To DC Manager',
        8 => 'Finish',
        9 => "Reject",
        10 => 'Failed Upload'
    ];
    const PATHFILE = [
        'qr' => '/app/qrcode/label'
    ];
    const TRACKING = [2,3];

    const IS_ACTUAL = 1;

    const STS_PROCESS_IREGULAR = [
        1 => "Waiting Approval Doc",
        2 => "Approval Doc Submitted",
        3 => "CC Officer",
        4 => "Form CC Submitted",
        5 => "Invoice Submitted",
        6 => "Packing List Submitted",
        7 => "Case Mark Submitted",
        8 => "Waiting Approval CC Supervisor",
        9 => "Waiting Approval Document CC Supervisor",
        10 => "Waiting Approval CC Manager",
        11 => "Waiting Approval Document CC Manager",
        12 => "Approved CC Manager",
        97 => "Reject CC Officer",
        98 => "Reject CC Supervisor",
        99 => "Reject CC Manager",
    ];

    const MAX_IREGULAR_REVISION = 1;
}

