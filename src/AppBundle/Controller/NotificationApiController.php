<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/notifications")
 */
class NotificationApiController extends Controller
{
    /**
     * Lấy thông báo chưa đọc
     * @Route("/unread", name="api_notifications_unread")
     * @Method("GET")
     */
    public function unreadAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notificationService = $this->get('app.notification_service');
        $notifications = $notificationService->getUnreadNotifications($user->getId());

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'relatedId' => $notification->getRelatedId(),
                'relatedType' => $notification->getRelatedType(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'isRead' => $notification->isIsRead(),
            ];
        }

        return new JsonResponse([
            'count' => count($data),
            'notifications' => $data
        ]);
    }

    /**
     * Lấy số thông báo chưa đọc
     * @Route("/count", name="api_notifications_count")
     * @Method("GET")
     */
    public function countAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notificationService = $this->get('app.notification_service');
        $count = $notificationService->countUnreadNotifications($user->getId());

        return new JsonResponse(['count' => $count]);
    }

    /**
     * Lấy thông báo gần đây
     * @Route("/recent", name="api_notifications_recent")
     * @Method("GET")
     */
    public function recentAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $limit = $request->query->get('limit', 10);
        $notificationService = $this->get('app.notification_service');
        $notifications = $notificationService->getRecentNotifications($user->getId(), $limit);

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'relatedId' => $notification->getRelatedId(),
                'relatedType' => $notification->getRelatedType(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'isRead' => $notification->isIsRead(),
            ];
        }

        return new JsonResponse([
            'count' => count($data),
            'notifications' => $data
        ]);
    }

    /**
     * Đánh dấu thông báo là đã đọc
     * @Route("/{id}/mark-read", name="api_notification_mark_read")
     * @Method("POST")
     */
    public function markReadAction($id)
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notificationService = $this->get('app.notification_service');
        $notificationService->markAsRead($id);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     * @Route("/mark-all-read", name="api_notifications_mark_all_read")
     * @Method("POST")
     */
    public function markAllReadAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notificationService = $this->get('app.notification_service');
        $notificationService->markAllAsRead($user->getId());

        return new JsonResponse(['success' => true]);
    }
}
