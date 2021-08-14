<?php

namespace Feeria\PhpFrejaeid;

use Exception;

class PhpFrejaeid {

    // Private variables
    private $production;
    private $serviceUrl;
    private $resourceUrl;
    private $certificate;
    private $password;
    private $currentAuth;
    private $jwsCert;

    // Construct the class
    public function __construct(
      $certificate,
      $password,
      $production = false
    ) {

        // Set production variable
        $this->production = $production;

        // If production mode
        if ($production) {

            // Set API endpoints set to production
            $this->serviceUrl  = 'https://services.prod.frejaeid.com';
            $this->resourceUrl = 'https://resources.prod.frejaeid.com';

            // Read certificate
            if ( ! is_readable( __DIR__ . '/certificates/frejaeid_cert_prod.pem') )
                throw new Exception('JWS Certificate file could not be found (' . __DIR__ . '/certificates/frejaeid_cert_prod.pem)');
            else
                $this->jwsCert = file_get_contents( __DIR__ . '/certificates/frejaeid_cert_prod.pem');

        // If test mode
        } else {

            // Set API endpoints set to test
            $this->serviceUrl  = 'https://services.test.frejaeid.com';
            $this->resourceUrl = 'https://resources.test.frejaeid.com';

            // Read certificate
            if ( ! is_readable( __DIR__ . '/certificates/frejaeid_cert_test.pem') )
                throw new Exception('JWS Certificate file could not be found (' . __DIR__ . '/certificates/frejaeid_cert_test.pem)');
            else
                $this->jwsCert = file_get_contents( __DIR__ . '/certificates/frejaeid_cert_test.pem');

        }

        // If Freja eID provided certificate not found
        if ( ! is_readable($certificate) )
            throw new Exception('Certificate file in .pfx format could not be found. Please check if file exists or contact Freja eID to receive it.');

        // Get certificate and password
        $this->certificate = $certificate;
        $this->password = $password;

    }

    // Create QR code
    public function createAuthQRCode($existingCode = NULL) {

        // TODO to is_null
        if ($this->IsNullOrEmptyString($existingCode)) {
            $response = $this->initAuthentication();
            if (!$response->success)
                return $response;
            $existingCode = $response->authRef;
        }

        // Create an object
        $resultObject = $this->createSuccessObject();
        $resultObject->url = $this->resourceUrl . '/qrcode/generate?qrcodedata=frejaeid%3A%2F%2FbindUserToTransaction%3Fdimension%3D4x%3FtransactionReference%3D' . $existingCode;
        $resultObject->authRef = $existingCode;

        return $resultObject;

    }

    // Cancel the auth request
    public function cancelAuthentication($authRef) {

        // Get the authRef param
        $query = new \stdClass();
        $query->authRef = $authRef;

        // Form the cancellation request
        $apiPost = array(
            'cancelAuthRequest' => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);

        // Post to FrejaeID API cancellation request
        $result = $this->apiRequest(
          '/authentication/1.0/cancel',
          $apiPostQuery
        );

        // If error - return the error object
        if ( ! $result->success)
            return $this->createErrorObject($result->code, $result->data);

        // Else return success object
        return $this->createSuccessObject();

    }

    // Check the auth status
    public function checkAuthentication($authRef) {

        // Get the authRef param
        $query = new \stdClass();
        $query->authRef = $authRef;

        // Form the check request
        $apiPost = array(
            'getOneAuthResultRequest' => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);

        // Post to FrejaeID API check request
        $result = $this->apiRequest(
          '/authentication/1.0/getOneResult',
          $apiPostQuery
        );

        // If error - return the error object
        if ( ! $result->success)
            return $this->createErrorObject($result->code, $result->data);

        // Else return success object
        return $this->createSuccessObject($result->data);

    }

