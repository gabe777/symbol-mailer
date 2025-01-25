<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class HistoricalDataMailService
{
    private const string FROM_MAIL = 'no-reply@example.com';

    private ?Email $mail = null;

    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function getEmail(): Email
    {
        if (null === $this->mail) {
            $this->mail = new Email();
        }

        return $this->mail;
    }

    public function send(string $recipient, string $subject, string $body, string $csvContent): Email
    {
        $this->getEmail()->from(self::FROM_MAIL)->to($recipient)->subject($subject)->text($body)->attach(
            $csvContent,
            'historical_data.csv',
            'text/csv'
        );

        $this->mailer->send($this->getEmail());

        return $this->getEmail();
    }

}