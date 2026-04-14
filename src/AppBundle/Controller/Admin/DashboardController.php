<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\News;
use AppBundle\Entity\Comment;
use AppBundle\Entity\User;
use AppBundle\Entity\Contact;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Dashboard Controller
 * 
 * @Route("/admin")
 * @Route("/admin/dashboard")
 * @Security("has_role('ROLE_ADMIN')")
 */

class DashboardController extends Controller
{
    /**
     * Display dashboard with statistics
     * 
     * @Route("/", name="admin_dashboard_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        
        // Total counts
        $totalPosts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $totalComments = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
            
        $totalUsers = $em->getRepository(User::class)->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Total views
        $viewsData = $em->getRepository(News::class)->createQueryBuilder('n')
            ->select('SUM(n.viewCounts) as totalViews')
            ->getQuery()
            ->getOneOrNullResult();
        $totalViews = $viewsData['totalViews'] ?? 0;
        
        // Recent posts (last 7 days)
        $recentPosts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->select('n.id, n.title, n.postType, n.createdAt, n.viewCounts')
            ->where('n.createdAt >= :week_ago')
            ->setParameter('week_ago', new \DateTime('-7 days'))
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        // Recent comments
        $recentComments = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->select('c.id, c.author, c.createdAt, c.approved, c.news_id')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
        
        // View trends - last 30 days
        $viewTrends = $this->getViewTrendsByDate();
        
        // Approved vs Pending comments
        $approvedComments = $em->getRepository(Comment::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.approved = :approved')
            ->setParameter('approved', true)
            ->getQuery()
            ->getSingleScalarResult();
            
        $pendingComments = $totalComments - $approvedComments;
        
        // Top 5 posts by views
        $topPosts = $em->getRepository(News::class)->createQueryBuilder('n')
            ->select('n.id, n.title, n.postType, n.viewCounts')
            ->orderBy('n.viewCounts', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/dashboard/index.html.twig', [
            'totalPosts' => $totalPosts,
            'totalComments' => $totalComments,
            'totalUsers' => $totalUsers,
            'totalViews' => $totalViews,
            'approvedComments' => $approvedComments,
            'pendingComments' => $pendingComments,
            'recentPosts' => $recentPosts,
            'recentComments' => $recentComments,
            'topPosts' => $topPosts,
            'viewTrends' => $viewTrends,
        ]);
    }

    /**
     * @Route("/notifications/feed", name="admin_notifications_feed")
     * @Method("GET")
     */
    public function notificationsFeedAction()
    {
        $notifications = $this->buildAdminNotifications();

        return new JsonResponse([
            'total' => $notifications['total'],
            'html' => $this->renderView('admin/layout/_notifications_menu.html.twig', [
                'notifications' => $notifications,
            ]),
        ]);
    }
    
    /**
     * Get view trends for last 30 days
     */
    private function getViewTrendsByDate()
    {
        $em = $this->getDoctrine()->getManager();
        $trends = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $dateStr = $date->format('Y-m-d');
            
            // Set start and end of day
            $startOfDay = clone $date;
            $startOfDay->setTime(0, 0, 0);
            
            $endOfDay = clone $date;
            $endOfDay->setTime(23, 59, 59);
            
            $result = $em->getRepository(News::class)->createQueryBuilder('n')
                ->select('SUM(n.viewCounts) as views')
                ->where('n.updatedAt >= :startDate AND n.updatedAt <= :endDate')
                ->setParameter('startDate', $startOfDay)
                ->setParameter('endDate', $endOfDay)
                ->getQuery()
                ->getOneOrNullResult();
            
            $trends[$dateStr] = (int)($result['views'] ?? 0);
        }
        
        return $trends;
    }

    private function buildAdminNotifications()
    {
        $contactRepository = $this->getDoctrine()->getRepository(Contact::class);
        $commentRepository = $this->getDoctrine()->getRepository(Comment::class);
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $contactCount = $contactRepository->countUnread();
        $commentCount = $commentRepository->countPending();
        $userCount = $userRepository->countUnreadRegistrationNotifications();

        return [
            'total' => $contactCount + $commentCount + $userCount,
            'contacts' => [
                'count' => $contactCount,
                'items' => $contactRepository->findUnreadNotifications(),
            ],
            'comments' => [
                'count' => $commentCount,
                'items' => $commentRepository->findPendingNotifications(),
            ],
            'users' => [
                'count' => $userCount,
                'items' => $userRepository->findUnreadRegistrationNotifications(),
            ],
        ];
    }
}
