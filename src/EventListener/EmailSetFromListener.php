<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailSetFromListener implements EventSubscriberInterface
{
    private $senderEmail;

    public function __construct(string $senderEmail)
    {
        $this->senderEmail = $senderEmail;
    }

    public function onMessage(MessageEvent $event)
    {
        $email = $event->getMessage();
        if (!$email instanceof Email) {
            return;
        }

        $email->from(new Address($this->senderEmail));
    }

    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'OnMessage',
        ];
    }
}
