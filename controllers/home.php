<?php
// include 'helpers/telegram_requests.php';
// require_once 'helpers/telegram_requests.php';
class Home
{
    public function __construct()
    {
        $bot = new TelegramBot();
        $bot->getData();
    }


    public function request($step = null, $action = null, $array = [], $year = '', $month = '', $payment_method = '')
    {
        $postData = array(
            "payment_method" => $payment_method,
            "deposit_full_amount" => 0,
            "client_time_zone" => "-",
            "google_recaptcha_token" => "undefined",
            "google_recaptcha_action" => "booknetic_booking_panel_1",
            "step" => $step,
            "cart" => json_encode(
                array(
                    $array
                )
            ),
            "year" => $year,
            "month" => $month,
            "current" => 0,
            "query_params" => json_encode(array()),
            "action" => $action,
            "tenant_id" => 3
        );

        $ch = curl_init('https://sandbox.booknetic.com/sandboxes/sandbox-saas-6f49ae724d32a0cf3823/wp-admin/admin-ajax.php');

        curl_setopt_array(
            $ch,
            array(
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
            return $response;
        }

    }
    public function addCollection($service_id = '', $date = '', $time = '', $customer_data = [])
    {
        $bot = new TelegramBot();

        $params = [
            "location" => -1,
            "staff" => -1,
            "service_category" => "",
            "service" => $service_id,
            "service_extras" => array(),
            "date" => $date,
            "time" => $time,
            "brought_people_count" => 0,
            "recurring_start_date" => "",
            "recurring_end_date" => "",
            "recurring_times" => "{}",
            "appointments" => "[]",
            "customer_data" => $customer_data
        ];
        $bot->sendMessage($params);
        return $params;
    }

    public function getServices()
    {
        $bot = new TelegramBot();
        $step = new Step();

        $bot->getData();
        $params = $this->addCollection();
        $response = $this->request('service', 'bkntc_get_data_service', $params);

        $responseArray = json_decode($response, true);
        $html_entity = $responseArray['html'];
        $html_content = html_entity_decode($html_entity);
        $pattern = '/<div class="booknetic_service_card demo booknetic_fade" data-id="(\d+)"[^>]*>.*?<span class="booknetic_service_title_span">(.*?)<\/span>.*? <div class="booknetic_service_card_price " data-price="(.*?)">(.*?)<\/div>/s';

        preg_match_all($pattern, $html_content, $matches, PREG_SET_ORDER);
        $row = [];
        foreach ($matches as $match) {
            $row[] = [
                'text' => '#' . $match[1] . " - " . $match[2] . " - " . $match[3],
                'callback_data' => 'service_' . $match[1]
            ];

            if (count($row) == 2) {
                $buttons[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) {
            $buttons[] = $row;
        }

        if (!empty($buttons)) {
            $step->updateStep('service');
            $reply_markup = $bot->createInlineKeyboard($buttons);
            $bot->sendMessage('Select Service:', $reply_markup);
        } else {
            $bot->sendMessage('No services found.');
        }
    }

    function pickDate($params)
    {
        $bot = new TelegramBot();
        $step = new Step();
        $bot->getData();
        $buttons = [];
        $service_id = $params['service_id'];
        $date = $params['date'];
        $date_parts = explode('-', $date);

        $year = $date_parts[0];
        $month = $date_parts[1];
        $day = $date_parts[2];


        $collection = $this->addCollection($service_id, $date);

        $response = $this->request('date_time', 'bkntc_get_data_date_time', $collection, $year, $month, $day);
        $responseArray = json_decode($response, true);

        if (isset($responseArray['data']['dates'])) {
            $dates = $responseArray['data']['dates'];
            $row = [];
            foreach ($dates[$date] as $time) {
                $row[] = [
                    'text' => $time['start_time'] . " - " . $time['end_time'],
                    'callback_data' => 'time_' . $time['start_time']
                ];

                if (count($row) == 3) {
                    $buttons[] = $row;
                    $row = [];
                }
            }
            if (!empty($row)) {
                $buttons[] = $row;
            }

            if (!empty($buttons)) {
                $step->updateStep('time');
                $reply_markup = $bot->createInlineKeyboard($buttons);
                $bot->sendMessage('Choose time:', $reply_markup);
            } else {
                $bot->sendMessage('Sorry, but there are no available slots at the selected date and time. Please choose another date.');
            }
        } else {
            $bot->sendMessage('Dates data not found in response');
        }


    }

    function pickTime($params)
    {
        $bot = new TelegramBot();
        $bot->getData();
        $service_id = $params['service_id'];
        $date = $params['date'];
        $time = $params['time'];

        $collection = $this->addCollection($service_id, $date, $time);
        $response = $this->request('information', 'bkntc_get_data_information', $collection);

        $response = json_decode($response, true);
        return $response['status'];
    }
    function addInformation($params)
    {
        $bot = new TelegramBot();
        $bot->getData();
        $service_id = $params['service_id'];
        $date = $params['date'];
        $time = $params['time'];
        $customer_data = array(
            "first_name" => $params['name'],
            "last_name" => $params['surname'],
            "email" => $params['email'],
            "phone" => $params['phone']
        );
        $collection = $this->addCollection($service_id, $date, $time, $customer_data);

        $response = $this->request('confirm_details', 'bkntc_get_data_confirm_details', $collection);
        $response = json_decode($response, true);
        return $response['status'];
    }
    function confirmReservation($params)
    {
        $bot = new TelegramBot();
        $bot->getData();
        $service_id = $params['service_id'];
        $date = $params['date'];
        $time = $params['time'];
        $customer_data = array(
            "first_name" => $params['name'],
            "last_name" => $params['surname'],
            "email" => $params['email'],
            "phone" => $params['phone']
        );
        $collection = $this->addCollection($service_id, $date, $time, $customer_data);

        $response = $this->request('confirm', 'bkntc_confirm', $collection, null, null, 'local');
        $response = json_decode($response, true);
        $data['id'] = $response['id'];
        $data['status'] = $response['status'];
        return $data;


    }

}

