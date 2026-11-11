<?php

namespace App\Controller\Admin;

use App\Repository\InternalLinkReportRepository;
use App\Seo\InternalLink\InternalLinkAnalyzer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/internal-links")
 * @Security("has_role('ROLE_ADMIN')")
 */
class InternalLinkController extends Controller
{
    private const PER_PAGE = 50;

    private const PAGE_ISSUES = [
        'orphan' => [
            'label' => 'Trang mồ côi',
            'level' => 'critical',
            'help' => 'Không có link nào trỏ tới (nội dung, menu, danh mục). Google yêu cầu mọi trang quan trọng có ít nhất 1 link từ trang khác.',
        ],
        'unreachable' => [
            'label' => 'Không tới được từ trang chủ',
            'level' => 'critical',
            'help' => 'Có link trỏ tới nhưng không có đường đi nào bắt đầu từ trang chủ (mất tín hiệu homepagePagerankNs).',
        ],
        'money_starved' => [
            'label' => 'Money Page thiếu lực đẩy',
            'level' => 'critical',
            'help' => 'Trang đích chuyển đổi cốt lõi (Money Page) có dưới ' . InternalLinkAnalyzer::MIN_MONEY_PAGE_INLINKS . ' bài vệ tinh trỏ link ngữ cảnh tới.',
        ],
        'local_starved' => [
            'label' => 'Local Page thiếu lực đẩy',
            'level' => 'warning',
            'help' => 'Trang SEO Địa phương (Quận/Huyện/Tỉnh) có dưới ' . InternalLinkAnalyzer::MIN_LOCAL_PAGE_INLINKS . ' bài dự án/vệ tinh trỏ link ngữ cảnh tới.',
        ],
        'project_no_local' => [
            'label' => 'Dự án chưa link về Local Page',
            'level' => 'warning',
            'help' => 'Bài công trình thực tế có địa danh Quận/Huyện/Tỉnh nhưng chưa đặt link trỏ về Local Page tương ứng.',
        ],
        'no_contextual' => [
            'label' => 'Chưa có link trong nội dung',
            'level' => 'warning',
            'help' => 'Chỉ được link từ menu hoặc trang danh mục. Thêm link từ bài liên quan để truyền tín hiệu ngữ cảnh (context2).',
        ],
        'no_money_link' => [
            'label' => 'Chưa truyền lực lên Money / Local / Hub',
            'level' => 'warning',
            'help' => 'Bài Blog hoặc Dự án dài (≥300 từ) nhưng chưa đặt link trỏ về bất kỳ Money Page, Local Page hay Hub danh mục nào.',
        ],
        'dead_end' => [
            'label' => 'Trang cụt (Dead-end)',
            'level' => 'warning',
            'help' => 'Nội dung không có link nội bộ nào đi ra, làm đứt dòng chảy PageRank nội bộ.',
        ],
        'deep' => [
            'label' => 'Nằm quá sâu (> ' . InternalLinkAnalyzer::MAX_CLICK_DEPTH . ' click)',
            'level' => 'warning',
            'help' => 'Cần hơn ' . InternalLinkAnalyzer::MAX_CLICK_DEPTH . ' lần click từ trang chủ mới tới, làm suy giảm mạnh điểm truyền lực.',
        ],
        'single_source' => [
            'label' => 'Chỉ 1 bài link tới',
            'level' => 'info',
            'help' => 'Chỉ có đúng 1 trang link tới trong nội dung và không nằm trong menu.',
        ],
        'too_many_links' => [
            'label' => 'Mật độ link quá dày',
            'level' => 'info',
            'help' => 'Mật độ link trong nội dung cao bất thường, làm loãng trọng số truyền lực trên mỗi link.',
        ],
    ];

