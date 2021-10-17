<?php

namespace App\AsyncMessageHandler;

use App\Entity\Notification;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Service\DolibarrClientService; 
use App\Repository\NotificationRepository;   

class AsyncMessageHandler implements MessageHandlerInterface
{

    private $dolibarrClientService;
    private $notificationRepository;

    public function __construct(DolibarrClientService $dolibarrClientService)
    {
        $this->dolibarrClientService = $dolibarrClientService;
    }

    public function __invoke(Notification $notification)
    {
        $this->dolibarrClientService->processNotification($notification); 
    }
}