    // Auth initialization
    public function initAuthentication(
      $userType  = 'N/A',
      $userInfo  = 'N/A',
      $authLevel = 'BASIC',
      $requestInfo = ['EMAIL_ADDRESS', 'RELYING_PARTY_USER_ID']
    ) {

        // If no request info provided...
        if (empty($requestInfo)) {
          return $this->createErrorObject(
            400,
            'Missing request info from FrejaeID. Please request one or several attributes for EXTENDED and PLUS levels: EMAIL_ADDRESS, BASIC_USER_INFO, DATE_OF_BIRTH, SSN, ALL_EMAIL_ADDRESSES, ALL_PHONE_NUMBERS, AGE, ADDRESSES, REGISTRATION_LEVEL. For BASIC level allowed only: EMAIL_ADDRESS, REGISTRATION_LEVEL'
          );
        }

        // Set EMAIL_ADDRESS attribute
        if (in_array('EMAIL_ADDRESS', $requestInfo)) {
          $emailAttribute = new \stdClass();
          $emailAttribute->attribute = 'EMAIL_ADDRESS';
        }

        // Set RELYING_PARTY_USER_ID attribute
        if (in_array('RELYING_PARTY_USER_ID', $requestInfo)) {
          $userAttribute = new \stdClass();
          $userAttribute->attribute = 'RELYING_PARTY_USER_ID';
        }

        // Set BASIC_USER_INFO attribute
        if (in_array('BASIC_USER_INFO', $requestInfo)) {
          $basicAttribute = new \stdClass();
          $basicAttribute->attribute = 'BASIC_USER_INFO';
        }

        // Set DATE_OF_BIRTH attribute
        if (in_array('DATE_OF_BIRTH', $requestInfo)) {
          $dobAttribute = new \stdClass();
          $dobAttribute->attribute = 'DATE_OF_BIRTH';
        }

        // Set SSN attribute
        if (in_array('SSN', $requestInfo)) {
          $ssnAttribute = new \stdClass();
          $ssnAttribute->attribute = 'SSN';
        }

        // Set ALL_EMAIL_ADDRESSES attribute
        if (in_array('ALL_EMAIL_ADDRESSES', $requestInfo)) {
          $allEmailsAttribute = new \stdClass();
          $allEmailsAttribute->attribute = 'ALL_EMAIL_ADDRESSES';
        }

        // Set ALL_PHONE_NUMBERS attribute
        if (in_array('ALL_PHONE_NUMBERS', $requestInfo)) {
          $allPhonesAttribute = new \stdClass();
          $allPhonesAttribute->attribute = 'ALL_PHONE_NUMBERS';
        }

        // Set AGE attribute
        if (in_array('AGE', $requestInfo)) {
          $ageAttribute = new \stdClass();
          $ageAttribute->attribute = 'AGE';
        }

        // Set ADDRESSES attribute
        if (in_array('ADDRESSES', $requestInfo)) {
          $allAddressesAttribute = new \stdClass();
          $allAddressesAttribute->attribute = 'ADDRESSES';
        }

        // Set REGISTRATION_LEVEL attribute
        if (in_array('REGISTRATION_LEVEL', $requestInfo)) {
          $registrationLevelAttribute = new \stdClass();
          $registrationLevelAttribute->attribute = 'REGISTRATION_LEVEL';
        }

        // Push all requested attributes to array
        $query = new \stdClass();
        $query->attributesToReturn = array ( $emailAttribute );
        array_push($query->attributesToReturn, $userAttribute );

        // Check type of user login param
        switch ($userType) {

            // If not provided any data - return empty user
            case 'N/A':
                 $query->userInfoType = 'INFERRED';
                 $query->userInfo     = 'N/A';
                 break;

            // In case of phone - use the phone to login
            case 'PHONE':
                 $query->userInfoType = 'PHONE';
                 $query->userInfo = $userInfo;
                 break;

            // In case of email - use the email to login
            case 'EMAIL':
                 $query->userInfoType = 'EMAIL';
                 $query->userInfo = $userInfo;
                 break;

            // In case of SSN - use the SSN to login (only Sweden)
            case 'SSN':
                 $query->userInfoType = 'SSN';
                 $ssnUserinfo = new \stdClass();
                 $ssnUserinfo->country = 'SE';
                 $ssnUserinfo->ssn = $userInfo;
                 $query->userInfo = base64_encode(json_encode($ssnUserinfo));
                 break;

            // Another case throw an exception
            default:
                 throw new Exception('User type not N/A, EMAIL, PHONE or SNN.');
                 break;

        }

        // Check the authorization level in FrejaeID
        switch ($authLevel) {

            // If BASIC - return BASIC data
            case 'BASIC':
                 $query->minRegistrationLevel = 'BASIC';
                 if (in_array('REGISTRATION_LEVEL', $requestInfo))  array_push($query->attributesToReturn, $registrationLevelAttribute);
                 break;

            // If EXTENDED - return EXTENDED data
            case 'EXTENDED':
                 $query->minRegistrationLevel = 'EXTENDED';
                 if (in_array('BASIC_USER_INFO', $requestInfo))     array_push($query->attributesToReturn, $basicAttribute);
                 if (in_array('REGISTRATION_LEVEL', $requestInfo))  array_push($query->attributesToReturn, $registrationLevelAttribute);
                 if (in_array('ALL_EMAIL_ADDRESSES', $requestInfo)) array_push($query->attributesToReturn, $allEmailsAttribute);
                 if (in_array('ALL_PHONE_NUMBERS', $requestInfo))   array_push($query->attributesToReturn, $allPhonesAttribute);
                 if (in_array('DATE_OF_BIRTH', $requestInfo))       array_push($query->attributesToReturn, $dobAttribute);
                 if (in_array('AGE', $requestInfo))                 array_push($query->attributesToReturn, $ageAttribute);
                 if (in_array('SSN', $requestInfo))                 array_push($query->attributesToReturn, $ssnAttribute);
                 if (in_array('ADDRESSES', $requestInfo))           array_push($query->attributesToReturn, $allAddressesAttribute);
                 break;

            // If PLUS - return PLUS data
            case 'PLUS':
                 $query->minRegistrationLevel = 'PLUS';
                 if (in_array('BASIC_USER_INFO', $requestInfo))     array_push($query->attributesToReturn, $basicAttribute);
                 if (in_array('REGISTRATION_LEVEL', $requestInfo))  array_push($query->attributesToReturn, $registrationLevelAttribute);
                 if (in_array('ALL_EMAIL_ADDRESSES', $requestInfo)) array_push($query->attributesToReturn, $allEmailsAttribute);
                 if (in_array('ALL_PHONE_NUMBERS', $requestInfo))   array_push($query->attributesToReturn, $allPhonesAttribute);
                 if (in_array('DATE_OF_BIRTH', $requestInfo))       array_push($query->attributesToReturn, $dobAttribute);
                 if (in_array('AGE', $requestInfo))                 array_push($query->attributesToReturn, $ageAttribute);
                 if (in_array('SSN', $requestInfo))                 array_push($query->attributesToReturn, $ssnAttribute);
                 if (in_array('ADDRESSES', $requestInfo))           array_push($query->attributesToReturn, $allAddressesAttribute);
                 break;

            // Another case throw an exception
            default:
                 throw new Exception('User type is not BASIC, EXTENDED or PLUS. Please check the provided parameter.');
                 break;
        }

        // Form the init request
        $apiPost = array(
            'initAuthRequest' => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);

        // Post to FrejaeID API init request
        $result = $this->apiRequest(
          '/authentication/1.0/initAuthentication',
          $apiPostQuery
        );

        // If error - return the error object
        if ( ! $result->success)
            return $this->createErrorObject($result->code, $result->data);

        // If missed authRef - return the error object
        if ( ! isset($result->data->authRef))
            return $this->createErrorObject(
              400,
              'Missing authRef from API response.'
            );

        // If success - return the success object
        return $this->createSuccessObject($result->data);

    }

