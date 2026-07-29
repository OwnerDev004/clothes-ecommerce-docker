<?php
declare(strict_types=1);

namespace Piseth\BakongKhqr\Utils;

use Piseth\BakongKhqr\Exceptions\KHQRException;

class PostUtils
{
    public static function post_data_to_url(string $url, array $payload, ?string $bearerToken = null): array
    {
        $postData = json_encode($payload);
        // Set up cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        if (isset($bearerToken)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearerToken,
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Max execution time in seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Stops if the connection cannot be established in 10s

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $error_code = curl_errno($ch);
        curl_close($ch);

        if ($error_code === CURLE_OPERATION_TIMEDOUT) {
            throw new KHQRException(KHQRException::CONNECTION_TIMEOUT);
        }

        // Check for errors
        if ($response === false || $httpCode != 200) {
            if (! StringUtils::isEmpty($error)) {
                throw new KHQRException($error, $httpCode);
            }
            if (is_string($response)) {
                $json = json_decode($response, true);
                if (
                    is_array($json)
                    && isset($json['responseMessage'])
                    && isset($json['errorCode'])
                ) {
                    throw new KHQRException(
                        (string) $json['responseMessage'],
                        (int) $json['errorCode']
                    );
                }

                throw new KHQRException($httpCode.PHP_EOL.$response, $httpCode);
            }
        }

        $result = json_decode((string) $response, true);

        return is_array($result) ? $result : [$response];
    }
}