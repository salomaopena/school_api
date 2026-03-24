<?php

use \Config\Services;

function Encrypt($value)
{
    $secret = CHAVE_ENCRIPTACAO;

    // Assinatura reduzida (8 bytes)
    $signature = substr(
        hash_hmac('sha256', $value, $secret, true),
        0,
        16 // 16 bytes = 128 bits de segurança
    );

    // Concatena id + assinatura
    $data = $value . $signature;

    // Base64 URL-safe
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function Decrypt($value)
{
    $secret = CHAVE_ENCRIPTACAO;

    $decoded = base64_decode(strtr($value, '-_', '+/'));

    if ($decoded === false || strlen($decoded) <= 16) {
        return null;
    }

    $id = substr($decoded, 0, -16);
    $signature = substr($decoded, -16);

    $expected = substr(
        hash_hmac('sha256', $id, $secret, true),
        0,
        16
    );

    return hash_equals($expected, $signature) ? $id : null;
}




// util para dados sensíveis
function encrypt_token($value)
{
    if (empty($value)) {
        return null;
    } else {
        try {
            // Get encryption service instance
            $encryption = Services::encrypter();

            return base64_encode($encryption->encrypt($value));
        } catch (Exception $e) {
            return null;
        }
    }
}

function decrypt_token($value)
{
    if (empty($value)) {
        return null;
    } else {
        try {
            // Get encryption service instance
            $encryption = Services::encrypter();

            return $encryption->decrypt(base64_decode($value));
        } catch (Exception $e) {
            return null;
        }
    }
}


