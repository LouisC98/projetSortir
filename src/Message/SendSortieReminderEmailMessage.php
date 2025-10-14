<?php

namespace App\Message;

final class SendSortieReminderEmailMessage
{
    public function __construct(
        private readonly int $sortieId
    ) {
    }

    public function getSortieId(): int
    {
        return $this->sortieId;
    }
}
