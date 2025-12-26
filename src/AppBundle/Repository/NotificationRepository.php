<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Notification;
use Doctrine\ORM\EntityRepository;

class NotificationRepository extends EntityRepository
{
    /**
     * Lấy thông báo chưa đọc của user
     */
    public function getUnreadNotifications($userId)
    {
        return $this->createQueryBuilder('n')
            ->where('n.user_id = :userId')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->orderBy('n.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Lấy số lượng thông báo chưa đọc
     */
    public function countUnreadNotifications($userId)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user_id = :userId')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('userId', $userId)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Lấy các thông báo mới nhất
     */
    public function getRecentNotifications($userId, $limit = 10)
    {
        return $this->createQueryBuilder('n')
            ->where('n.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Đánh dấu thông báo là đã đọc
     */
    public function markAsRead($notificationId)
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.is_read', true)
            ->set('n.read_at', 'CURRENT_TIMESTAMP()')
            ->where('n.id = :id')
            ->setParameter('id', $notificationId)
            ->getQuery()
            ->execute();
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     */
    public function markAllAsRead($userId)
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.is_read', true)
            ->set('n.read_at', 'CURRENT_TIMESTAMP()')
            ->where('n.user_id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    /**
     * Xóa thông báo cũ hơn X ngày
     */
    public function deleteOldNotifications($days = 30)
    {
        $date = new \DateTime();
        $date->modify('-' . $days . ' days');

        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.created_at < :date')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('date', $date)
            ->setParameter('isRead', true)
            ->getQuery()
            ->execute();
    }
}
