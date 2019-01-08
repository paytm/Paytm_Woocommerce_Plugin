<?php
class PaytmPayment {

    static function encrypt_e($input, $ky) {
        $key   = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, $iv );
        return $data;
    }

    static function decrypt_e($crypt, $ky) {
        $key   = html_entity_decode($ky);
        $iv = "@@@@&&&&####$$$$";
        $data = openssl_decrypt ( $crypt , "AES-128-CBC" , $key, 0, $iv );
        return $data;
    }

    static function pkcs5_pad_e($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    static function pkcs5_unpad_e($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        return substr($text, 0, -1 * $pad);
    }

    static function generateSalt_e($length) {
        $random = "";
        srand((double) microtime() * 1000000);
        
        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";
        
        for ($i = 0; $i < $length; $i++) {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }
        
        return $random;
    }


    static function checkString_e($value) {
        $myvalue = ltrim($value);
        $myvalue = rtrim($myvalue);
        if ($myvalue == 'null')
            $myvalue = '';
        return $myvalue;
    }

    static function getChecksumFromArray($arrayList, $key, $sort = 1) {
        if ($sort != 0) {
            ksort($arrayList);
        }
        $str         = self::getArray2Str($arrayList);
        $salt        = self::generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash        = hash("sha256", $finalString);
        $hashString  = $hash . $salt;
        $checksum    = self::encrypt_e($hashString, $key);
        return $checksum;
    }

    static function verifychecksum_e($arrayList, $key, $checksumvalue) {
        $arrayList = self::removeCheckSumParam($arrayList);
        ksort($arrayList);
        $str        = self::getArray2StrForVerify($arrayList);
        $paytm_hash = self::decrypt_e($checksumvalue, $key);
        $salt       = substr($paytm_hash, -4);
        
        $finalString = $str . "|" . $salt;
        
        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;
        return $website_hash == $paytm_hash? true : false;
    }

    static function getArray2Str($arrayList) {
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;  
        foreach ($arrayList as $key => $value) {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos !== false || $pospipe !== false) 
            {
                continue;
            }
            
            if ($flag) {
                $paramStr .= self::checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . self::checkString_e($value);
            }
        }
        return $paramStr;
    }

    static function getArray2StrForVerify($arrayList) {
        $paramStr = "";
        $flag = 1;
        foreach ($arrayList as $key => $value) {
            if ($flag) {
                $paramStr .= self::checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . self::checkString_e($value);
            }
        }
        return $paramStr;
    }

    static function redirect2PG($paramList, $key) {
        $hashString = self::getchecksumFromArray($paramList);
        $checksum   = self::encrypt_e($hashString, $key);
    }


    static function removeCheckSumParam($arrayList) {
        if (isset($arrayList["CHECKSUMHASH"])) {
            unset($arrayList["CHECKSUMHASH"]);
        }
        return $arrayList;
    }

    static function callNewAPI($apiURL, $requestParamList) {

        $jsonResponse = wp_remote_post($apiURL, array(
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode($requestParamList),
        ));

        //$response_code = wp_remote_retrieve_response_code( $jsonResponse );
        $response_body = wp_remote_retrieve_body( $jsonResponse );
        $responseParamList = json_decode($response_body, true);
        return $responseParamList;
    }
}