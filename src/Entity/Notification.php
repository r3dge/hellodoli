<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Action\NotFoundAction;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 * collectionOperations={
 *         "get"={
 *             "controller"=NotFoundAction::class,
 *             "read"=true,
 *             "output"=false,
 *         },
 *         "post"={"messenger" = true, "output" = false, "status" = 202} 
 *     },
 *     itemOperations={
 *         "get"={
 *             "controller"=NotFoundAction::class,
 *             "read"=true,
 *             "output"=false,
 *         },
 *     }
  * )
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
{
    const EVENT_TYPE_ORDER = 'Order';
    const EVENT_TYPE_PAYMENT = 'Payment';
    const EVENT_TYPE_FORM = 'Form';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $eventType;

    /**
     * @ORM\Column(type="json")
     */
    private $data = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
