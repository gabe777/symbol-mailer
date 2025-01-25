<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class HistoricalDataMailService
{
    private const string FROM_MAIL = 'no-reply@example.com';

    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    public function send(string $recipient, string $subject, string $body, string $csvContent): void
    {
        $email = (new Email())->from(self::FROM_MAIL)->to($recipient)->subject($subject)->text($body)->attach(
            $csvContent,
            'historical_data.csv',
            'text/csv'
        );

        $this->mailer->send($email);
    }

}