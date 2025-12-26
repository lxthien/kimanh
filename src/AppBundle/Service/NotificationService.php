<?php

namespace AppBundle\Service;

use AppBundle\Entity\Notification;
use Doctrine\Common\Persistence\ManagerRegistry;

class NotificationService
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Lấy entity manager
     */
    private function getEm()
    {
        return $this->doctrine->getManager();
    }

    /**
     * Tạo thông báo cho Comment chưa duyệt
     */
    public function notifyPendingComment($comment)
    {
        $em = $this->getEm();

        // Lấy admin users
        $adminUsers = $em->getRepository('AppBundle:User')
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        foreach ($adminUsers as $admin) {
            $notification = new Notification();
            $notification->setUserId($admin->getId());
            $notification->setType('comment_pending');
            $notification->setTitle('Bình luận mới cần duyệt');
            $notification->setMessage('Có bình luận mới từ ' . ($comment->getUser() ? $comment->getUser()->getUsername() : $comment->getName()) . ' trong bài viết "' . $comment->getNews()->getTitle() . '"');
            $notification->setRelatedId($comment->getId());
            $notification->setRelatedType('Comment');
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());

            $em->persist($notification);
        }

        $em->flush();
    }

    /**
     * Tạo thông báo cho Contact Request mới
     */
    public function notifyNewContact($contact)
    {
        $em = $this->getEm();

        // Lấy admin users
        $adminUsers = $em->getRepository('AppBundle:User')
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        foreach ($adminUsers as $admin) {
            $notification = new Notification();
            $notification->setUserId($admin->getId());
            $notification->setType('contact_new');
            $notification->setTitle('Yêu cầu liên hệ mới');
            $notification->setMessage('Yêu cầu liên hệ từ ' . $contact->getName() . ' (' . $contact->getEmail() . ')');
            $notification->setRelatedId($contact->getId());
            $notification->setRelatedType('Contact');
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());

            $em->persist($notification);
        }

        $em->flush();
    }

    /**
     * Lấy thông báo chưa đọc
     */
    public function getUnreadNotifications($userId)
    {
        return $this->getEm()->getRepository('AppBundle:Notification')
            ->getUnreadNotifications($userId);
    }

    /**
     * Lấy số thông báo chưa đọc
     */
    public function countUnreadNotifications($userId)
    {
        return $this->getEm()->getRepository('AppBundle:Notification')
            ->countUnreadNotifications($userId);
    }

    /**
     * Lấy thông báo gần đây
     */
    public function getRecentNotifications($userId, $limit = 10)
    {
        return $this->getEm()->getRepository('AppBundle:Notification')
            ->getRecentNotifications($userId, $limit);
    }

    /**
     * Đánh dấu thông báo là đã đọc
     */
    public function markAsRead($notificationId)
    {
        $em = $this->getEm();
        $notification = $em->getRepository('AppBundle:Notification')->find($notificationId);
        if ($notification) {
            $notification->setIsRead(true);
            $notification->setReadAt(new \DateTime());
            $em->persist($notification);
            $em->flush();
        }
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     */
    public function markAllAsRead($userId)
    {
        $this->getEm()->getRepository('AppBundle:Notification')->markAllAsRead($userId);
    }
}
