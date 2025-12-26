<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Contact;
use AppBundle\Service\NotificationService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ContactNotificationSubscriber implements EventSubscriber
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getSubscribedEvents()
    {
        return ['postPersist'];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof Contact) {
            return;
        }

        // Tạo thông báo khi có Contact mới
        $this->notificationService->notifyNewContact($entity);
    }
}
