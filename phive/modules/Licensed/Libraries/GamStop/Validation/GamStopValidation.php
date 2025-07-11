<?php

class GamStopValidation
{
    /**
     * @param $first_name
     * @param $last_name
     * @param $date_of_birth
     * @param $email
     * @param $postcode
     * @param $mobile
     * @param $x_trace_id
     * @throws Exception
     */
    public function validateSingle($first_name,$last_name, $date_of_birth, $email, $postcode, $mobile, $x_trace_id)
    {
        $this->validateFirstName($first_name);
        $this->validateLastName($last_name);
        $this->validateDateOfBirth($date_of_birth);
        $this->validateEmail($email);
        $this->validatePostcode($postcode);
        $this->validateMobile($mobile);
        $this->validateXTraceId($x_trace_id);
    }


    /**
     * @param $first_name
     * @param $last_name
     * @param $date_of_birth
     * @param $email
     * @param $postcode
     * @param $mobile
     * @throws Exception
     */
    public function validateBatch($first_name,$last_name, $date_of_birth, $email, $postcode, $mobile)
    {
        $this->validateFirstName($first_name);
        $this->validateLastName($last_name);
        $this->validateDateOfBirth($date_of_birth);
        $this->validateEmail($email);
        $this->validatePostcode($postcode);
        $this->validateMobile($mobile);
    }


    /**
     * @param $date_of_birth
     * @throws Exception
     */
    public function validateDateOfBirth($date_of_birth)
    {

        // verify the required parameter 'date_of_birth' is set
        if ($date_of_birth === null || (is_array($date_of_birth) && count($date_of_birth) === 0)) {
            throw new \Exception(
                'Missing the required parameter $date_of_birth when calling v2Post'
            );
        }
        if (strlen($date_of_birth) > 10) {
            throw new \Exception('invalid length for "$date_of_birth" when calling DefaultApi.v2Post, must be smaller than or equal to 10.');
        }
        if (strlen($date_of_birth) < 10) {
            throw new \Exception('invalid length for "$date_of_birth" when calling DefaultApi.v2Post, must be bigger than or equal to 10.');
        }
    }

    /**
     * @param $last_name
     * @throws Exception
     */
    public function validateLastName($last_name)
    {
        // verify the required parameter 'last_name' is set
        if ($last_name === null || (is_array($last_name) && count($last_name) === 0)) {
            throw new \Exception(
                'Missing the required parameter $last_name when calling v2Post'
            );
        }
        if (strlen($last_name) > 20) {
            throw new \Exception('invalid length for "$last_name" when calling DefaultApi.v2Post, must be smaller than or equal to 20.');
        }
        if (strlen($last_name) < 2) {
            throw new \Exception('invalid length for "$last_name" when calling DefaultApi.v2Post, must be bigger than or equal to 2.');
        }
    }

    /**
     * @param $first_name
     * @throws Exception
     */
    public function validateFirstName($first_name)
    {
        if ($first_name === null || (is_array($first_name) && count($first_name) === 0)) {
            throw new \Exception(
                'Missing the required parameter $first_name when calling v2Post'
            );
        }
        if (strlen($first_name) > 20) {
            throw new \Exception('invalid length for "$first_name" when calling DefaultApi.v2Post, must be smaller than or equal to 20.');
        }
        if (strlen($first_name) < 2) {
            throw new \Exception('invalid length for "$first_name" when calling DefaultApi.v2Post, must be bigger than or equal to 2.');
        }
    }

    /**
     * @param $email
     * @throws Exception
     */
    public function validateEmail($email)
    {

        // verify the required parameter 'email' is set
        if ($email === null || (is_array($email) && count($email) === 0)) {
            throw new \Exception(
                'Missing the required parameter $email when calling v2Post'
            );
        }
        if (strlen($email) > 254) {
            throw new \Exception('invalid length for "$email" when calling DefaultApi.v2Post, must be smaller than or equal to 254.');
        }
        if (strlen($email) < 0) {
            throw new \Exception('invalid length for "$email" when calling DefaultApi.v2Post, must be bigger than or equal to 0.');
        }
    }

    /**
     * @param $postcode
     * @throws Exception
     */
    public function validatePostcode($postcode)
    {
        // verify the required parameter 'postcode' is set
        if ($postcode === null || (is_array($postcode) && count($postcode) === 0)) {
            throw new \Exception(
                'Missing the required parameter $postcode when calling v2Post'
            );
        }
        if (strlen($postcode) > 10) {
            throw new \Exception('invalid length for "$postcode" when calling DefaultApi.v2Post, must be smaller than or equal to 10.');
        }
        if (strlen($postcode) < 5) {
            throw new \Exception('invalid length for "$postcode" when calling DefaultApi.v2Post, must be bigger than or equal to 5.');
        }
    }

    /**
     * @param $mobile
     * @throws Exception
     */
    public function validateMobile($mobile)
    {
        // verify the required parameter 'mobile' is set
        if ($mobile === null || (is_array($mobile) && count($mobile) === 0)) {
            throw new \Exception(
                'Missing the required parameter $mobile when calling v2Post'
            );
        }
        if (strlen($mobile) > 14) {
            throw new \Exception('invalid length for "$mobile" when calling DefaultApi.v2Post, must be smaller than or equal to 14.');
        }
        if (strlen($mobile) < 0) {
            throw new \Exception('invalid length for "$mobile" when calling DefaultApi.v2Post, must be bigger than or equal to 0.');
        }
    }

    /**
     * @param $x_trace_id
     * @throws Exception
     */
    public function validateXTraceId($x_trace_id)
    {
        if ($x_trace_id !== null && strlen($x_trace_id) > 36) {
            throw new \Exception('invalid length for "$x_trace_id" when calling DefaultApi.v2Post, must be smaller than or equal to 36.');
        }
    }
}