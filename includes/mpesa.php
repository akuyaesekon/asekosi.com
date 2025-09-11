<?php
class Mpesa {
    private $consumerKey;
    private $consumerSecret;
    private $shortcode;
    private $passkey;
    private $callbackUrl;
    private $env;

    public function __construct() {
        $this->consumerKey = MPESA_CONSUMER_KEY;
        $this->consumerSecret = MPESA_CONSUMER_SECRET;
        $this->shortcode = MPESA_SHORTCODE;
        $this->passkey = MPESA_PASSKEY;
        $this->callbackUrl = MPESA_CALLBACK_URL;
        $this->env = MPESA_ENV;
    }

    private function getBaseUrl() {
        return $this->env == 'sandbox'
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    private function normalizePhone($phone) {
        $phone = preg_replace('/\D/', '', $phone); // remove non-digits
        if (substr($phone, 0, 1) == '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (substr($phone, 0, 3) != '254') {
            $phone = '254' . $phone;
        }
        return $phone;
    }

    public function getAccessToken() {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        $url = $this->getBaseUrl() . '/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            file_put_contents(__DIR__ . '/mpesa_log.txt', date('c') . " AccessToken Error: $response\n", FILE_APPEND);
            return false;
        }

        $result = json_decode($response);
        return $result->access_token ?? false;
    }

    public function stkPush($phone, $amount, $accountReference, $transactionDesc) {
        $phone = $this->normalizePhone($phone);
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['error' => 'Failed to get access token'];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => MPESA_TRANSACTION_TYPE,
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Always log response for debugging
        file_put_contents(__DIR__ . '/mpesa_log.txt', date('c') . " STK Response: $response\n", FILE_APPEND);

        if ($httpCode != 200) {
            return ['error' => 'STK push failed (HTTP ' . $httpCode . ')'];
        }

        return json_decode($response, true);
    }

    // handleCallback() remains the same...
}

// Initialize M-Pesa
$mpesa = new Mpesa();

function processPayment($phone, $amount, $orderId) {
    global $mpesa;

    $accountReference = "AsekosiGo Order $orderId";
    $transactionDesc = "Payment for Order $orderId";

    $response = $mpesa->stkPush($phone, $amount, $accountReference, $transactionDesc);

    if (isset($response['error'])) {
        return ['success' => false, 'error' => $response['error']];
    }

    if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
        return [
            'success' => true,
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID']
        ];
    }

    return [
        'success' => false,
        'error' => $response['errorMessage'] ?? 'Payment initiation failed'
    ];
}