    private const LINK_ISSUES = [
        'broken' => [
            'label' => 'Link hỏng (404)',
            'level' => 'critical',
            'help' => 'Đích không tồn tại hoặc bài viết đang tắt hiển thị.',
        ],
        'uncrawlable' => [
            'label' => 'Link Google không đi theo được',
            'level' => 'critical',
            'help' => 'Thẻ <a> không có href thật hoặc dùng javascript:.',
        ],
        'redirect' => [
            'label' => 'Link qua chuyển hướng (301/302)',
            'level' => 'warning',
            'help' => 'Đi qua redirect làm hao hụt tốc độ crawl và tín hiệu truyền lực. Nên sửa trỏ thẳng tới URL đích.',
        ],
        'noncanonical' => [
            'label' => 'Link tới URL không chuẩn (Non-canonical)',
            'level' => 'warning',
            'help' => 'Trang mở được nhưng không phải URL canonical (ví dụ chứa slug danh mục ở đầu hoặc trang phân trang).',
        ],
        'cannibal_anchor' => [
            'label' => 'Anchor ăn thịt từ khóa (Cannibalization)',
            'level' => 'warning',
            'help' => 'Cùng một anchor text từ khóa đang trỏ tới nhiều trang đích khác nhau, làm phân tán tín hiệu xếp hạng.',
        ],
        'nofollow' => [
            'label' => 'Link nội bộ gắn nofollow',
            'level' => 'warning',
            'help' => 'Chặn dòng truyền PageRank nội bộ. Link nội bộ luôn nên để dofollow.',
        ],
        'noindex_target' => [
            'label' => 'Link tới trang noindex',
            'level' => 'warning',
            'help' => 'Trang đích đang chặn index, gây lãng phí lực truyền.',
        ],
        'empty' => [
            'label' => 'Anchor rỗng',
            'level' => 'warning',
            'help' => 'Link không có chữ, hoặc là ảnh thiếu thuộc tính alt.',
        ],
        'generic' => [
            'label' => 'Anchor chung chung',
            'level' => 'warning',
            'help' => 'Kiểu "xem thêm", "tại đây": không truyền được tín hiệu từ khóa (Anchor Context) cho trang đích.',
        ],
        'duplicate_target' => [
            'label' => 'Trùng đích trong cùng bài (First-Link Rule)',
            'level' => 'info',
            'help' => 'Một bài đặt nhiều link trỏ tới cùng 1 trang đích. Google ưu tiên ghi nhận anchor của link đầu tiên.',
        ],
        'long' => [
            'label' => 'Anchor quá dài',
            'level' => 'info',
            'help' => 'Hơn ' . InternalLinkAnalyzer::LONG_ANCHOR_WORDS . ' từ. Anchor nên ngắn gọn, tập trung vào cụm từ khóa.',
        ],
        'adjacent' => [
            'label' => 'Link đứng sát nhau',
            'level' => 'info',
            'help' => 'Hai link liền nhau không có chữ ngăn giữa ("chaining up links").',
        ],
        'self' => [
            'label' => 'Link tự trỏ chính mình',
            'level' => 'info',
            'help' => 'Trang tự link về chính URL của nó trong nội dung.',
        ],
    ];

