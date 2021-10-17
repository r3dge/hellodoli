<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NotificationRepository;  
use App\Service\DolibarrClientService;  
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Entity\AsyncMessage;  

class HelloAssoController extends AbstractController
{
    /**
     * @Route("/ ", name="helloasso")
     */
    public function index(NotificationRepository $notificationRepository, DolibarrClientService $dolibarr, MessageBusInterface $bus): Response
    {   
        // renvoie une erreur 404
        throw new NotFoundHttpException();
    }

}
