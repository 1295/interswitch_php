<?php

namespace Interswitch;

/**
 * Description of Utils
 *
 * @author Abiola.Adebanjo
 */
include_once 'Constants.php';

class Utils {

    static function generateNonce() {
        return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    static function generateTimestamp() {
        $date = new DateTime(null, new DateTimeZone(Constants::LAGOS_TIME_ZONE));
        return $date->getTimestamp();
    }

    static function generateSignature($clientId, $clientSecretKey, $resourceUrl, $httpMethod, $timestamp, $nonce, $transactionParams) {

        $resourceUrl = strtolower($resourceUrl);
        $resourceUrl = str_replace('http://', 'https://', $resourceUrl);
        $encodedUrl = urlencode($resourceUrl);

        $signatureCipher = $httpMethod . '&' . $encodedUrl . '&' . $timestamp . '&' . $nonce . '&' . $clientId . '&' . $clientSecretKey;

        if (!empty($transactionParams) && is_array($transactionParams)) {
            $parameters = implode("&", $transactionParams);
            $signatureCipher = $signatureCipher . $parameters;
        }

        $signature = hash('sha256', $signatureCipher);

        return base64_encode($signature);
    }

    static function generateAccessToken($clientId, $clientSecret, $passortUrl) {
        $content_type = 'application/x-www-form-urlencoded';
        $basicCipher = base64_encode($clientId . ':' . $clientSecret);
        $authorization = 'Basic ' . $basicCipher;

        $headers = [
            'Content-Type: ' . $content_type,
            'Authorization: ' . $authorization
        ];

        echo "headers>>> " . $headers;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $passortUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "grant_type=client_credentials&scope=profile");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $curl_response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($curl_response === false) {

            curl_close($curl);
            die('error occured during curl exec. Additioanl info: ' . var_export($info));
        }

//        $json = json_decode($curl_response, true);
        $response[Constants::HTTP_CODE] = $info['http_code'];
        $response[Constants::RESPONSE_BODY] = $curl_response;

        curl_close($curl);

        return $response;
    }

    static function doREST($content_type, $authorization, $signatureMethod, $signature, $timestamp, $nonce, $resourceUrl, $request) {
        $response = Array();

        $headers = [
            'Content-Type: ' . $content_type,
            'Authorization: ' . $authorization,
            'SignatureMethod: ' . $signatureMethod,
            'Signature: ' . $signature,
            'Timestamp: ' . $timestamp,
            'Nonce: ' . $nonce
        ];

        echo $headers;
        echo $request;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $resourceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $curl_response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($curl_response === false) {
            curl_close($curl);
            die('error occured during curl exec. Additioanl info: ' . var_export($info));
        }

//        $json = json_decode($curl_response, true);
        $response[Constants::HTTP_CODE] = $info['http_code'];
        $response[Constants::RESPONSE_BODY] = $curl_response;

        curl_close($curl);

        return $response;
    }

}