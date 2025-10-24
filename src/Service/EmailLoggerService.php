<?php

namespace App\Service;

use App\Entity\EmailLog;
use Doctrine\ORM\EntityManagerInterface;

class EmailLoggerService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function logEmail(
        string $recipient,
        string $subject,
        string $status,
        string $emailType,
        ?string $errorMessage = null,
        ?array $context = null
    ): void {
        $log = new EmailLog();
        $log->setRecipient($recipient);
        $log->setSubject($subject);
        $log->setStatus($status);
        $log->setEmailType($emailType);
        $log->setErrorMessage($errorMessage);
        $log->setSentAt(new \DateTimeImmutable());
        
        if ($context) {
            $log->setContext(json_encode($context, JSON_PRETTY_PRINT));
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logSuccess(string $recipient, string $subject, string $emailType, ?array $context = null): void
    {
        $this->logEmail($recipient, $subject, 'success', $emailType, null, $context);
    }

    public function logError(string $recipient, string $subject, string $emailType, string $errorMessage, ?array $context = null): void
    {
        $this->logEmail($recipient, $subject, 'error', $emailType, $errorMessage, $context);
    }
}