    public function initSignatureRequest(
      $userType,
      $userInfo,
      $agreementText,
      $agreementTitle,
      $authLevel = 'BASIC',
      $requestInfo = ['EMAIL_ADDRESS', 'RELYING_PARTY_USER_ID'],
      $timeoutMinutes = 2,
      $confidential = false,
      $pushTitle = NULL,
      $pushMessage = NULL,
      $binaryData = NULL
    ) {

        // If no request info provided...
        if (empty($requestInfo)) {
          return $this->createErrorObject(
            400,
            'Missing request info from FrejaeID. Please request one or several attributes for EXTENDED and PLUS levels: EMAIL_ADDRESS, BASIC_USER_INFO, DATE_OF_BIRTH, SSN, ALL_EMAIL_ADDRESSES, ALL_PHONE_NUMBERS, AGE, ADDRESSES, REGISTRATION_LEVEL. For BASIC level allowed only: EMAIL_ADDRESS, REGISTRATION_LEVEL'
          );
        }

        // Set EMAIL_ADDRESS attribute
        if (in_array('EMAIL_ADDRESS', $requestInfo)) {
          $emailAttribute = new \stdClass();
          $emailAttribute->attribute = 'EMAIL_ADDRESS';
        }

        // Set RELYING_PARTY_USER_ID attribute
        if (in_array('RELYING_PARTY_USER_ID', $requestInfo)) {
          $userAttribute = new \stdClass();
          $userAttribute->attribute = 'RELYING_PARTY_USER_ID';
        }

        // Set BASIC_USER_INFO attribute
        if (in_array('BASIC_USER_INFO', $requestInfo)) {
          $basicAttribute = new \stdClass();
          $basicAttribute->attribute = 'BASIC_USER_INFO';
        }

        // Set DATE_OF_BIRTH attribute
        if (in_array('DATE_OF_BIRTH', $requestInfo)) {
          $dobAttribute = new \stdClass();
          $dobAttribute->attribute = 'DATE_OF_BIRTH';
        }

        // Set SSN attribute
        if (in_array('SSN', $requestInfo)) {
          $ssnAttribute = new \stdClass();
          $ssnAttribute->attribute = 'SSN';
        }

        // Set ALL_EMAIL_ADDRESSES attribute
        if (in_array('ALL_EMAIL_ADDRESSES', $requestInfo)) {
          $allEmailsAttribute = new \stdClass();
          $allEmailsAttribute->attribute = 'ALL_EMAIL_ADDRESSES';
        }

        // Set ALL_PHONE_NUMBERS attribute
        if (in_array('ALL_PHONE_NUMBERS', $requestInfo)) {
          $allPhonesAttribute = new \stdClass();
          $allPhonesAttribute->attribute = 'ALL_PHONE_NUMBERS';
        }

        // Set AGE attribute
        if (in_array('AGE', $requestInfo)) {
          $ageAttribute = new \stdClass();
          $ageAttribute->attribute = 'AGE';
        }

        // Set ADDRESSES attribute
        if (in_array('ADDRESSES', $requestInfo)) {
          $allAddressesAttribute = new \stdClass();
          $allAddressesAttribute->attribute = 'ADDRESSES';
        }

        // Set REGISTRATION_LEVEL attribute
        if (in_array('REGISTRATION_LEVEL', $requestInfo)) {
          $registrationLevelAttribute = new \stdClass();
          $registrationLevelAttribute->attribute = 'REGISTRATION_LEVEL';
        }

        // Push all requested attributes to array
        $query = new \stdClass();
        $query->attributesToReturn = array ( $emailAttribute );
        array_push($query->attributesToReturn, $userAttribute );

        // Check if agreement text and title is set
        if ($this->IsNullOrEmptyString($agreementText) or $this->IsNullOrEmptyString($agreementTitle))
            throw new Exception('Agreement text and title must be specified.');

        // Check type of user login param
        switch ($userType) {

            // In case of phone - use the phone to sign
            case 'PHONE':
                 $query->userInfoType = 'PHONE';
                 $query->userInfo = $userInfo;
                 break;

            // In case of email - use the email to sign
            case 'EMAIL':
                 $query->userInfoType = 'EMAIL';
                 $query->userInfo = $userInfo;
                 break;

            // In case of SSN - use the SSN to sign
            case 'SSN':
                 $query->userInfoType = 'SSN';
                 $ssnUserinfo = new \stdClass();
                 $ssnUserinfo->country = 'SE';
                 $ssnUserinfo->ssn = $userInfo;
                 $query->userInfo = base64_encode(json_encode($ssnUserinfo));
                 break;

            // Another case throw an exception
            default:
                 throw new Exception('User type is not BASIC, EXTENDED or PLUS. Please check the provided parameter.');
                 break;
        }

        // Check the authorization level in FrejaeID
        switch ($authLevel) {

            // If BASIC - return BASIC data
            case 'BASIC':
                 $query->minRegistrationLevel = 'BASIC';
                 if (in_array('REGISTRATION_LEVEL', $requestInfo))  array_push($query->attributesToReturn, $registrationLevelAttribute);
                 break;

            // If EXTENDED - return EXTENDED data
            case 'EXTENDED':
                 $query->minRegistrationLevel = 'EXTENDED';
                 if (in_array('BASIC_USER_INFO', $requestInfo))     array_push($query->attributesToReturn, $basicAttribute);
                 if (in_array('DATE_OF_BIRTH', $requestInfo))       array_push($query->attributesToReturn, $dobAttribute);
                 if (in_array('AGE', $requestInfo))                 array_push($query->attributesToReturn, $ageAttribute);
                 if (in_array('SSN', $requestInfo))                 array_push($query->attributesToReturn, $ssnAttribute);
                 if (in_array('REGISTRATION_LEVEL', $requestInfo))  array_push($query->attributesToReturn, $registrationLevelAttribute);
                 if (in_array('ALL_EMAIL_ADDRESSES', $requestInfo)) array_push($query->attributesToReturn, $allEmailsAttribute);
                 if (in_array('ALL_PHONE_NUMBERS', $requestInfo))   array_push($query->attributesToReturn, $allPhonesAttribute);
                 if (in_array('ADDRESSES', $requestInfo))           array_push($query->attributesToReturn, $allAddressesAttribute);
                 break;

            // If PLUS - return PLUS data
            case 'PLUS':
                 $query->minRegistrationLevel = 'PLUS';
                 if (in_array('BASIC_USER_INFO', $requestInfo))     array_push($query->attributesToReturn, $basicAttribute);
                 if (in_array('DATE_OF_BIRTH', $requestInfo))       array_push($query->attributesToReturn, $dobAttribute);
                 if (in_array('AGE', $requestInfo))                 array_push($query->attributesToReturn, $ageAttribute);
                 if (in_array('SSN', $requestInfo))                 array_push($query->attributesToReturn, $ssnAttribute);
                 if (in_array('REGISTRATION_LEVEL', $requestInfo))  array_push($query->attributesToReturn, $registrationLevelAttribute);
                 if (in_array('ALL_EMAIL_ADDRESSES', $requestInfo)) array_push($query->attributesToReturn, $allEmailsAttribute);
                 if (in_array('ALL_PHONE_NUMBERS', $requestInfo))   array_push($query->attributesToReturn, $allPhonesAttribute);
                 if (in_array('ADDRESSES', $requestInfo))           array_push($query->attributesToReturn, $allAddressesAttribute);
                 break;

            // Another case throw an exception
            default:
                 throw new Exception('User type not BASIC, EXTENDED or PLUS.');
                 break;
        }

        // Set agreement
        $query->title = $agreementTitle;
        $query->confidential = $confidential;
        $query->expiry = (time() + ($timeoutMinutes * 60)) * 1000;

        // Push notificationn title and text
        if (!$this->IsNullOrEmptyString($pushTitle)) {
            $pushNotification = new \stdClass();
            $pushNotification->title = $pushTitle;
            $pushNotification->text = $pushMessage;
            $query->pushNotification = $pushNotification;
        }

        // Contract to sign
        $dataToSign = new \stdClass();
        $dataToSign->text = base64_encode($agreementText);

        if ($this->IsNullOrEmptyString($binaryData)) {
            $query->dataToSign      = $dataToSign;
            $query->dataToSignType  = 'SIMPLE_UTF8_TEXT';
            $query->signatureType   = 'SIMPLE';
        } else {
            $dataToSign->binaryData = base64_encode($binaryData);
            $query->dataToSign      = $dataToSign;
            $query->dataToSignType  = 'EXTENDED_UTF8_TEXT';
            $query->signatureType   = 'EXTENDED';
        }

        // Form the signature request
        $apiPost = array(
            'initSignRequest' => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);

        // Post to FrejaeID API signature request
        $result = $this->apiRequest(
          '/sign/1.0/initSignature',
          $apiPostQuery
        );

        // If error - return the error object
        if ( ! $result->success )
            return $this->createErrorObject($result->code, $result->data);

        // If missed signRef - return the error object
        if ( ! isset($result->data->signRef) )
            return $this->createErrorObject(
              400,
              'Missing signRef from API response.'
            );

        // If success - return the success object
        return $this->createSuccessObject($result->data);
    }

