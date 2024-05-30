<?php

$url = "https://sandbox.booknetic.com/sandboxes/sandbox-saas-6f49ae724d32a0cf3823/wp-admin/admin-ajax.php";

$postData = array(
    "payment_method" => "undefined",
    "deposit_full_amount" => 0,
    "client_time_zone" => "-",
    "google_recaptcha_token" => "undefined",
    "google_recaptcha_action" => "booknetic_booking_panel_1",
    "step" => "service",
    "cart" => json_encode(
        array(
            array(
                "location" => -1,
                "staff" => -1,
                "service_category" => "",
                "service" => "",
                "service_extras" => array(),
                "date" => "",
                "time" => "",
                "brought_people_count" => 0,
                "recurring_start_date" => "",
                "recurring_end_date" => "",
                "recurring_times" => "{}",
                "appointments" => "[]",
                "customer_data" => array()
            )
        )
    ),
    "current" => 0,
    "query_params" => json_encode(array()),
    "action" => "bkntc_get_data_service",
    "tenant_id" => 3
);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
curl_setopt($ch, CURLOPT_POST, true); 
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); 

$response = curl_exec($ch);
// echo $response;

if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    $responseArray = json_decode($response, true);

    $htmlContent = $responseArray['html'];
    $dom = new DOMDocument();
    $dom->loadHTML($htmlContent);
    
    $xpath = new DOMXPath($dom);

    $serviceCards = $xpath->query('//div[@class="booknetic_service_card"]');

    foreach ($serviceCards as $serviceCard) {
        $serviceName = $xpath->query('.//span[@class="booknetic_service_title_span"]', $serviceCard)->item(0)->nodeValue;
        $servicePrice = $xpath->query('.//div[@class="booknetic_service_card_price"]', $serviceCard)->item(0)->nodeValue;
        echo "Service Name: $serviceName, Price: $servicePrice\n";
    }
}

curl_close($ch);

