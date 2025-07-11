<?php
/**
 * Block types:
 * 0 Failed 3 deposits in a row
 * 1 Failed 3 SMS validations in a row
 * 2 Wrong country
 * 3 Admin locked
 * 4 User locked himself
 * 5 Tried to login too many times with the wrong password
 * 6 Failed SMS authentication
 * 7 Wrong code from email link.
 * 8 Failed login attempts.
 * 9 Instadebit chargeback
 * 16
 */

namespace App\Classes;

class BlockType
{
    const FAILED_3_DEPOSITS = 0;

    const FAILED_3_SMS_VALIDATIONS = 1;

    const WRONG_COUNTRY = 2;

    const ADMIN_LOCKED = 3;

    const USER_LOCKED_HIMSELF = 4;

    const FAILED_LOGIN = 5;

    const FAILED_SMS_AUTH = 6;

    const WRONG_CODE_FROM_EMAIL = 7;

    const FAILED_LOGIN_DUPLICATE_TYPE = 8; //this one is supposed to be wrong

    const PSP_CHARGEBACK = 9;

    const TOO_SIMILAR_ACCOUNT = 10;

    const TEMPORARY_ACCOUNT_BLOCK = 11;

    const FAILED_PEP_SL_CHECK = 12;

    const EXTERNAL_SELF_EXCLUSION = 13;

    const UNDERAGE_USER = 14;

    const EXTERNAL_VERIFIED_AS_DECEASED = 15;

    const EXTERNAL_PRECAUTIONARY_SUSPENSION = 16;

    const IGNORING_INTENSIVE_GAMBLING_CHECK = 17;

}