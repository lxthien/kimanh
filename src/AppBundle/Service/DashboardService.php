<?php

namespace AppBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use AppBundle\Entity\News;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Notification;
use AppBundle\Entity\Contact;

class DashboardService
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get overall statistics
     */
    public function getOverallStats()
    {
        $em = $this->doctrine->getManager();

        // Total posts
        $totalPosts = $em->createQuery(
            'SELECT COUNT(n) FROM AppBundle:News n WHERE n.enable = 1'
        )->getSingleScalarResult();

        // Total comments
        $totalComments = $em->createQuery(
            'SELECT COUNT(c) FROM AppBundle:Comment c'
        )->getSingleScalarResult();

        // Pending comments
        $pendingComments = $em->createQuery(
            'SELECT COUNT(c) FROM AppBundle:Comment c WHERE c.approved = 0'
        )->getSingleScalarResult();

        // Total views
        $totalViews = $em->createQuery(
            'SELECT SUM(n.viewCounts) FROM AppBundle:News n WHERE n.enable = 1'
        )->getSingleScalarResult() ?: 0;

        // Total contacts/messages
        $totalContacts = $em->createQuery(
            'SELECT COUNT(c) FROM AppBundle:Contact c'
        )->getSingleScalarResult();

        // Unread notifications
        $unreadNotifications = $em->createQuery(
            'SELECT COUNT(n) FROM AppBundle:Notification n WHERE n.is_read = 0'
        )->getSingleScalarResult();

        return [
            'totalPosts' => $totalPosts,
            'totalComments' => $totalComments,
            'pendingComments' => $pendingComments,
            'totalViews' => $totalViews,
            'totalContacts' => $totalContacts,
            'unreadNotifications' => $unreadNotifications
        ];
    }

    /**
     * Get recent posts
     */
    public function getRecentPosts($limit = 10)
    {
        $em = $this->doctrine->getManager();

        // Get recent posts
        $posts = $em->createQuery(
            'SELECT n.id, n.title, n.enable, n.createdAt, n.viewCounts FROM AppBundle:News n WHERE n.enable = 1 ORDER BY n.createdAt DESC'
        )
        ->setMaxResults($limit)
        ->getResult();

        // Get comment counts separately
        $commentCounts = $em->createQuery(
            'SELECT c.news_id, COUNT(c) as total FROM AppBundle:Comment c GROUP BY c.news_id'
        )
        ->getResult();

        // Build a lookup map
        $commentMap = [];
        foreach ($commentCounts as $cnt) {
            $commentMap[$cnt['news_id']] = $cnt['total'];
        }

        // Add comment counts to posts
        foreach ($posts as &$post) {
            $post['commentCount'] = $commentMap[$post['id']] ?? 0;
        }

        return $posts;
    }

    /**
     * Get pending comments
     */
    public function getPendingComments($limit = 10)
    {
        $em = $this->doctrine->getManager();

        // Get pending comments
        $pendingComments = $em->createQuery(
            'SELECT c.id, c.author, c.content, c.createdAt, c.news_id FROM AppBundle:Comment c WHERE c.approved = 0 ORDER BY c.createdAt DESC'
        )
        ->setMaxResults($limit)
        ->getResult();

        // Get news titles by ID
        if (!empty($pendingComments)) {
            $newsIds = array_map(function($c) { return $c['news_id']; }, $pendingComments);
            
            // Fetch all needed news posts
            $newsList = $em->createQuery(
                'SELECT n.id, n.title, n.url FROM AppBundle:News n WHERE n.id IN (:newsIds)'
            )
            ->setParameter('newsIds', $newsIds)
            ->getResult();

            // Build news lookup map
            $newsMap = [];
            foreach ($newsList as $news) {
                $newsMap[$news['id']] = $news;
            }

            // Add news info to comments
            foreach ($pendingComments as &$comment) {
                $newsId = $comment['news_id'];
                $comment['postTitle'] = $newsMap[$newsId]['title'] ?? 'N/A';
                $comment['postUrl'] = $newsMap[$newsId]['url'] ?? '#';
            }
        }

        return $pendingComments;
    }

    /**
     * Get top viewed posts
     */
    public function getTopViewedPosts($limit = 10)
    {
        $em = $this->doctrine->getManager();

        return $em->createQuery(
            'SELECT n.id, n.title, n.url, n.viewCounts, n.createdAt FROM AppBundle:News n WHERE n.enable = 1 ORDER BY n.viewCounts DESC'
        )
        ->setMaxResults($limit)
        ->getResult();
    }

    /**
     * Get view trends (last 30 days)
     */
    public function getViewTrends()
    {
        $em = $this->doctrine->getManager();

        $results = $em->createQuery(
            'SELECT n.createdAt, SUM(n.viewCounts) as views FROM AppBundle:News n WHERE n.enable = 1 AND n.createdAt >= :thirtyDaysAgo GROUP BY n.createdAt ORDER BY n.createdAt ASC'
        )
        ->setParameter('thirtyDaysAgo', new \DateTime('-30 days'))
        ->getResult();

        // Format data for Chart.js - group by date
        $groupedByDate = [];
        foreach ($results as $result) {
            $date = $result['createdAt']->format('Y-m-d');
            $views = $result['views'] ?: 0;
            $groupedByDate[$date] = ($groupedByDate[$date] ?? 0) + $views;
        }

        // Fill missing days for complete 30-day range
        $labels = [];
        $data = [];
        $period = new \DatePeriod(
            new \DateTime('-30 days'),
            new \DateInterval('P1D'),
            new \DateTime('tomorrow')
        );

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $labels[] = $dateStr;
            $data[] = $groupedByDate[$dateStr] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get posts by category (for pie chart)
     */
    public function getPostsByCategory()
    {
        $em = $this->doctrine->getManager();

        $labels = [];
        $data = [];

        // Get all categories
        $categories = $em->createQuery(
            'SELECT nc.id, nc.name FROM AppBundle:NewsCategory nc ORDER BY nc.name ASC'
        )->getResult();

        foreach ($categories as $category) {
            $labels[] = $category['name'];
            
            // Count posts for this category
            $count = $em->createQuery(
                'SELECT COUNT(n) FROM AppBundle:News n WHERE n.categoryPrimary = :categoryId AND n.enable = 1'
            )
            ->setParameter('categoryId', $category['id'])
            ->getSingleScalarResult();
            
            $data[] = (int)$count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 20)
    {
        $em = $this->doctrine->getManager();

        $activities = [];

        // Recent posts
        $recentPosts = $em->createQuery(
            'SELECT n.id, n.title, n.createdAt FROM AppBundle:News n ORDER BY n.createdAt DESC'
        )
        ->setMaxResults($limit)
        ->getResult();

        foreach ($recentPosts as $post) {
            $activities[] = [
                'type' => 'post',
                'title' => 'New post: ' . $post['title'],
                'date' => $post['createdAt'],
                'icon' => 'fa-file-alt',
                'color' => 'info'
            ];
        }

        // Recent comments
        $recentComments = $em->createQuery(
            'SELECT c.id, c.author, c.createdAt, c.news_id FROM AppBundle:Comment c ORDER BY c.createdAt DESC'
        )
        ->setMaxResults($limit)
        ->getResult();

        // Get news titles for comments
        if (!empty($recentComments)) {
            $newsIds = array_map(function($c) { return $c['news_id']; }, $recentComments);
            
            $newsList = $em->createQuery(
                'SELECT n.id, n.title FROM AppBundle:News n WHERE n.id IN (:newsIds)'
            )
            ->setParameter('newsIds', $newsIds)
            ->getResult();

            // Build news lookup map
            $newsMap = [];
            foreach ($newsList as $news) {
                $newsMap[$news['id']] = $news;
            }

            foreach ($recentComments as $comment) {
                $postTitle = $newsMap[$comment['news_id']]['title'] ?? 'Unknown Post';
                $activities[] = [
                    'type' => 'comment',
                    'title' => $comment['author'] . ' commented on: ' . $postTitle,
                    'date' => $comment['createdAt'],
                    'icon' => 'fa-comments',
                    'color' => 'warning'
                ];
            }
        }

        // Recent contacts
        try {
            $recentContacts = $em->createQuery(
                'SELECT c.id, c.name, c.createdAt FROM AppBundle:Contact c ORDER BY c.createdAt DESC'
            )
            ->setMaxResults($limit)
            ->getResult();

            foreach ($recentContacts as $contact) {
                $activities[] = [
                    'type' => 'contact',
                    'title' => 'New message from: ' . $contact['name'],
                    'date' => $contact['createdAt'],
                    'icon' => 'fa-envelope',
                    'color' => 'danger'
                ];
            }
        } catch (\Exception $e) {
            // Contact entity might not exist or table not found
        }

        // Sort by date desc
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get comment stats
     */
    public function getCommentStats()
    {
        $em = $this->doctrine->getManager();

        $approved = $em->createQuery(
            'SELECT COUNT(c) FROM AppBundle:Comment c WHERE c.approved = 1'
        )->getSingleScalarResult();

        $pending = $em->createQuery(
            'SELECT COUNT(c) FROM AppBundle:Comment c WHERE c.approved = 0'
        )->getSingleScalarResult();

        $total = $approved + $pending;

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'approvalRate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get today's stats
     */
    public function getTodayStats()
    {
        $em = $this->doctrine->getManager();
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        // Posts created today
        $postsToday = $em->createQuery(
            'SELECT COUNT(n) FROM AppBundle:News n WHERE n.createdAt >= :today AND n.createdAt < :tomorrow'
        )
        ->setParameter('today', $today)
        ->setParameter('tomorrow', $tomorrow)
        ->getSingleScalarResult();

        // Comments created today
        $commentsToday = $em->createQuery(
            'SELECT COUNT(c) FROM AppBundle:Comment c WHERE c.createdAt >= :today AND c.createdAt < :tomorrow'
        )
        ->setParameter('today', $today)
        ->setParameter('tomorrow', $tomorrow)
        ->getSingleScalarResult();

        // Views today (approximate based on current viewCounts increment)
        $viewsToday = $em->createQuery(
            'SELECT SUM(n.viewCounts) FROM AppBundle:News n WHERE n.enable = 1'
        )->getSingleScalarResult() ?: 0;

        return [
            'postsToday' => $postsToday,
            'commentsToday' => $commentsToday,
            'viewsToday' => $viewsToday
        ];
    }
}
