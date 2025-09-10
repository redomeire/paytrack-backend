<?php

namespace App\Dto;

readonly class NotificationDto
{
    public function __construct(
        public string $userId,
        public string $billId,
        public string $title,
        public string $message,
        public string $type = 'Payment_Status_Success',
        public string $description = ''
    ) {}
}