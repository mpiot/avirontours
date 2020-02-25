<?php

namespace App\Notification;

use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

class ShellDamageNotification extends Notification implements EmailNotificationInterface
{
    private $shellDamage;

    public function __construct(ShellDamage $shellDamage)
    {
        $this->shellDamage = $shellDamage;
        $this->importance(ShellDamageCategory::PRIORITY_HIGH === $shellDamage->getCategory()->getPriority() ? Notification::IMPORTANCE_URGENT : Notification::IMPORTANCE_MEDIUM);

        parent::__construct('Nouvelle avarie');
    }

    public function asEmailMessage(Recipient $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('emails/shell_damage_notification.html.twig')
            ->context(['shellDamage' => $this->shellDamage]);

        return $message;
    }
}
