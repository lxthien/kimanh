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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\DashboardService;

/**
 * 
 * @Route("/admin")
 * @Route("/admin/dashboard")
 * @Security("has_role('ROLE_ADMIN')")
 */

class DashboardController extends Controller
{
    private $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     *
     * @Route("/", name="admin_dashboard_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        // Overall stats
        $stats = $this->dashboardService->getOverallStats();
        
        // Today stats
        $todayStats = $this->dashboardService->getTodayStats();
        
        // Comment stats
        $commentStats = $this->dashboardService->getCommentStats();
        
        // Recent posts
        $recentPosts = $this->dashboardService->getRecentPosts(8);
        
        // Pending comments
        $pendingComments = $this->dashboardService->getPendingComments(5);
        
        // Top viewed posts
        $topViewedPosts = $this->dashboardService->getTopViewedPosts(5);
        
        // Recent activity
        $recentActivity = $this->dashboardService->getRecentActivity(15);
        
        // View trends
        $viewTrends = $this->dashboardService->getViewTrends();
        
        // Posts by category
        $postsByCategory = $this->dashboardService->getPostsByCategory();

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'todayStats' => $todayStats,
            'commentStats' => $commentStats,
            'recentPosts' => $recentPosts,
            'pendingComments' => $pendingComments,
            'topViewedPosts' => $topViewedPosts,
            'recentActivity' => $recentActivity,
            'viewTrends' => json_encode($viewTrends),
            'postsByCategory' => json_encode($postsByCategory)
        ]);
    }

    /**
     * @Route("/api/view-trends", name="api_view_trends")
     */
    public function viewTrendsAction()
    {
        $trends = $this->dashboardService->getViewTrends();
        return new JsonResponse($trends);
    }

    /**
     * @Route("/api/posts-by-category", name="api_posts_category")
     */
    public function postsByCategoryAction()
    {
        $data = $this->dashboardService->getPostsByCategory();
        return new JsonResponse($data);
    }

    /**
     * @Route("/api/comment-stats", name="api_comment_stats")
     */
    public function commentStatsAction()
    {
        $stats = $this->dashboardService->getCommentStats();
        return new JsonResponse($stats);
    }

    /**
     * @Route("/api/recent-activity", name="api_recent_activity")
     */
    public function recentActivityAction()
    {
        $activity = $this->dashboardService->getRecentActivity(20);
        return new JsonResponse($activity);
    }
}