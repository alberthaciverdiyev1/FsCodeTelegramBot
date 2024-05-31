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

curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData)
)
);

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    $responseArray = json_decode($response, true);
    $html_entity = $responseArray['html'];
    $html_content = html_entity_decode($html_entity);
    $pattern = '/<div class="booknetic_service_card demo booknetic_fade" data-id="(\d+)"[^>]*>.*?<span class="booknetic_service_title_span">(.*?)<\/span>.*? <div class="booknetic_service_card_price " data-price="(.*?)">(.*?)<\/div>/s';

// Eşleşmeleri bul
preg_match_all($pattern, $html_content, $matches, PREG_SET_ORDER);

// Eşleşmeleri görüntüle
foreach ($matches as $match) {
    echo "data-id: " . $match[1] . "<br/>";
    echo "Service Name: " . $match[2] . "<br/>";
    echo "Price: " . $match[3] . "<br/>";
}


 
    
}