    // Check the signature request
    public function checkSignatureRequest($signRef) {

        // Get the signRef param
        $query = new \stdClass();
        $query->signRef = $signRef;

        // Form the signature check request
        $apiPost = array(
            'getOneSignResultRequest' => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);

        // Post to FrejaeID API signature check request
        $result = $this->apiRequest(
          '/sign/1.0/getOneResult',
          $apiPostQuery
        );

        // If error - return the error object
        if ( ! $result->success)
            return $this->createErrorObject($result->code, $result->data);

        // If data status not approved - return the error object
        if ($result->data->status != 'APPROVED')
            return $this->createSuccessObject($result->data);

        // Call JWS
        $jws = new \Gamegos\JWS\JWS();

        // Try to decode the signature
        try
        {
            $result->data->details = json_decode(json_encode($jws->verify($result->data->details, $this->jwsCert))); //
            $result->data->jwsMessage = 'The signed information is valid.';
            $result->data->jwsVerified = true;
        }

        // Else catch exception
        catch (Exception $e)
        {
            try
            {
                $result->data->details = json_decode(json_encode($jws->decode($result->data->details)));
                $result->data->jwsMessage = $e->getMessage();
                $result->data->jwsVerified = false;
            }
            catch (Exception $e)
            {
                return $this->createErrorObject(
                  400,
                  'JWS data decoding from the remote was failed.'
                );
            }
        }

        $headers = $result->data->details->headers;
        $payload = $result->data->details->payload;

        $userTicket = explode('.', $result->data->details->payload->signatureData->userSignature);
        $userHeader = json_decode(base64_decode($userTicket[0]));
        $userPayload = base64_decode($userTicket[1]);
        $userSignature = $userTicket[2];

        // Get the details
        $result->data->details->payload->signatureData = new \stdClass();
        $result->data->details->payload->signatureData->kid = $userHeader->kid;
        $result->data->details->payload->signatureData->alg = $userHeader->alg;
        $result->data->details->payload->signatureData->content = $userPayload;
        $result->data->details->payload->userInfo = json_decode($result->data->details->payload->userInfo);

        $result->data->details = new \stdClass();
        $result->data->details = $payload;
        $result->data->details->x5t = $headers->x5t;
        $result->data->details->alg = $headers->alg;

        // Return the success object
        return $this->createSuccessObject($result->data);

    }

