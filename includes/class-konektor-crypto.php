<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Konektor_Crypto {

    private static function key() {
        return hash( 'sha256', KONEKTOR_ENCRYPTION_KEY, true );
    }

    public static function encrypt( $plaintext ) {
        if ( empty( $plaintext ) ) return $plaintext;
        $iv  = openssl_random_pseudo_bytes( 16 );
        $enc = openssl_encrypt( $plaintext, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv );
        return base64_encode( $iv . $enc );
    }

    public static function decrypt( $ciphertext ) {
        if ( empty( $ciphertext ) ) return $ciphertext;
        $raw = base64_decode( $ciphertext );
        if ( strlen( $raw ) < 17 ) return $ciphertext;
        $iv  = substr( $raw, 0, 16 );
        $enc = substr( $raw, 16 );
        return openssl_decrypt( $enc, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv );
    }

    public static function fingerprint( $phone, $email ) {
        return hash( 'sha256', strtolower( trim( $phone ) ) . '|' . strtolower( trim( $email ) ) );
    }

    public static function generate_token( $length = 48 ) {
        return bin2hex( random_bytes( $length ) );
    }
}
