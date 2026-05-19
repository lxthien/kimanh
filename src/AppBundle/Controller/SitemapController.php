<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\Tag;

class SitemapController extends Controller
{
    /**
     * Kiểm tra URL có thể index hay không dựa vào field robots.
     * Trả về false nếu robots chứa "noindex" hoặc "nofollow".
     *
     * @param string|null $robots
     * @return bool
     */
    private function isIndexable($robots)
    {
        if (empty($robots)) {
            return true;
        }

        $lower = strtolower($robots);

        return (strpos($lower, 'noindex') === false) && (strpos($lower, 'nofollow') === false);
    }

    /**
     * Tạo sitemap XML tự động cho toàn bộ website.
     *
     * @Route("/sitemap.xml", name="sitemap", defaults={"_format"="xml"})
     *
     * @return Response
     */
    public function indexAction()
    {
        $urls = [];

        // ----------------------------------------------------------------
        // 1. Trang chủ — luôn đưa vào sitemap, priority cao nhất
        // ----------------------------------------------------------------
        $urls[] = [
            'loc'        => $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod'    => (new \DateTime())->format('Y-m-d'),
            'changefreq' => 'daily',
            'priority'   => '1.0',
        ];

        // ----------------------------------------------------------------
        // 2. Trang liên hệ — static, luôn đưa vào sitemap
        // ----------------------------------------------------------------
        $urls[] = [
            'loc'        => $this->generateUrl('contact', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod'    => (new \DateTime())->format('Y-m-d'),
            'changefreq' => 'monthly',
            'priority'   => '0.7',
        ];

        // ----------------------------------------------------------------
        // 3. Trang công cụ tính chi phí xây dựng — static
        // ----------------------------------------------------------------
        $urls[] = [
            'loc'        => $this->generateUrl('caculator_cost_construction', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod'    => (new \DateTime())->format('Y-m-d'),
            'changefreq' => 'monthly',
            'priority'   => '0.8',
        ];

        // ----------------------------------------------------------------
        // 4. Danh mục (NewsCategory) — cấp 1 và cấp 2
        // ----------------------------------------------------------------
        $categories = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->createQueryBuilder('c')
            ->where('c.enable = :enable')
            ->setParameter('enable', true)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($categories as $category) {
            // Lọc theo robots
            if (!$this->isIndexable($category->getRobots())) {
                continue;
            }

            $isRoot = ($category->getParentcat() === 'root');

            if ($isRoot) {
                // Danh mục cấp 1: /{level1}
                $loc = $this->generateUrl(
                    'news_category',
                    ['level1' => $category->getUrl()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } else {
                // Danh mục cấp 2: /{level1}/{level2}
                // Bỏ qua nếu parent không xác định được
                $parent = $category->getParentcat();
                if (!$parent || !($parent instanceof NewsCategory)) {
                    continue;
                }

                $loc = $this->generateUrl(
                    'list_category',
                    [
                        'level1' => $parent->getUrl(),
                        'level2' => $category->getUrl(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => $category->getUpdatedAt()
                    ? $category->getUpdatedAt()->format('Y-m-d')
                    : $category->getCreatedAt()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => $isRoot ? '0.8' : '0.7',
            ];
        }

        // ----------------------------------------------------------------
        // 5. Bài viết (post) và trang tĩnh (page) — News entity
        // ----------------------------------------------------------------
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('n')
            ->where('n.enable = :enable')
            ->setParameter('enable', true)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($posts as $post) {
            // Lọc theo robots
            if (!$this->isIndexable($post->getRobots())) {
                continue;
            }

            $changefreq = $post->isPage() ? 'monthly' : 'weekly';
            $priority   = $post->isPage() ? '0.8' : '0.6';

            $urls[] = [
                'loc'        => $this->generateUrl(
                    'news_show',
                    ['slug' => $post->getUrl()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'lastmod'    => $post->getUpdatedAt()
                    ? $post->getUpdatedAt()->format('Y-m-d')
                    : $post->getCreatedAt()->format('Y-m-d'),
                'changefreq' => $changefreq,
                'priority'   => $priority,
            ];
        }

        // ----------------------------------------------------------------
        // 6. Tags — không có field robots, đưa tất cả vào sitemap
        // ----------------------------------------------------------------
        /* $tags = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($tags as $tag) {
            $urls[] = [
                'loc'        => $this->generateUrl(
                    'tags',
                    ['slug' => $tag->getUrl()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'lastmod'    => (new \DateTime())->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.5',
            ];
        } */

        // ----------------------------------------------------------------
        // Render XML response
        // ----------------------------------------------------------------
        $response = new Response(
            $this->renderView('sitemap/index.xml.twig', ['urls' => $urls]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );

        // Cache 1 giờ phía client / reverse proxy
        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
