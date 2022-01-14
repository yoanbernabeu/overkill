<?php

namespace App\MessageHandler;

use App\Message\UploadMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UploadMessageHandler implements MessageHandlerInterface
{
    public function __invoke(UploadMessage $message)
    {
        dump($message);
    }
}