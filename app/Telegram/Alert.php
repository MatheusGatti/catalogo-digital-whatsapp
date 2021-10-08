<?php

namespace App\Telegram;

use TelegramBot\Api\BotApi;

class Alert
{

    /**
     * Token de acesso ao bot
     * @var string
     */
    const TELEGRAM_BOT_TOKEN = '';

    /**
     * ID da conversa/canal/grupo com o bot
     * @var integer
     */
    const TELEGRAM_CHAT_ID = null;

    /**
     * Método responsável por enviar a mensagem de alerta
     * @param string $message
     * @param string $parseMode
     * @param string $replayMarkup
     * @return boolean
     */
    public static function sendMessage($message, $parseMode = 'html')
    {
        //INSTÂNCIA DO BOT COM O TOKEN GERADO
        $objBotApi = new BotApi(self::TELEGRAM_BOT_TOKEN);

        //ENVIA A MENSAGEM PARA O TELEGRAM
        return $objBotApi->sendMessage(self::TELEGRAM_CHAT_ID, $message, $parseMode, true);
    }
}