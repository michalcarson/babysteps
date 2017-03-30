<?php

namespace App\Models;

/*
 * provides encryption and decryption services
 *
 * @author Michal Carson <michal.carson@carsonsoftwareengineering.com
 * @author Jacq Ball
 * @copyright (c) 2014, Carson Software Engineering
 *
 */

class DatabaseEncryptor
{
    /**
     * encrypt the field with public key
     *
     * key generation commands:
     *      openssl genrsa -out private.pem 1024
     *      openssl rsa -in private.pem -out public.pem -outform PEM -pubout
     *
     * PUBLIC_KEY_FILE file must be a PHP file that sets $key like so:
     * <?php
     * $key="-----BEGIN PUBLIC KEY-----
     * MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCv8EFAnMERCjf5pJxE2MQwucOb
     * jtndnDjJs91wQFB4EuF2+7u+Rfw8/8mlI6k5ptCU9p8oSPlY9RGT5JdNMLEzoOLA
     * livtN1N9pRMKu7zWw4liaA3JtjLWVlLGzklU6D1PSWKPRVwbTIoIKpznXTlfkL+E
     * d2MU6cTbw3gPVluF5wIDAQAB
     * -----END PUBLIC KEY-----";
     * @staticvar string $key_public
     * @param string $cleartext data to encrypt
     * @return string               encrypted data
     */
    public function encrypt($cleartext)
    {
        // get the public key
        static $key_public = null;

        // don't keep reading it if we already have it
        if ( ! $key_public) {

            // this should have been defined in config.php, but if not...
            if ( ! defined('PUBLIC_KEY_FILE')) {
                define('PUBLIC_KEY_FILE', SITE_ROOT . '/public.php');
            }

            include_once(PUBLIC_KEY_FILE);

            if ( ! strlen($key)) {
                die('cannot load public key from ' . realpath(PUBLIC_KEY_FILE));
            }

            $key_public = openssl_get_publickey($key);
            if ($key_public === false) {
                die('public key is invalid');
            }

        }

        // lets encrypt
        if (openssl_public_encrypt($cleartext, $encrypted, $key_public) === false) {
            die('encryption failed');
        }

        return $encrypted;
    }

    /**
     * decrypt the field with the private key
     *
     * PRIVATE_KEY_FILE file must be a PHP file that sets $key_text like so:
     * <?php
     * $key_text="-----BEGIN PRIVATE KEY-----
     * MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCv8EFAnMERCjf5pJxE2MQwucOb
     * jtndnDjJs91wQFB4EuF2+7u+Rfw8/8mlI6k5ptCU9p8oSPlY9RGT5JdNMLEzoOLA
     * livtN1N9pRMKu7zWw4liaA3JtjLWVlLGzklU6D1PSWKPRVwbTIoIKpznXTlfkL+E
     * d2MU6cTbw3gPVluF5wIDAQAB
     * -----END PRIVATE KEY-----";
     * @staticvar string $key_private
     * @param string $ciphertext
     * @return string
     */
    public function decrypt($ciphertext)
    {
        static $key_private = null;

        if ( ! $key_private) {

            if ( ! defined('PRIVATE_KEY_FILE')) {
                define('PRIVATE_KEY_FILE', SITE_ROOT . '/private.php'); // not a good place to keep a private key!
            }

            include_once(PRIVATE_KEY_FILE);

            if ( ! strlen($key_text)) {
                die('cannot load private key'); // do not say where the key is
            }

            $key_private = openssl_get_privatekey($key_text);
            if ($key_private === false) {
                die('private key is invalid');
            }

        }

        if (openssl_private_decrypt($ciphertext, $decrypted, $key_private) === false) {
            die('decryption failed');
        }

        return $decrypted;
    }
}
