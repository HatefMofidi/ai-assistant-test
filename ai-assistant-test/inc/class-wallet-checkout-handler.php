<?php
class AI_Assistant_Wallet_Checkout_Handler {
    private static $instance;
    
    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function connect_to_zarinpal($amount) {
        // استفاده از مرچنت آیدی مناسب
        $merchant_id = ai_assistant_get_zarinpal_merchant_id();
        $callback_url = home_url('/wallet-checkout?payment_verify=1');
        
        $data = array(
            'merchant_id' => $merchant_id,
            'amount' => $amount,
            'callback_url' => $callback_url,
            'description' => 'شارژ کیف پول هوش مصنوعی',
        );
        
        // استفاده از API مناسب برای sandbox
        $api_url = ZARINPAL_SANDBOX ? 
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' :
            'https://api.zarinpal.com/pg/v4/payment/request.json';
        
        $jsonData = json_encode($data);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            return array('status' => false, 'message' => $err);
        } else {
            $result = json_decode($result, true);
            
            if (isset($result['errors']) && !empty($result['errors'])) {
                return array(
                    'status' => false,
                    'message' => $result['errors']['message']
                );
            }
            
            if ($result['data']['code'] == 100) {
                $gateway_url = ai_assistant_get_zarinpal_gateway_url();
                return array(
                    'status' => true,
                    'url' => $gateway_url . $result['data']["authority"],
                    'authority' => $result['data']["authority"]
                );
            } else {
                return array(
                    'status' => false,
                    'message' => 'خطا در اتصال به درگاه پرداخت. کد خطا: ' . $result['data']['code']
                );
            }
        }
    }
    
    // تابع برای تأیید پرداخت
    public function verify_payment($authority, $amount) {
        $merchant_id = ai_assistant_get_zarinpal_merchant_id();
        
        $data = array(
            'merchant_id' => $merchant_id,
            'amount' => $amount,
            'authority' => $authority
        );
        
        // استفاده از API مناسب برای sandbox
        $api_url = ZARINPAL_SANDBOX ? 
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' :
            'https://api.zarinpal.com/pg/v4/payment/verify.json';
        
        $jsonData = json_encode($data);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            return array('status' => false, 'message' => $err);
        } else {
            $result = json_decode($result, true);
            
            if (isset($result['errors']) && !empty($result['errors'])) {
                return array(
                    'status' => false,
                    'message' => $result['errors']['message']
                );
            }
            
            if ($result['data']['code'] == 100) {
                return array(
                    'status' => true,
                    'ref_id' => $result['data']['ref_id']
                );
            } else {
                return array(
                    'status' => false,
                    'message' => 'پرداخت ناموفق بود. کد خطا: ' . $result['data']['code']
                );
            }
        }
    }
}