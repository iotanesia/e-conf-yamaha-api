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
    const DRAFT = 1;
    const FINISH = 2;
    const STS_BOOK_FINISH = 1;
    const STS_STOK = 1;
    const INSTOCK = 'INSTOCK';
    const OUTSTOCK = 'OUTSTOCK';
    const IS_NOL = 0;
    const IS_NULL = null;
    const STS_PROCESS_RG_ENTRY = [
        1 => 'Proses', 2 => 'Done Upload', 3 => 'Send To PC', 4 => 'Revisi', 5 => 'Approved', 6 => 'Error', 7 => 'Send To DC Manager', 8 => 'Finish', 9 => "Reject"
    ];
    const PATHFILE = [
        'qr' => '/app/qrcode/label'
    ];
    const TRACKING = [2,3];
}

