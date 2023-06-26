<?php

namespace App\Constants;


class ErrorMessage
{
    const NONE = 'None';
    const INTERNAL_SERVER_ERROR = 'Terjadi kesalah pada sistem';
    const INSUF_PARAM = 'Kesalahan parameter';
    const INSUF_FILE = 'Tidak ada fail yang dipilih';
    const REQUEST_TIME_OUT = 'Kesalahan parameter';
    const INVALID_LOGIN = 'Kesalahan pada username atau kata sandi';
    const INVALID_ACCESS_TOKEN = 'Sesi habis';
    const ERROR_ACCESS_TOKEN = 'An error while decoding token';
    const TOKEN_NOT_FOUND = 'token not found';
    const URL_UNKNOWN = 'URL tidak dikenali';
    const EXTERNAL_SERVER_ERROR = 'Terjadi kesalahan pada sumber eksternal';
    const DATA_NOT_FOUND = 'Data tidak ditemukan';
    const FORBIDDEN = 'Forbidden';
    const INVALID_VERIFICATION = 'Gagal memverifikasi akun';
    const ACCOUNT_VALID = 'Akun anda sudah terverifikasi';
    const INVALID_RESET_PASSWORD = 'Gagal mengubah password';
    const EMAIL_NOT_REGISTERED = 'Email tidak terdaftar';
    const FAILED_LOGIN = 'Email tidak terdaftar atau Password salah';
    const GLOBAL_MESSAGE = 'Please contact to your system administrator about this error.';
    const INVALID_PASSWORD = 'Password Salah';
    const PROFILE_NOT_COMPLETED = 'Profil anda belum lengkap';
    const UNAUTHORIZED = 'Tidak dapat access';

}