    /**
     * @Route("/", name="admin_internal_link_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, InternalLinkReportRepository $reports)
    {
        $latest = $reports->findLatest();
        if (!$latest) {
            return $this->render('admin/internal_link/index.html.twig', ['report' => null] + $this->labels());
        }

        $data = $latest->getData();
        $tab = (string) $request->query->get('tab', 'overview');
        $validTabs = ['overview', 'money', 'local', 'pages', 'links', 'opportunities'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'overview';
        }

        $filters = [
            'q' => mb_strtolower(trim((string) $request->query->get('q'))),
            'issue' => (string) $request->query->get('issue', ''),
            'type' => (string) $request->query->get('type', ''),
            'tier' => (string) $request->query->get('tier', ''),
            'sort' => (string) $request->query->get('sort', ''),
            'target_tier' => (string) $request->query->get('target_tier', ''),
        ];

        if ($tab === 'money') {
            $filters['tier'] = 'money';
            if ($filters['sort'] === '') {
                $filters['sort'] = 'inlinks_asc';
            }
            $items = $this->filterPages($data, $filters);
        } elseif ($tab === 'local') {
            $filters['tier'] = 'local';
            if ($filters['sort'] === '') {
                $filters['sort'] = 'inlinks_asc';
            }
            $items = $this->filterPages($data, $filters);
        } elseif ($tab === 'pages') {
            $items = $this->filterPages($data, $filters);
        } elseif ($tab === 'links') {
            $items = $this->filterLinks($data, $filters);
        } elseif ($tab === 'opportunities') {
            $items = $this->filterOpportunities($data, $filters);
        } else {
            $items = [];
        }

        $paginator = $this->get('knp_paginator');

        return $this->render('admin/internal_link/index.html.twig', [
            'report' => $data,
            'generatedAt' => $latest->getGeneratedAt(),
            'tab' => $tab,
            'filters' => $filters,
            'pagination' => $paginator->paginate($items, max(1, $request->query->getInt('page', 1)), self::PER_PAGE),
        ] + $this->labels());
    }

    /**
     * @Route("/page", name="admin_internal_link_page")
     * @Method("GET")
     */
    public function pageAction(Request $request, InternalLinkReportRepository $reports)
    {
        $latest = $reports->findLatest();
        $data = $latest ? $latest->getData() : [];
        $key = (string) $request->query->get('key');

        if (!isset($data['pages'][$key])) {
            throw $this->createNotFoundException('Trang không có trong báo cáo link nội bộ.');
        }

        $inbound = array_values(array_filter(
            $data['links'],
            static fn (array $link) => $link['target'] === $key && $link['source'] !== $key
        ));

        $anchorCounts = [];
        foreach ($inbound as $link) {
            $a = trim((string) $link['anchor']) ?: '(rỗng)';
            $anchorCounts[$a] = ($anchorCounts[$a] ?? 0) + 1;
        }
        arsort($anchorCounts);

        return $this->render('admin/internal_link/page.html.twig', [
            'report' => $data,
            'generatedAt' => $latest->getGeneratedAt(),
            'page' => $data['pages'][$key],
            'inbound' => $inbound,
            'anchorCounts' => $anchorCounts,
            'outbound' => array_values(array_filter($data['links'], static fn (array $link) => $link['source'] === $key)),
            'opportunities' => array_values(array_filter($data['opportunities'], static fn (array $item) => $item['target'] === $key)),
            'outboundOpportunities' => array_values(array_filter($data['opportunities'], static fn (array $item) => $item['source'] === $key)),
        ] + $this->labels());
    }

