<?php
include './controllers/home.php';
include './helpers/telegram_requests.php';
include './configs/urls.php';
include './helpers/step_tracker.php';

$bot = new TelegramBot();
$home = new Home();
$step = new Step();

$data = $bot->getData();
$text = $data->text;
$chat_id = $data->chat->id;
$service_id = $time = null;


// FILE_PATH = "{$directory_path}/" . md5($chat_id) . ".txt";

if (!file_exists(DIRECTORY_PATH)) {
    mkdir(DIRECTORY_PATH, 0777, true);
}


if ($text === '/start') {
    $bot->sendMessage('Bot started');
    $home->getServices();
    $step->updateStep('service');
} else {
    $current_step = $step->currentStep();

    if ($current_step ==='finished') {
        $bot->sendMessage('Please enter /start command to start bot');
    }

    if (isset($data->id) && isset($data->data)) {
        $service_id = $current_step === 'service' ? substr($data->data, strlen("service_")) : '';
        $time = $current_step === 'time' ? substr($data->data, strlen("time_")) : '';
    }

    if ($service_id) {
        $params_array = ['service_id' => substr($data->data, strlen("service_"))];
        file_put_contents(FILE_PATH, serialize($params_array));
        $step->updateStep('pick_date');
        $bot->sendMessage('Enter date YYYY-MM-DD');
    }

    if ($current_step === 'pick_date') {
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)){
            if (file_exists(FILE_PATH)) {
                $params_array = unserialize(file_get_contents(FILE_PATH));
                $params_array['date'] = $text;
                $params_array['step'] = 'time';
                file_put_contents(FILE_PATH, serialize($params_array));
                $home->pickDate($params_array);
            } else {
                $bot->sendMessage('Service ID file not found');
            }
        }else{
            $bot->sendMessage('Invalid date. Please enter a valid date (YEAR-MONTH-DAY)');
        }
    }
    if ($time) {
        $params_array = unserialize(file_get_contents(FILE_PATH));
        $params_array['time'] = $time;
        $res = $home->pickTime($params_array);
        if ($res === 'ok') {
            $bot->sendMessage('Enter name');
            file_put_contents(FILE_PATH, serialize($params_array));
            $step->updateStep('name');
        }
    }
    if ($current_step === 'name') {
        if(!empty($text)){
            $params_array = unserialize(file_get_contents(FILE_PATH));
            $params_array['name'] = $text;
            $bot->sendMessage('Enter surname');
            file_put_contents(FILE_PATH, serialize($params_array));
            $step->updateStep('surname');
        }else{
            $bot->sendMessage('Please enter name');
        }

    }
    if ($current_step === 'surname') {
        if(!empty($text)){
            $params_array = unserialize(file_get_contents(FILE_PATH));
            $params_array['surname'] = $text;
            $bot->sendMessage('Enter email');
            file_put_contents(FILE_PATH, serialize($params_array));
            $step->updateStep('email');
        }else{
            $bot->sendMessage('Please enter surname');
        }
    }
    if ($current_step === 'email') {
        if (preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $text)) {
            $params_array = unserialize(file_get_contents(FILE_PATH));
            $params_array['email'] = $text;
            $bot->sendMessage('Enter phone');
            file_put_contents(FILE_PATH, serialize($params_array));
            $step->updateStep('phone');
        } else {
            $bot->sendMessage('Invalid email');
        }
    }
    if ($current_step === 'phone') {
        if (preg_match('/^\+\d{1,3}\d{6,14}$/', $text)) {
            $params_array = unserialize(file_get_contents(FILE_PATH));
            $params_array['phone'] = $text;
            $bot->sendMessage("Bring your companions along. Don't forget to specify the number.");
            file_put_contents(FILE_PATH, serialize($params_array));
            $step->updateStep('bring_people');
        } else {
            $bot->sendMessage('Invalid phone');
        }
    }
    if ($current_step === 'bring_people') {
        if (is_numeric($text)) {
            $params_array = unserialize(file_get_contents(FILE_PATH));
            $params_array['bring_people'] = $text;
            $res = $home->addInformation($params_array);
            if ($res === 'ok') {
                file_put_contents(FILE_PATH, serialize($params_array));
                $step->updateStep('confirm');
                $confirmButton[] = [
                    'text' => 'Confirm',
                    'callback_data' => 'confirm'
                ];
                $reply_markup = $bot->createInlineKeyboard([$confirmButton]);
                $message = "Confirm your reservation:\n" .
                    "Service: {$params_array['service_id']}\n" .
                    "Date: {$params_array['date']}\n" .
                    "Time: {$params_array['time']}\n" .
                    "Name: {$params_array['name']}\n" .
                    "Surname: {$params_array['surname']}\n" .
                    "Email: {$params_array['email']}\n" .
                    "Phone: {$params_array['phone']}\n";
                $bot->sendMessage($message, $reply_markup);
            }
        } else {
            $bot->sendMessage("Please enter a valid number.");
        }
    }

    if ($current_step === 'confirm' && ($data->data === 'confirm')) {
        $params_array = unserialize(file_get_contents(FILE_PATH));
        $params_array['bring_people'] = $text;
        $res = $home->confirmReservation($params_array);
        if ($res['status'] === 'ok') {
            $bot->sendMessage("Reservation confirmed.\n
            Your reservation ID: {$res['id']}");
            $step->updateStep('finished');
            // unlink(STEP_PATH);
            // unlink(FILE_PATH);
        } else {
            $bot->sendMessage('Reservation not confirmed');
        }
    }

}
