<?php

$previous_error_reporting = error_reporting(E_ERROR | E_PARSE);

const ENCRYPTION_KEY = 'your-secret-key';
const ENCRYPTION_METHOD = 'AES-256-CBC';

if (!function_exists('encrypt')) {

    function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    function decrypt($data) {
        $decoded_data = base64_decode($data);
        if ($decoded_data === false) {
            return false;
        }

        $parts = explode('::', $decoded_data);
        if (count($parts) !== 2) {
            return false;
        }

        list($encrypted_data, $iv) = $parts;
        try {
            $decrypted_data = openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
        } catch (Exception $e) {
            return false;
        }
        if ($decrypted_data === false) {
            return false;
        }

        return $decrypted_data;
    }
}

error_reporting($previous_error_reporting);

?>