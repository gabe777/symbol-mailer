<?php

namespace App\Tests\Unit\Service;

use App\Service\HistoricalDataMailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;

class HistoricalDataMailServiceTest extends TestCase
{

    public function testGetEmail()
    {
        $service = new HistoricalDataMailService($this->createMock(MailerInterface::class));
        $mail = $service->getEmail();
        assertInstanceOf(Email::class, $mail);
        assertSame($mail, $service->getEmail());
    }

    public function testSend()
    {
        $recipient = 'recipient';
        $subject = 'subject';
        $body = 'body';
        $csvContent = 'csvContent';

        $mail = $this->createMock(Email::class);
        $mail->expects($this->once())->method('from')->willReturn($mail);
        $mail->expects($this->once())->method('to')->with($recipient)->willReturn($mail);
        $mail->expects($this->once())->method('subject')->with($subject)->willReturn($mail);
        $mail->expects($this->once())->method('text')->with($body)->willReturn($mail);
        $mail->expects($this->once())->method('attach')->with($csvContent)->willReturn($mail);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($mail);

        $service = $this->getMockBuilder(HistoricalDataMailService::class)->setConstructorArgs([$mailer])->onlyMethods(
                ['getEmail']
            )->getMock();

        $service->method('getEmail')->willReturn($mail);
        $return = $service->send($recipient, $subject, $body, $csvContent);
        assertSame($mail, $return);
    }
}
