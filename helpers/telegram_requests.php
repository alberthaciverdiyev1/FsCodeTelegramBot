<?php
require 'configs/urls.php';
class TelegramBot
{
    public $token;
    public $chat_id;
    
    const API_URL = 'https://api.telegram.org/bot';

    public function request($method, $post)
    {
        $ch = curl_init();
        $url = self::API_URL . BOT_TOKEN . '/' . $method;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setWebhook()
    {
        return $this->request('setWebhook', [
            'url' => WEBHOOK_URL
        ]);
    }

    public function getData()
    {
        $data = json_decode(file_get_contents('php://input'));
        if (isset($data->message)) {
            $this->chat_id = $data->message->chat->id;
            return $data->message;
        } elseif (isset($data->callback_query)) {
            $this->chat_id = $data->callback_query->message->chat->id;
            return $data->callback_query;
        }
        return null;
    }

    public function sendMessage($message, $reply_markup = null)
    {
        $post = [
            'chat_id' => $this->chat_id,
            'text' => $message
        ];
        if ($reply_markup) {
            $post['reply_markup'] = $reply_markup;
        }
        return $this->request('sendMessage', $post);
    }

    public function createInlineKeyboard($buttons)
    {
        $inlineKeyboard = ['inline_keyboard' => $buttons];
        return json_encode($inlineKeyboard);
    }

    public function answerCallbackQuery($callback_id, $text)
    {
        $post = [
            'callback_query_id' => $callback_id,
            'text' => $text,
            'show_alert' => false 
        ];
        return $this->request('answerCallbackQuery', $post);
    }
}

