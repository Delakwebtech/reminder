<?php

// Include database connection
// include('config/database.php');

class Token{
    
    static function Sign($payload, $key, $expire = null){
        // header
        $header = ['algo' => 'HS256',  'type' => 'JWT'];
        if($expire){
            $header['expire'] = time() + $expire;
        }
        
        $header_encoded = base64_encode(json_encode($header));
        
        
        // payload
        $payload_encoded = base64_encode(json_encode($payload));
        
        // Signature
        $signature = hash_hmac('SHA256', $header_encoded.$payload_encoded, $key);
        $signature_encoded = base64_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    static function Verify($token, $key){
        $token_parts = explode('.', $token);
        
        $signature = base64_encode(hash_hmac('SHA256', $token_parts[0] . $token_parts[1], $key));
        
        if($signature != $token_parts[2]){
            echo json_encode("Invalid token");
            return false;
        }
        
        $header = json_decode(base64_decode($token_parts[0]), true);
        
        if(isset($header['expire'])){
            if($header['expire'] < time()){
                echo json_encode("Token expired");
                return false;
            }
        }
        
        $payload = json_decode(base64_decode($token_parts[1]), true);
        
        return $payload;
    }
    
}

?>