    public function cancelSignatureRequest($signRef) {

        // Get the signRef param
        $query = new \stdClass();
        $query->signRef = $signRef;

        // Form the signature cancel request
        $apiPost = array(
            'cancelSignRequest' => base64_encode(json_encode($query))
        );
        $apiPostQuery = http_build_query($apiPost);

        // Post to FrejaeID API signature cancel request
        $result = $this->apiRequest(
          '/sign/1.0/cancel',
          $apiPostQuery
        );

        // If error - return the error object
        if ( ! $result->success )
            return $this->createErrorObject($result->code, $result->data);

        // If success - return the success object
        return $this->createSuccessObject();

    }

    // API cURL request
    private function apiRequest($apiUrl,$apiPostQuery){

        // Init cURL
        $curl = curl_init();

        // Set required API Headers
        $apiHeader = array();
        $apiHeader[] = 'Content-length: ' . strlen($apiPostQuery);
        $apiHeader[] = 'Content-type: application/json';

        // Set cURL Options
        $options = array(
            CURLOPT_URL                 => $this->serviceUrl . $apiUrl,
            CURLOPT_RETURNTRANSFER      => true,
            CURLOPT_HEADER              => false,
            CURLINFO_HEADER_OUT         => false,
            CURLOPT_HTTPGET             => false,
            CURLOPT_POST                => true,
            CURLOPT_FOLLOWLOCATION      => false,
            CURLOPT_SSL_VERIFYHOST      => false,                               // NOTE: is set to true, in production will not work due to private certs at FrejaeID
            CURLOPT_SSL_VERIFYPEER      => false,                               // NOTE: is set to true, in production will not work due to private certs at FrejaeID
            CURLOPT_TIMEOUT             => 30,
            CURLOPT_MAXREDIRS           => 2,
            CURLOPT_HTTPHEADER          => $apiHeader,
            CURLOPT_USERAGENT           => 'phpFreja/1.0', //TODO to class name
            CURLOPT_POSTFIELDS          => $apiPostQuery,
            CURLOPT_SSLCERTTYPE         => 'P12',
            CURLOPT_SSLCERT             => $this->certificate,
            CURLOPT_KEYPASSWD           => $this->password
        );

        // Set options to cURL
        curl_setopt_array($curl, $options);
        $http_output = curl_exec($curl);
        $http_info = curl_getinfo($curl);

        // If error
        if (curl_errno($curl)) {
                $response->success = false;
                $response->code = 500;
                $response->data = curl_error($curl);
                return $response;
        }

        // Form response and codes/errors
        $response = new \stdClass();
        switch($http_info['http_code']) {

            case 200:
                $remoteResponse     = json_decode($http_output);
                $response->success  = true;
                $response->code     = 200;
                $response->data     = $remoteResponse;
                break;

            case 204:
                $response->success  = true;
                $response->code     = 200;
                $response->data     = '';
                break;

            case 404:

            case 410:
                $response->success  = false;
                $response->code     = 404;
                $response->data     = 'Freja eID API reported the resource cannot be found.';
                break;

            case 400:
                $response->success  = false;
                $response->code     = 400;
                $response->data     = 'Freja eID API reported the request cannot be parsed.';
                break;

            case 422:
                $remoteResponse     = json_decode($http_output);
                $response->success  = false;
                $response->code     = 400;
                $response->data     = 'Freja eID API reported processing errors: ' . $remoteResponse->message;
                break;

            case 500:
                $response->success  = false;
                $response->code     = 500;
                $response->data     = 'Freja eID API reported an internal error.';
                break;

            default:
                $response->success  = false;
                $response->code     = 500;
                $response->data     = 'Freja eID API reported an unknown status: ' . $remoteResponse->code;
                $response->http_data = $http_output;
                break;

        }

        return $response;

    }

    // TODO remove
    private function IsNullOrEmptyString($input){
            return (!isset($input) || trim($input)==='');
    }

    // Form the error object
    private function createErrorObject(
      $error_code,
      $error_message
    ) {
        $resultObject           = new \stdClass();
        $resultObject->success  = false;
        $resultObject->code     = $error_code;
        $resultObject->message  = $error_message;
        return $resultObject;
    }

    // Form the success object
    private function createSuccessObject($dataObject) {
        if (!isset($dataObject)) {
            $dataObject = new \stdClass();
        }
        $dataObject->success = true;
        return $dataObject;
    }

}

?>
