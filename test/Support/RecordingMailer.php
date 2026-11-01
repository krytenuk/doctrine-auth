<?php

declare(strict_types=1);

namespace FwsDoctrineAuthTest\Support;

use DateTimeImmutable;
use FwsDoctrineAuth\PasswordReset\AuthMailerInterface;
use RuntimeException;

final class RecordingMailer implements AuthMailerInterface
{
    /** @var list<array{user: object, token: string, expiresAt: DateTimeImmutable}> */
    public array $sent = [];

    public bool $fail = false;

    public function sendPasswordReset(object $user, string $token, DateTimeImmutable $expiresAt): void
    {
        if ($this->fail) {
            throw new RuntimeException('Mail server down');
        }

        $this->sent[] = ['user' => $user, 'token' => $token, 'expiresAt' => $expiresAt];
    }

    public function lastToken(): string
    {
        return $this->sent[array_key_last($this->sent)]['token'];
    }
}
