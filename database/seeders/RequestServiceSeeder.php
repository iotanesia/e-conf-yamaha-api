<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequestService;

class RequestServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RequestService::truncate();
        RequestService::insert([
            [
                'url' => '/api/v1/card-validation',
                'method' => 'POST',
                'request_body' => json_encode([
                    'cardNo' => null,
                    'expiryDate' => null,
                    'bankCode' => null,
                    'identificationCardNo' => null,
                    'phoneNo' => null
                ]),
            ]
        ]);
    }
}
