<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Comment;
use AppBundle\Service\NotificationService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class CommentNotificationSubscriber implements EventSubscriber
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

        if (!$entity instanceof Comment) {
            return;
        }

        // Chỉ tạo thông báo nếu comment ở trạng thái pending
        if ($entity->getStatus() === 'pending' || $entity->getStatus() === null) {
            $this->notificationService->notifyPendingComment($entity);
        }
    }
}