    /**
     * @Route("/refresh", name="admin_internal_link_refresh")
     * @Method("POST")
     */
    public function refreshAction(Request $request, InternalLinkReportRepository $reports, InternalLinkAnalyzer $analyzer)
    {
        if (!$this->isCsrfTokenValid('internal_link_refresh', $request->request->get('token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $reports->refresh($analyzer);
        $this->addFlash('success', 'Đã phân tích lại đồ thị link nội bộ theo 6 nhóm trang chuẩn SEO.');

        return $this->redirectToRoute('admin_internal_link_index');
    }

    private function filterPages(array $data, array $filters): array
    {
        $pages = array_filter($data['pages'], static function (array $page) use ($filters) {
            if ($page['key'] === 'home') {
                return false;
            }
            if ($filters['issue'] === 'any' ? empty($page['issues']) : ($filters['issue'] !== '' && !in_array($filters['issue'], $page['issues'], true))) {
                return false;
            }
            if ($filters['type'] !== '' && $page['type'] !== $filters['type']) {
                return false;
            }
            if ($filters['tier'] !== '' && ($page['tier'] ?? '') !== $filters['tier']) {
                return false;
            }

            return $filters['q'] === '' || str_contains(mb_strtolower($page['title'] . ' ' . $page['path'] . ' ' . ($page['clusterName'] ?? '') . ' ' . ($page['locationName'] ?? '')), $filters['q']);
        });

        $weight = static fn (array $page) => array_sum(array_map(
            static fn (string $issue) => ['critical' => 100, 'warning' => 10, 'info' => 1][self::PAGE_ISSUES[$issue]['level'] ?? 'info'],
            $page['issues']
        ));

        $sort = $filters['sort'];
        usort($pages, static function (array $a, array $b) use ($sort, $weight) {
            return match ($sort) {
                'juice_desc' => ($b['linkJuice'] ?? 0) <=> ($a['linkJuice'] ?? 0),
                'juice_asc' => ($a['linkJuice'] ?? 0) <=> ($b['linkJuice'] ?? 0),
                'inlinks_asc' => [$a['inContent'], $a['linkJuice'] ?? 0] <=> [$b['inContent'], $b['linkJuice'] ?? 0],
                'inlinks_desc' => [$b['inContent'], $b['linkJuice'] ?? 0] <=> [$a['inContent'], $a['linkJuice'] ?? 0],
                'views_desc' => ($b['viewCounts'] ?? 0) <=> ($a['viewCounts'] ?? 0),
                default => [$weight($b), $a['inContent']] <=> [$weight($a), $b['inContent']],
            };
        });

        return $pages;
    }

    private function filterLinks(array $data, array $filters): array
    {
        $links = [];
        foreach ($data['links'] as $link) {
            $issues = $link['flags'];
            if (isset(self::LINK_ISSUES[$link['status']])) {
                array_unshift($issues, $link['status']);
            }

            if ($filters['issue'] === 'all') {
                if ($link['status'] === 'external') {
                    continue;
                }
            } elseif ($filters['issue'] === '' ? empty($issues) : !in_array($filters['issue'], $issues, true)) {
                continue;
            }

            $source = $data['pages'][$link['source']] ?? null;
            if ($filters['q'] !== '' && !str_contains(mb_strtolower($link['href'] . ' ' . $link['anchor'] . ' ' . ($source['title'] ?? 'menu')), $filters['q'])) {
                continue;
            }

            $links[] = $link + ['issues' => $issues];
        }

        return $links;
    }

    private function filterOpportunities(array $data, array $filters): array
    {
        return array_values(array_filter($data['opportunities'], static function (array $item) use ($data, $filters) {
            if ($filters['target_tier'] === 'money' && ($item['targetTier'] ?? '') !== 'money') {
                return false;
            }
            if ($filters['target_tier'] === 'local' && ($item['targetTier'] ?? '') !== 'local') {
                return false;
            }
            if ($filters['target_tier'] === 'cluster' && empty($item['sameCluster'])) {
                return false;
            }
            if ($filters['q'] === '') {
                return true;
            }
            $sourceTitle = $data['pages'][$item['source']]['title'] ?? '';
            $targetTitle = $data['pages'][$item['target']]['title'] ?? '';

            return str_contains(mb_strtolower($item['keyword'] . ' ' . $sourceTitle . ' ' . $targetTitle . ' ' . ($item['flowLabel'] ?? '')), $filters['q']);
        }));
    }

    private function labels(): array
    {
        return [
            'pageIssues' => self::PAGE_ISSUES,
            'linkIssues' => self::LINK_ISSUES,
            'typeLabels' => [
                'post' => 'Bài viết',
                'page' => 'Trang tĩnh',
                'category' => 'Danh mục',
                'route' => 'Trang chức năng',
                'home' => 'Trang chủ',
            ],
            'tierLabels' => [
                'money' => '★ Money Page (Dịch vụ & Báo giá cốt lõi)',
                'local' => '📍 Local Page (SEO Địa phương Quận/Huyện/Tỉnh)',
                'project' => '🏗 Project Page (Công trình / Dự án thực tế)',
                'blog' => '📝 Blog Page (Kiến thức / Mẫu nhà / Phong thủy)',
                'hub' => '📂 Hub Page (Danh mục trụ cột)',
                'trust' => '🛡 Trust Page (Thương hiệu / E-E-A-T)',
            ],
        ];
    }
}
