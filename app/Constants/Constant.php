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

    const STS_PROCESS_RG_ENTRY = [
        1 => 'Proses', 2 => 'Selesai', 3 => 'Send To PC', 4 => 'Revisi', 5 => 'Approved', 6 => 'Error'
    ];
}

