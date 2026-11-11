<?php

namespace App\Seo\InternalLink;

use App\Entity\Menu;
use App\Entity\MenuItem;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\Redirect;
use App\Health\InternalUrlNormalizer;
use App\Service\ContentMetrics;
use App\Service\MenuService;
use App\Service\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Phân tích đồ thị Internal Link của site theo:
 *  1. Hướng dẫn "SEO link best practices" của Google Search Central
 *  2. Tài liệu Google Content Warehouse API Leak (2024):
 *     - homepagePagerankNs & khoảng cách click từ trang chủ
 *     - Trọng số vị trí đặt link trong bài (35% đầu bài > giữa bài > cuối bài)
 *     - First-Link Priority (nhiều link cùng trỏ 1 đích trong 1 bài)
 *     - Anchor Text Context & Anchor Cannibalization (1 anchor trỏ nhiều đích khác nhau)
 *     - Tín hiệu Traffic (viewCounts) & Freshness (updatedAt) của trang nguồn truyền lực
 *  3. Mô hình 6 nhóm trang chuẩn SEO Xây dựng:
 *     - money   : Money Page (Trang dịch vụ & báo giá cốt lõi toàn công ty)
 *     - local   : Local Page (Trang SEO địa phương theo Quận/Huyện/Tỉnh)
 *     - project : Project Page (Dự án / Công trình thực tế - E-E-A-T)
 *     - blog    : Blog Page (Bài kiến thức, cẩm nang, mẫu nhà, phong thủy, pháp lý)
 *     - hub     : Hub Page (Danh mục trụ cột)
 *     - trust   : Trust / Brand Page (Giới thiệu, Hồ sơ năng lực, Bảo hành, CEO, Liên hệ)
 */
class InternalLinkAnalyzer
{
    public const VERSION = 3;

    public const MAX_CLICK_DEPTH = 3;
    public const LONG_ANCHOR_WORDS = 12;
    public const MAX_CONTENT_LINKS = 100;
    public const MIN_WORDS_PER_LINK = 40;
    public const MIN_MONEY_PAGE_INLINKS = 3;
    public const MIN_LOCAL_PAGE_INLINKS = 2;
    private const MAX_OPPORTUNITIES = 400;
    private const MAX_OPPORTUNITIES_PER_TARGET = 15;

    private const GENERIC_ANCHORS = [
        'tại đây', 'ở đây', 'đây', 'vào đây', 'xem tại đây', 'bấm vào đây', 'nhấn vào đây', 'click vào đây',
        'xem thêm', 'đọc thêm', 'tìm hiểu thêm', 'xem chi tiết', 'chi tiết', 'xem ngay', 'xem tiếp', 'đọc tiếp',
        'link', 'liên kết', 'trang này', 'bài viết này', 'bài này', 'website', 'nguồn', 'tham khảo',
        'click here', 'click', 'here', 'read more', 'learn more', 'more', 'this page', 'this',
    ];

    /**
     * 6. Trust / Brand Pages (E-E-A-T, Pháp nhân, Chính sách)
     */
    private const TRUST_PAGE_SLUGS = [
        'gioi-thieu', 'lien-he', 'ho-so-nang-luc', 'chinh-sach-bao-hanh',
        'chinh-sach-bao-mat-thong-tin', 'dieu-khoan-su-dung', 'nguyenduytuan',
        'tuyen-dung', 'bang-khao-sat',
    ];

    /**
     * 1. Core Money Pages (Trang đích chuyển đổi & Báo giá cốt lõi)
     */
    private const CORE_MONEY_SLUGS = [
        'bao-gia-xay-nha-tron-goi',
        'bao-gia-xay-nha-tron-goi-tphcm',
        'bao-gia-xay-nha-phan-tho',
        'bao-gia-thiet-ke-kien-truc',
        'sua-nha-tron-goi',
        'dich-vu-xin-giay-phep-xay-dung',
        'dich-vu-hoan-cong-nha-o',
        'xay-nha-cap-4-tron-goi',
        'xay-biet-thu-tron-goi',
        'chi-phi-xay-dung',
        'cong-ty-xay-nha-tron-goi-uy-tin-tphcm',
        'cong-ty-xay-nha-phan-tho-uy-tin',
        'chi-phi-xay-nha-cap-4',
        'chi-phi-xay-nha-2-tang',
        'chi-phi-xay-nha-3-tang',
        'chi-phi-ep-coc-xay-nha',
        'don-gia-nhan-cong-xay-dung-phan-tho',
    ];

    /**
     * Danh sách địa danh Quận/Huyện/Tỉnh để nhận diện Local Page & ghép cặp với Project Page cùng khu vực
     */
    private const LOCATION_MAP = [
        'quan-12' => 'Quận 12',
        'q12' => 'Quận 12',
        'quan-11' => 'Quận 11',
        'q11' => 'Quận 11',
        'quan-10' => 'Quận 10',
        'quan-9' => 'TP Thủ Đức',
        'quan-8' => 'Quận 8',
        'quan-7' => 'Quận 7',
        'quan-6' => 'Quận 6',
        'quan-5' => 'Quận 5',
        'quan-4' => 'Quận 4',
        'quan-3' => 'Quận 3',
        'quan-2' => 'TP Thủ Đức',
        'quan-1' => 'Quận 1',
        'thu-duc' => 'TP Thủ Đức',
        'go-vap' => 'Quận Gò Vấp',
        'binh-tan' => 'Quận Bình Tân',
        'tan-binh' => 'Quận Tân Bình',
        'tan-phu' => 'Quận Tân Phú',
        'binh-thanh' => 'Quận Bình Thạnh',
        'phu-nhuan' => 'Quận Phú Nhuận',
        'hoc-mon' => 'Huyện Hóc Môn',
        'binh-chanh' => 'Huyện Bình Chánh',
        'cu-chi' => 'Huyện Củ Chi',
        'nha-be' => 'Huyện Nhà Bè',
        'can-gio' => 'Huyện Cần Giờ',
        'long-an' => 'Long An',
        'binh-duong' => 'Bình Dương',
        'dong-nai' => 'Đồng Nai',
        'tay-ninh' => 'Tây Ninh',
        'ba-ria' => 'Bà Rịa - Vũng Tàu',
        'vung-tau' => 'Bà Rịa - Vũng Tàu',
    ];

    /**
     * Danh mục thuộc nhóm Dự án / Công trình thực tế (Project Page)
     */
    private const PROJECT_CATEGORY_SLUGS = [
        'cong-trinh', 'xay-dung-nha', 'sua-chua-nha',
    ];

    private array $pages = [];
    private array $links = [];
    /** @var array<string, array<string, int>> đồ thị có trọng số (số click) dùng tính độ sâu */
    private array $edges = [];
    /** @var array<string, array<string, float>> đồ thị truyền lực PageRank nội bộ */
    private array $weightedGraph = [];
    private array $newsBySlug = [];
    private array $unpublishedSlugs = [];
    private array $categoriesBySlug = [];
    private array $redirects = [];
    private array $ownHosts = [];
    private string $webRoot;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
        private readonly MenuService $menuService,
        private readonly ContentMetrics $contentMetrics,
        private readonly SettingsManager $settings,
        KernelInterface $kernel
    ) {
        $this->webRoot = $kernel->getProjectDir() . '/public';
    }

    public function analyze(): array
    {
        $this->pages = $this->links = $this->edges = $this->weightedGraph = [];
        $this->newsBySlug = $this->unpublishedSlugs = $this->categoriesBySlug = [];
        $this->ownHosts = $this->resolveOwnHosts();
        $this->redirects = $this->em->createQueryBuilder()
            ->select('r.sourceUrl', 'r.destinationUrl', 'r.matchType')
            ->from(Redirect::class, 'r')
            ->where('r.isActive = true')
            ->orderBy('r.orderNum', 'ASC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $contents = $this->loadPages();
        $this->addMenuLinks();
        $this->classifyPageTiers();
        $this->addStructuralLinks();

        $linkableText = [];
        foreach ($contents as $key => $html) {
            $linkableText[$key] = $this->extractContentLinks($key, $html);
        }

        $this->detectAnchorCannibalization();
        $this->computeInternalPageRank();
        $this->computeMetrics();
        $opportunities = $this->findOpportunities($linkableText);
        $summary = $this->summarize($opportunities);

        return [
            'version' => self::VERSION,
            'summary' => $summary,
            'score' => $this->score($summary),
            'pages' => $this->pages,
            'links' => $this->links,
            'opportunities' => $opportunities,
        ];
    }

    /**
     * @return array<string, string> key trang => HTML nội dung cần quét link
     */
    private function loadPages(): array
    {
        $contents = [];
        $this->addPage('home', 'home', 'Trang chủ', '/', true, [
            'tier' => 'hub',
            'isMoneyPage' => false,
            'isLocalPage' => false,
        ]);

        $categories = $this->em->getRepository(NewsCategory::class)->findBy(['enable' => true]);
        foreach ($categories as $category) {
            $parent = is_object($category->getParentcat()) ? $category->getParentcat() : null;
            $path = '/' . ($parent ? $parent->getUrl() . '/' : '') . $category->getUrl() . '/';
            $key = 'cat:' . $category->getId();
            $indexable = stripos((string) $category->getRobots(), 'noindex') === false;
            $catHtml = (string) $category->getContent();
            $catTitle = (string) ($category->getTitleLandingPage() ?: $category->getName());

            $this->addPage($key, 'category', (string) $category->getName(), $path, $indexable, [
                'edit' => ['route' => 'admin_newscategory_edit', 'id' => $category->getId()],
                'keywords' => self::extractEntityKeywords($category->getPageKeyword(), $catTitle, (string) $category->getUrl()),
                'parent' => $parent ? 'cat:' . $parent->getId() : null,
                'clusterKey' => $parent ? 'cat:' . $parent->getId() : $key,
                'clusterName' => (string) ($parent ? $parent->getName() : $category->getName()),
                'categorySlugs' => [(string) $category->getUrl()],
                'tier' => 'hub',
                'wordCount' => $this->contentMetrics->countWords($catHtml),
            ]);
            $this->categoriesBySlug[$category->getUrl()] = [
                'key' => $key,
                'parentSlug' => $parent ? $parent->getUrl() : null,
            ];
            if (trim($catHtml) !== '') {
                $contents[$key] = $catHtml;
            }
        }

        $rows = $this->em->createQueryBuilder()
            ->select(
                'n.id',
                'n.title',
                'n.url',
                'n.postType',
                'n.enable',
                'n.contents',
                'n.pageKeyword',
                'n.robots',
                'n.viewCounts',
                'n.categoryPrimary',
                'n.updatedAt',
                'n.pageBuilderEnabled',
                'n.pageBuilderData',
                'IDENTITY(n.parent) AS parentId'
            )
            ->from(News::class, 'n')
            ->where('n.postType IN (:postTypes)')
            ->setParameter('postTypes', ['post', 'page'])
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $catPairs = $this->em->createQueryBuilder()
            ->select('n.id AS newsId', 'c.id AS categoryId', 'c.name AS categoryName', 'c.url AS categorySlug')
            ->from(News::class, 'n')
            ->innerJoin('n.category', 'c')
            ->where('n.enable = true')
            ->getQuery()
            ->getArrayResult();

        $newsCategories = [];
        foreach ($catPairs as $pair) {
            $newsCategories[$pair['newsId']][] = [
                'id' => (int) $pair['categoryId'],
                'key' => 'cat:' . $pair['categoryId'],
                'name' => (string) $pair['categoryName'],
                'slug' => (string) $pair['categorySlug'],
            ];
        }

        foreach ($rows as $row) {
            if (empty($row['enable'])) {
                $this->unpublishedSlugs[$row['url']] = true;
                continue;
            }

            $isPage = $row['postType'] === 'page';
            $key = 'news:' . $row['id'];
            $html = (string) $row['contents'];
            if (!empty($row['pageBuilderEnabled']) && !empty($row['pageBuilderData'])) {
                $html .= self::collectHtml(json_decode((string) $row['pageBuilderData'], true));
            }

            $indexable = stripos((string) $row['robots'], 'noindex') === false;
            $cats = $newsCategories[$row['id']] ?? [];
            $primaryCatKey = null;
            $primaryCatName = null;
            if (!empty($row['categoryPrimary']) && isset($this->pages['cat:' . $row['categoryPrimary']])) {
                $primaryCatKey = 'cat:' . $row['categoryPrimary'];
                $primaryCatName = $this->pages[$primaryCatKey]['title'];
            } elseif (!empty($cats)) {
                $primaryCatKey = $cats[0]['key'];
                $primaryCatName = $cats[0]['name'];
            }

            $updatedAt = $row['updatedAt'] instanceof \DateTimeInterface ? $row['updatedAt']->format('Y-m-d') : null;

            $this->addPage($key, $isPage ? 'page' : 'post', (string) $row['title'], '/' . $row['url'] . '/', $indexable, [
                'edit' => ['route' => $isPage ? 'admin_page_edit' : 'admin_news_edit', 'id' => $row['id']],
                'keywords' => self::extractEntityKeywords($row['pageKeyword'], (string) $row['title'], (string) $row['url']),
                'parent' => $row['parentId'] ? 'news:' . $row['parentId'] : $primaryCatKey,
                'clusterKey' => $primaryCatKey ?: ($row['parentId'] ? 'news:' . $row['parentId'] : null),
                'clusterName' => $primaryCatName ?: ($isPage ? 'Trang dịch vụ' : 'Chưa phân cụm'),
                'categories' => array_column($cats, 'key'),
                'categorySlugs' => array_column($cats, 'slug'),
                'wordCount' => $this->contentMetrics->countWords($html),
                'viewCounts' => (int) ($row['viewCounts'] ?? 0),
                'updatedAt' => $updatedAt,
            ]);
            $this->newsBySlug[$row['url']] = $key;
            $contents[$key] = $html;
        }

        return $contents;
    }

    private function addPage(string $key, string $type, string $title, string $path, bool $indexable, array $extra = []): void
    {
        $this->pages[$key] = $extra + [
            'key' => $key,
            'type' => $type,
            'title' => $title,
            'path' => $path,
            'indexable' => $indexable,
            'edit' => null,
            'keywords' => [],
            'parent' => null,
            'clusterKey' => null,
            'clusterName' => '',
            'categories' => [],
            'categorySlugs' => [],
            'tier' => 'blog', // 'money' | 'local' | 'project' | 'blog' | 'hub' | 'trust'
            'isMoneyPage' => false,
            'isLocalPage' => false,
            'locationName' => null,
            'wordCount' => 0,
            'viewCounts' => 0,
            'updatedAt' => null,
            'inContent' => 0,
            'inContentLinks' => 0,
            'inEarlyLinks' => 0,
            'inMenu' => false,
            'inStructure' => 0,
            'outInternal' => 0,
            'outMoneyLinks' => 0,
            'outLocalLinks' => 0,
            'outHubLinks' => 0,
            'outExternal' => 0,
            'uniqueAnchors' => 0,
            'linkJuice' => 0.0,
            'depth' => null,
            'issues' => [],
        ];
    }

    /**
     * Phân loại chính xác 6 nhóm trang:
     *  1. money   : Money Page (Trang đích chuyển đổi & Báo giá cốt lõi)
     *  2. local   : Local Page (Trang SEO địa phương Quận/Huyện/Tỉnh)
     *  3. project : Project Page (Dự án / Công trình thực tế)
     *  4. blog    : Blog Page (Bài kiến thức, cẩm nang, mẫu nhà, phong thủy, pháp lý)
     *  5. hub     : Hub Page (Danh mục trụ cột)
     *  6. trust   : Trust / Brand Page (Giới thiệu, Hồ sơ năng lực, Bảo hành, CEO, Liên hệ)
     */
    private function classifyPageTiers(): void
    {
        foreach ($this->pages as $key => &$page) {
            if ($key === 'home') {
                $page['tier'] = 'hub';
                continue;
            }
            if ($page['type'] === 'route') {
                $slug = trim($page['path'], '/');
                $page['tier'] = in_array($slug, self::TRUST_PAGE_SLUGS, true) ? 'trust' : 'blog';
                continue;
            }
            if ($page['type'] === 'category') {
                $page['tier'] = 'hub';
                continue;
            }

            $slug = trim($page['path'], '/');
            $locationName = self::detectLocation($slug);
            $page['locationName'] = $locationName;

            // 1. Trust / Brand Page
            if (in_array($slug, self::TRUST_PAGE_SLUGS, true)) {
                $page['tier'] = 'trust';
                $page['clusterName'] = 'Thương hiệu & Uy tín (E-E-A-T)';
                continue;
            }

            // 2. Project / Case Study Page (Thuộc danh mục Công trình hoặc bắt đầu bằng du-an-)
            $inProjectCategory = !empty(array_intersect($page['categorySlugs'], self::PROJECT_CATEGORY_SLUGS));
            if ($inProjectCategory || str_starts_with($slug, 'du-an-')) {
                $page['tier'] = 'project';
                if ($locationName) {
                    $page['clusterName'] = 'Công trình · ' . $locationName;
                }
                continue;
            }

            // 3. Local Page (Trang SEO Địa phương Quận/Huyện/Tỉnh hoặc trang Khu vực thi công)
            if ($slug === 'khu-vuc-thi-cong' || ($locationName !== null && ($page['type'] === 'page' || str_starts_with($slug, 'xay-nha-') || str_starts_with($slug, 'cong-ty-xay-dung-') || str_starts_with($slug, 'sua-nha-')))) {
                $page['tier'] = 'local';
                $page['isLocalPage'] = true;
                $page['clusterName'] = $locationName ? ('Local SEO · ' . $locationName) : 'Local Hub (Khu vực)';
                continue;
            }

            // 4. Money Page (Trang dịch vụ & Báo giá cốt lõi toàn công ty)
            $isCoreMoney = in_array($slug, self::CORE_MONEY_SLUGS, true)
                || str_starts_with($slug, 'bao-gia-')
                || str_starts_with($slug, 'don-gia-')
                || str_starts_with($slug, 'chi-phi-xay-nha-')
                || str_starts_with($slug, 'dich-vu-');

            if ($isCoreMoney) {
                $page['tier'] = 'money';
                $page['isMoneyPage'] = true;
                continue;
            }

            // 5. Blog Page (Kiến thức xây nhà, sửa nhà, pháp lý, phong thủy, mẫu nhà, quy trình, hợp đồng...)
            $page['tier'] = 'blog';
        }
        unset($page);
    }

    private static function detectLocation(string $slug): ?string
    {
        foreach (self::LOCATION_MAP as $token => $name) {
            if (preg_match('/(^|-)' . preg_quote($token, '/') . '(-|$)/', $slug)) {
                return $name;
            }
        }

        return null;
    }

    private function addStructuralLinks(): void
    {
        $perPage = max(1, (int) $this->settings->get('numPostsPerPage', 12));

        foreach ($this->pages as $key => $page) {
            if ($page['parent'] && isset($this->pages[$page['parent']])) {
                $this->addStructure($key, $page['parent']);
                if ($page['type'] === 'category' || $page['type'] === 'page') {
                    $this->addStructure($page['parent'], $key);
                }
            }
        }

        $pairs = $this->em->createQueryBuilder()
            ->select('n.id AS newsId', 'c.id AS categoryId')
            ->from(News::class, 'n')
            ->innerJoin('n.category', 'c')
            ->where('n.enable = true')
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $position = [];
        foreach ($pairs as $pair) {
            $news = 'news:' . $pair['newsId'];
            $category = 'cat:' . $pair['categoryId'];
            if (!isset($this->pages[$news], $this->pages[$category])) {
                continue;
            }

            $position[$category] = ($position[$category] ?? -1) + 1;
            $clicks = 1 + intdiv($position[$category], $perPage);
            $this->addStructure($category, $news, $clicks);
            $this->addStructure($news, $category, 1);
        }
    }

    private function addStructure(string $from, string $to, int $clicks = 1): void
    {
        if (isset($this->edges[$from][$to])) {
            $this->edges[$from][$to] = min($this->edges[$from][$to], $clicks);

            return;
        }

        $this->edges[$from][$to] = $clicks;
        $this->pages[$to]['inStructure']++;
        $weight = $clicks === 1 ? 0.35 : (0.2 / $clicks);
        $this->weightedGraph[$from][$to] = ($this->weightedGraph[$from][$to] ?? 0.0) + $weight;
    }

    private function addMenuLinks(): void
    {
        $items = $this->em->createQueryBuilder()
            ->select('i', 'm')
            ->from(MenuItem::class, 'i')
            ->innerJoin('i.menu', 'm')
            ->where('i.enable = true')
            ->andWhere('m.enable = true')
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($items as $item) {
            $href = (string) $this->menuService->getItemUrl($item);
            if ($href === '' || $href === '#') {
                continue;
            }

            $link = $this->classify($href) + [
                'source' => 'menu',
                'kind' => 'menu',
                'href' => $href,
                'anchor' => (string) $item->getTitle(),
                'context' => 'Menu điều hướng toàn trang',
                'positionZone' => 'nav',
                'flags' => [],
                'menuId' => $item->getMenu() instanceof Menu ? $item->getMenu()->getId() : null,
            ];
            $this->links[] = $link;

            if ($link['target'] !== null && isset($this->pages[$link['target']])) {
                $this->pages[$link['target']]['inMenu'] = true;
                $this->edges['home'][$link['target']] = 1;
                $this->weightedGraph['home'][$link['target']] = ($this->weightedGraph['home'][$link['target']] ?? 0.0) + 1.5;
            }
        }
    }

    /**
     * @return string phần chữ KHÔNG nằm trong link/heading (để tìm cơ hội đặt link mới)
     */
    private function extractContentLinks(string $sourceKey, string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>');
        libxml_clear_errors();

        $fullPlainText = self::normalizeText($dom->textContent ?? '');
        $totalLen = max(1, mb_strlen($fullPlainText));

        $sources = [];
        $seenTargetsInDoc = [];
        $anchors = iterator_to_array($dom->getElementsByTagName('a'));

        foreach ($anchors as $idx => $node) {
            $href = trim($node->getAttribute('href'));
            $anchor = self::normalizeText($node->textContent);
            $flags = [];

            if ($anchor === '') {
                foreach ($node->getElementsByTagName('img') as $img) {
                    $anchor = self::normalizeText($img->getAttribute('alt'));
                    break;
                }
            }

            if ($href === '') {
                if (!$node->hasAttribute('onclick') && !$node->hasAttribute('href')) {
                    continue;
                }
                $link = ['status' => 'uncrawlable', 'target' => null, 'path' => null, 'note' => 'Thẻ <a> không có href trỏ tới URL thật.'];
            } elseif (preg_match('#^\s*javascript:#i', $href)) {
                $link = ['status' => 'uncrawlable', 'target' => null, 'path' => null, 'note' => 'href dùng javascript:, Google không đi theo được.'];
            } elseif ($href[0] === '#' || preg_match('#^(mailto|tel|sms|zalo|viber|skype):#i', $href)) {
                continue;
            } else {
                $link = $this->classify($href);
            }

            if ($link['status'] === 'file') {
                continue;
            }

            $contextText = '';
            if ($node->parentNode) {
                $contextText = mb_substr(self::normalizeText($node->parentNode->textContent ?? ''), 0, 180);
            }

            $posOffset = $anchor !== '' ? mb_strpos($fullPlainText, $anchor) : false;
            $posRatio = $posOffset !== false ? ($posOffset / $totalLen) : (($idx + 1) / max(1, count($anchors)));
            $positionZone = $posRatio <= 0.35 ? 'early' : ($posRatio <= 0.75 ? 'middle' : 'late');

            if ($link['status'] === 'external') {
                $this->pages[$sourceKey]['outExternal']++;
            } else {
                $this->pages[$sourceKey]['outInternal']++;
                $rel = preg_split('/\s+/', strtolower($node->getAttribute('rel')), -1, PREG_SPLIT_NO_EMPTY);
                if (in_array('nofollow', $rel, true)) {
                    $flags[] = 'nofollow';
                }

                if ($anchor === '') {
                    $flags[] = 'empty';
                } elseif (in_array(mb_strtolower(trim($anchor, " \t.,:;!?»›→>-–—")), self::GENERIC_ANCHORS, true)) {
                    $flags[] = 'generic';
                } elseif (count(preg_split('/\s+/u', $anchor)) > self::LONG_ANCHOR_WORDS) {
                    $flags[] = 'long';
                }

                if (self::isChained($node)) {
                    $flags[] = 'adjacent';
                }

                if ($link['target'] !== null && isset($this->pages[$link['target']])) {
                    $targetKey = $link['target'];
                    if ($targetKey === $sourceKey) {
                        $flags[] = 'self';
                    } else {
                        if (!$this->pages[$targetKey]['indexable']) {
                            $flags[] = 'noindex_target';
                        }

                        if (isset($seenTargetsInDoc[$targetKey])) {
                            $flags[] = 'duplicate_target';
                        }
                        $seenTargetsInDoc[$targetKey] = ($seenTargetsInDoc[$targetKey] ?? 0) + 1;

                        $this->pages[$targetKey]['inContentLinks']++;
                        if ($positionZone === 'early') {
                            $this->pages[$targetKey]['inEarlyLinks']++;
                        }

                        if ($this->pages[$targetKey]['isMoneyPage']) {
                            $this->pages[$sourceKey]['outMoneyLinks']++;
                        }
                        if ($this->pages[$targetKey]['isLocalPage']) {
                            $this->pages[$sourceKey]['outLocalLinks']++;
                        }
                        if ($this->pages[$targetKey]['tier'] === 'hub') {
                            $this->pages[$sourceKey]['outHubLinks']++;
                        }

                        $sources[$targetKey] = true;
                        if (!in_array('nofollow', $flags, true)) {
                            $this->edges[$sourceKey][$targetKey] = 1;
                            if ($seenTargetsInDoc[$targetKey] === 1) {
                                $zoneWeight = $positionZone === 'early' ? 1.5 : ($positionZone === 'middle' ? 1.2 : 1.0);
                                if (in_array('generic', $flags, true) || in_array('empty', $flags, true)) {
                                    $zoneWeight *= 0.5;
                                }
                                $this->weightedGraph[$sourceKey][$targetKey] = ($this->weightedGraph[$sourceKey][$targetKey] ?? 0.0) + $zoneWeight;
                            }
                        }
                    }
                }
            }

            $this->links[] = $link + [
                'source' => $sourceKey,
                'kind' => 'content',
                'href' => $href,
                'anchor' => mb_substr($anchor, 0, 300),
                'context' => $contextText,
                'positionZone' => $positionZone,
                'flags' => $flags,
            ];
        }

        foreach (array_keys($sources) as $target) {
            $this->pages[$target]['inContent']++;
        }

        foreach (['a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'script', 'style'] as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $texts = [];
        foreach ((new \DOMXPath($dom))->query('//text()') as $text) {
            $texts[] = $text->nodeValue;
        }

        return self::normalizeText(implode(' ', $texts));
    }

    private function detectAnchorCannibalization(): void
    {
        $anchorTargets = [];
        $targetAnchors = [];

        foreach ($this->links as $idx => $link) {
            if ($link['kind'] !== 'content' || $link['target'] === null || $link['target'] === $link['source']) {
                continue;
            }
            $normAnchor = mb_strtolower(trim($link['anchor'], " \t.,:;!?»›→>-–—\"'()"));
            if ($normAnchor === '' || in_array($normAnchor, self::GENERIC_ANCHORS, true)) {
                continue;
            }

            $targetAnchors[$link['target']][$normAnchor] = true;

            $wordCount = count(preg_split('/\s+/u', $normAnchor));
            if ($wordCount >= 2 && $wordCount <= 7 && !str_starts_with($normAnchor, 'http')) {
                $anchorTargets[$normAnchor][$link['target']][] = $idx;
            }
        }

        foreach ($targetAnchors as $targetKey => $anchors) {
            if (isset($this->pages[$targetKey])) {
                $this->pages[$targetKey]['uniqueAnchors'] = count($anchors);
            }
        }

        foreach ($anchorTargets as $normAnchor => $targetsMap) {
            if (count($targetsMap) < 2) {
                continue;
            }
            if (str_contains($normAnchor, 'kim anh') && count($targetsMap) > 3) {
                continue;
            }
            foreach ($targetsMap as $targetKey => $linkIndices) {
                foreach ($linkIndices as $idx) {
                    if (!in_array('cannibal_anchor', $this->links[$idx]['flags'], true)) {
                        $this->links[$idx]['flags'][] = 'cannibal_anchor';
                        $otherCount = count($targetsMap) - 1;
                        $extraNote = sprintf('Anchor "%s" đang trỏ tới %d trang đích khác nhau (phân tán tín hiệu từ khóa).', $normAnchor, $otherCount + 1);
                        $this->links[$idx]['note'] = trim(($this->links[$idx]['note'] ? $this->links[$idx]['note'] . ' ' : '') . $extraNote);
                    }
                }
            }
        }
    }

    private function computeInternalPageRank(): void
    {
        $keys = array_keys($this->pages);
        $n = count($keys);
        if ($n === 0) {
            return;
        }

        $damping = 0.85;
        $ranks = [];
        $seedTotal = 0.0;
        $seeds = [];

        foreach ($this->pages as $k => $p) {
            $seed = 1.0;
            if ($k === 'home') {
                $seed = 15.0;
            } elseif ($p['inMenu']) {
                $seed = 4.0;
            } elseif ($p['viewCounts'] > 0) {
                $seed += min(3.0, log10(1 + $p['viewCounts']));
            }
            $seeds[$k] = $seed;
            $seedTotal += $seed;
        }

        foreach ($keys as $k) {
            $ranks[$k] = $seeds[$k] / $seedTotal;
        }

        $outSum = [];
        foreach ($keys as $k) {
            $outSum[$k] = isset($this->weightedGraph[$k]) ? array_sum($this->weightedGraph[$k]) : 0.0;
        }

        for ($iter = 0; $iter < 20; $iter++) {
            $next = [];
            $danglingMass = 0.0;
            foreach ($keys as $k) {
                $next[$k] = (1.0 - $damping) * ($seeds[$k] / $seedTotal);
                if ($outSum[$k] <= 0.0) {
                    $danglingMass += $ranks[$k];
                }
            }

            foreach ($keys as $from) {
                if ($outSum[$from] <= 0.0) {
                    continue;
                }
                foreach ($this->weightedGraph[$from] as $to => $w) {
                    if (isset($next[$to])) {
                        $next[$to] += $damping * $ranks[$from] * ($w / $outSum[$from]);
                    }
                }
            }

            if ($danglingMass > 0.0) {
                foreach ($keys as $k) {
                    $next[$k] += $damping * $danglingMass * ($seeds[$k] / $seedTotal);
                }
            }

            $ranks = $next;
        }

        $maxRank = 0.00001;
        foreach ($ranks as $k => $r) {
            if ($k !== 'home' && $r > $maxRank) {
                $maxRank = $r;
            }
        }

        foreach ($this->pages as $k => &$page) {
            if ($k === 'home') {
                $page['linkJuice'] = 100.0;
                continue;
            }
            $ratio = $ranks[$k] / $maxRank;
            $page['linkJuice'] = round(min(100.0, max(1.0, pow($ratio, 0.45) * 100)), 1);
        }
        unset($page);
    }

    /**
     * @return array{status: string, target: ?string, path: ?string, note: string}
     */
    private function classify(string $href): array
    {
        $path = null;
        foreach ($this->ownHosts as $host) {
            $path = InternalUrlNormalizer::normalize($href, $host);
            if ($path !== null) {
                break;
            }
        }

        if ($path === null) {
            return ['status' => 'external', 'target' => null, 'path' => null, 'note' => ''];
        }

        if (is_file($this->webRoot . $path)) {
            return ['status' => 'file', 'target' => null, 'path' => $path, 'note' => ''];
        }

        $redirected = $this->followRedirects($path);
        if ($redirected !== null) {
            $destination = $this->resolvePath($redirected);

            return [
                'status' => $destination['status'] === 'broken' ? 'broken' : 'redirect',
                'target' => $destination['target'],
                'path' => $path,
                'note' => $destination['status'] === 'broken'
                    ? sprintf('Chuyển hướng tới %s nhưng đích không tồn tại.', $redirected)
                    : sprintf('Đi qua chuyển hướng tới %s. Nên sửa link trỏ thẳng tới đích.', $redirected),
            ];
        }

        return $this->resolvePath($path) + ['path' => $path];
    }

    /**
     * @return array{status: string, target: ?string, note: string}
     */
    private function resolvePath(string $path): array
    {
        if ($path === '/') {
            return ['status' => 'ok', 'target' => 'home', 'note' => ''];
        }

        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        $last = end($segments);
        $missingSlash = !str_ends_with($path, '/');

        $result = null;
        if (count($segments) === 1) {
            $result = $this->resolveSlug($last, null);
        } elseif (count($segments) <= 3 && isset($this->categoriesBySlug[$segments[0]])) {
            if (ctype_digit((string) $last)) {
                $category = $this->categoriesBySlug[$segments[count($segments) - 2]] ?? null;
                if ($category) {
                    return ['status' => 'noncanonical', 'target' => $category['key'], 'note' => 'Link tới trang phân trang của danh mục, nên trỏ về trang đầu.'];
                }
            }
            $result = $this->resolveSlug((string) $last, $segments[count($segments) - 2]);
        }

        if ($result === null) {
            $result = $this->resolveStaticRoute($path, $missingSlash);
        }

        if ($result['status'] === 'ok' && $missingSlash && $result['target'] !== 'route:' . $path) {
            return ['status' => 'redirect', 'target' => $result['target'], 'note' => 'Thiếu dấu "/" cuối URL nên bị chuyển hướng 301 thêm một lần.'];
        }

        return $result;
    }

    private function resolveSlug(string $slug, ?string $parentSlug): array
    {
        if (isset($this->newsBySlug[$slug])) {
            $target = $this->newsBySlug[$slug];
            if ($parentSlug !== null) {
                return ['status' => 'noncanonical', 'target' => $target, 'note' => sprintf('Trang vẫn mở được nhưng URL chuẩn (canonical) là %s.', $this->pages[$target]['path'])];
            }

            return ['status' => 'ok', 'target' => $target, 'note' => ''];
        }

        if (isset($this->categoriesBySlug[$slug])) {
            $category = $this->categoriesBySlug[$slug];
            if ($category['parentSlug'] === $parentSlug) {
                return ['status' => 'ok', 'target' => $category['key'], 'note' => ''];
            }
            if ($parentSlug === null) {
                return ['status' => 'redirect', 'target' => $category['key'], 'note' => sprintf('Danh mục con: bị chuyển hướng 301 tới %s.', $this->pages[$category['key']]['path'])];
            }
        }

        if (isset($this->unpublishedSlugs[$slug])) {
            return ['status' => 'broken', 'target' => null, 'note' => 'Bài viết/trang đích đang tắt hiển thị hoặc chưa xuất bản (404).'];
        }

        if ($parentSlug !== null) {
            return ['status' => 'broken', 'target' => null, 'note' => 'Không tìm thấy bài viết hoặc danh mục khớp với URL này (404).'];
        }

        return $this->resolveStaticRoute('/' . $slug . '/', false, 'Không tìm thấy bài viết, trang hoặc danh mục có đường dẫn này (404).');
    }

    private function resolveStaticRoute(string $path, bool $missingSlash, string $brokenNote = 'URL không khớp với trang nào của site (404).'): array
    {
        foreach ($missingSlash ? [$path, $path . '/'] : [$path] as $candidate) {
            try {
                $route = $this->router->match($candidate);
            } catch (\Throwable $e) {
                continue;
            }

            $name = (string) ($route['_route'] ?? '');
            if ($name === '' || str_starts_with($name, 'dynamic_') || in_array($name, ['news_category', 'list_category', 'news_show'], true)) {
                continue;
            }

            $key = 'route:' . $candidate;
            if (!isset($this->pages[$key])) {
                $this->addPage($key, 'route', $candidate, $candidate, $name !== 'tags' && $name !== 'news_search');
            }

            if ($candidate !== $path) {
                return ['status' => 'redirect', 'target' => $key, 'note' => 'Thiếu dấu "/" cuối URL nên bị chuyển hướng 301 thêm một lần.'];
            }

            return ['status' => 'ok', 'target' => $key, 'note' => ''];
        }

        return ['status' => 'broken', 'target' => null, 'note' => $brokenNote];
    }

    private function followRedirects(string $path): ?string
    {
        $current = $path;
        $visited = [$path => true];

        for ($hop = 0; $hop < 5; $hop++) {
            $next = null;
            foreach ($this->redirects as $redirect) {
                $source = (string) $redirect['sourceUrl'];
                $matched = match ($redirect['matchType']) {
                    'exact' => $current === $source,
                    'wildcard' => (bool) preg_match('#^' . str_replace('\*', '.*', preg_quote($source, '#')) . '$#i', $current),
                    'regex' => @preg_match(str_starts_with($source, '/') || str_starts_with($source, '#') ? $source : '#' . $source . '#i', $current) === 1,
                    default => false,
                };

                if ($matched) {
                    $next = (string) (parse_url((string) $redirect['destinationUrl'], PHP_URL_PATH) ?: '/');
                    break;
                }
            }

            if ($next === null || isset($visited[$next])) {
                break;
            }

            $visited[$next] = true;
            $current = $next;
        }

        return $current === $path ? null : $current;
    }

    private function computeMetrics(): void
    {
        $depth = ['home' => 0];
        $queue = ['home' => 0];
        while ($queue) {
            asort($queue);
            $current = array_key_first($queue);
            $distance = $queue[$current];
            unset($queue[$current]);

            foreach ($this->edges[$current] ?? [] as $target => $clicks) {
                if (!isset($depth[$target]) || $distance + $clicks < $depth[$target]) {
                    $depth[$target] = $distance + $clicks;
                    $queue[$target] = $distance + $clicks;
                }
            }
        }

        foreach ($this->pages as $key => &$page) {
            $page['depth'] = $depth[$key] ?? null;
            if ($key === 'home') {
                continue;
            }

            $issues = [];
            $tier = $page['tier'];
            $isContent = in_array($page['type'], ['post', 'page', 'category'], true);

            if ($page['indexable'] && $tier !== 'trust') {
                if ($page['inContent'] === 0 && !$page['inMenu'] && $page['inStructure'] === 0) {
                    $issues[] = 'orphan';
                } elseif ($page['inContent'] === 0) {
                    $issues[] = 'no_contextual';
                } elseif ($page['inContent'] === 1 && !$page['inMenu']) {
                    $issues[] = 'single_source';
                }

                // 1. Money Page thiếu lực đẩy (< 3 bài trỏ về)
                if ($tier === 'money' && $page['inContent'] < self::MIN_MONEY_PAGE_INLINKS) {
                    $issues[] = 'money_starved';
                }

                // 2. Local Page thiếu lực đẩy (< 2 bài trỏ về)
                if ($tier === 'local' && $page['inContent'] < self::MIN_LOCAL_PAGE_INLINKS) {
                    $issues[] = 'local_starved';
                }

                if ($page['depth'] === null) {
                    if (!in_array('orphan', $issues, true)) {
                        $issues[] = 'unreachable';
                    }
                } elseif ($page['depth'] > self::MAX_CLICK_DEPTH) {
                    $issues[] = 'deep';
                }
            }

            if ($isContent && $page['type'] !== 'category' && $tier !== 'trust' && $page['outInternal'] === 0) {
                $issues[] = 'dead_end';
            } elseif (
                in_array($tier, ['blog', 'project'], true)
                && $page['wordCount'] >= 300
                && $page['outMoneyLinks'] === 0
                && $page['outLocalLinks'] === 0
                && $page['outHubLinks'] === 0
            ) {
                $issues[] = 'no_money_link';
            }

            // Bài Dự án (Project) có địa danh nhưng chưa trỏ link về Local Page cùng khu vực
            if ($tier === 'project' && $page['locationName'] !== null && $page['outLocalLinks'] === 0) {
                $issues[] = 'project_no_local';
            }

            $totalLinks = $page['outInternal'] + $page['outExternal'];
            if ($totalLinks > self::MAX_CONTENT_LINKS || ($totalLinks >= 10 && $page['wordCount'] > 0 && $page['wordCount'] / $totalLinks < self::MIN_WORDS_PER_LINK)) {
                $issues[] = 'too_many_links';
            }

            $page['issues'] = $issues;
        }
        unset($page);
    }

    /**
     * Tìm cơ hội đặt link ngữ cảnh theo luồng:
     *  - Blog / Project / Local -> Money Page
     *  - Project (Quận X) / Khu vực thi công -> Local Page (Quận X)
     *  - Blog -> Blog / Hub cùng cụm Silo
     */
    private function findOpportunities(array $linkableText): array
    {
        $linked = [];
        foreach ($this->links as $link) {
            if ($link['kind'] === 'content' && $link['target'] !== null) {
                $linked[$link['source']][$link['target']] = true;
            }
        }

        $lowerText = array_map('mb_strtolower', array_filter($linkableText));
        $opportunities = [];
        $seenPairs = [];

        // 1. Ưu tiên ghép cặp Local Silo: Dự án thực tế (Quận X) -> Local Page (Quận X)
        $localPagesByLocation = [];
        foreach ($this->pages as $k => $p) {
            if ($p['tier'] === 'local' && $p['locationName'] !== null && $p['indexable']) {
                $localPagesByLocation[$p['locationName']][] = $k;
            }
        }

        foreach ($this->pages as $sourceKey => $sourcePage) {
            if ($sourcePage['tier'] !== 'project' || $sourcePage['locationName'] === null || empty($lowerText[$sourceKey])) {
                continue;
            }
            $locName = $sourcePage['locationName'];
            foreach ($localPagesByLocation[$locName] ?? [] as $localTargetKey) {
                if ($sourceKey === $localTargetKey || isset($linked[$sourceKey][$localTargetKey])) {
                    continue;
                }
                $targetPage = $this->pages[$localTargetKey];
                $anchorHint = $targetPage['keywords'][0] ?? ('xây nhà trọn gói ' . $locName);
                $locLower = mb_strtolower($locName);
                $pos = mb_strpos($lowerText[$sourceKey], $locLower);
                $start = $pos !== false ? max(0, $pos - 50) : 0;

                $opportunities[] = [
                    'source' => $sourceKey,
                    'target' => $localTargetKey,
                    'keyword' => $anchorHint,
                    'snippet' => ($start > 0 ? '…' : '') . mb_substr($linkableText[$sourceKey], $start, 150) . '…',
                    'targetTier' => 'local',
                    'isMoneyTarget' => false,
                    'isLocalTarget' => true,
                    'sameCluster' => true,
                    'flowLabel' => sprintf('Dự án %s → Local Page %s', $locName, $locName),
                    'powerScore' => 92,
                ];
                $seenPairs[$sourceKey][$localTargetKey] = true;
            }
        }

        // 2. Quét từ khóa ngữ cảnh cho Money Pages, Local Pages, Hub và Blog
        $targets = array_filter($this->pages, static fn (array $page) => $page['indexable'] && $page['tier'] !== 'trust' && !empty($page['keywords']));
        $tierPriority = ['money' => 4, 'local' => 3, 'hub' => 2, 'project' => 1, 'blog' => 0];
        uasort($targets, static function (array $a, array $b) use ($tierPriority) {
            $pa = $tierPriority[$a['tier']] ?? 0;
            $pb = $tierPriority[$b['tier']] ?? 0;
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }

            return $a['inContent'] <=> $b['inContent'];
        });

        $sourceKeys = array_keys($lowerText);
        usort($sourceKeys, fn (string $a, string $b) => ($this->pages[$b]['linkJuice'] ?? 0) <=> ($this->pages[$a]['linkJuice'] ?? 0));

        foreach ($targets as $targetKey => $target) {
            $found = 0;
            foreach ($sourceKeys as $sourceKey) {
                if ($sourceKey === $targetKey || isset($linked[$sourceKey][$targetKey]) || isset($seenPairs[$sourceKey][$targetKey])) {
                    continue;
                }

                $text = $lowerText[$sourceKey];
                $sourcePage = $this->pages[$sourceKey];

                foreach ($target['keywords'] as $keyword) {
                    $position = mb_strpos($text, $keyword);
                    if ($position === false) {
                        continue;
                    }

                    $sameCluster = ($sourcePage['clusterKey'] !== null && ($sourcePage['clusterKey'] === $target['clusterKey'] || $sourcePage['clusterKey'] === $targetKey))
                        || ($sourcePage['locationName'] !== null && $sourcePage['locationName'] === $target['locationName']);

                    $baseScore = match ($target['tier']) {
                        'money' => 50,
                        'local' => 42,
                        'hub' => 25,
                        default => 15,
                    };

                    $powerScore = (int) round(
                        $baseScore
                        + ($sameCluster ? 22 : 0)
                        + min(18, ($sourcePage['linkJuice'] ?? 0) * 0.22)
                        + min(10, log10(1 + ($sourcePage['viewCounts'] ?? 0)) * 3)
                    );

                    $start = max(0, $position - 60);
                    $opportunities[] = [
                        'source' => $sourceKey,
                        'target' => $targetKey,
                        'keyword' => $keyword,
                        'snippet' => ($start > 0 ? '…' : '') . mb_substr($linkableText[$sourceKey], $start, mb_strlen($keyword) + 120) . '…',
                        'targetTier' => $target['tier'],
                        'isMoneyTarget' => $target['tier'] === 'money',
                        'isLocalTarget' => $target['tier'] === 'local',
                        'sameCluster' => $sameCluster,
                        'flowLabel' => sprintf('%s → %s', self::shortTierName($sourcePage['tier']), self::shortTierName($target['tier'])),
                        'powerScore' => min(100, $powerScore),
                    ];
                    $seenPairs[$sourceKey][$targetKey] = true;
                    $found++;
                    break;
                }

                if ($found >= self::MAX_OPPORTUNITIES_PER_TARGET || count($opportunities) >= self::MAX_OPPORTUNITIES) {
                    break;
                }
            }

            if (count($opportunities) >= self::MAX_OPPORTUNITIES) {
                break;
            }
        }

        usort($opportunities, static fn (array $a, array $b) => $b['powerScore'] <=> $a['powerScore']);

        return $opportunities;
    }

    private static function shortTierName(string $tier): string
    {
        return match ($tier) {
            'money' => 'Money Page',
            'local' => 'Local Page',
            'project' => 'Dự án',
            'blog' => 'Blog',
            'hub' => 'Danh mục',
            'trust' => 'Trust Page',
            default => 'Trang',
        };
    }

    private function summarize(array $opportunities): array
    {
        $summary = [
            'pages' => 0, 'indexablePages' => 0,
            'moneyPages' => 0, 'localPages' => 0, 'projectPages' => 0, 'blogPages' => 0, 'hubPages' => 0, 'trustPages' => 0,
            'moneyStarved' => 0, 'localStarved' => 0, 'avgMoneyInContent' => 0.0, 'avgMoneyJuice' => 0.0, 'avgLocalInContent' => 0.0,
            'orphan' => 0, 'no_contextual' => 0, 'money_starved' => 0, 'local_starved' => 0,
            'no_money_link' => 0, 'project_no_local' => 0,
            'single_source' => 0, 'dead_end' => 0, 'deep' => 0, 'unreachable' => 0, 'too_many_links' => 0,
            'contentLinks' => 0, 'internalLinks' => 0, 'externalLinks' => 0, 'menuLinks' => 0,
            'broken' => 0, 'redirect' => 0, 'noncanonical' => 0, 'uncrawlable' => 0,
            'nofollow' => 0, 'generic' => 0, 'empty' => 0, 'long' => 0, 'adjacent' => 0, 'self' => 0,
            'noindex_target' => 0, 'duplicate_target' => 0, 'cannibal_anchor' => 0,
            'opportunities' => count($opportunities),
            'moneyOpportunities' => count(array_filter($opportunities, static fn (array $o) => ($o['targetTier'] ?? '') === 'money')),
            'localOpportunities' => count(array_filter($opportunities, static fn (array $o) => ($o['targetTier'] ?? '') === 'local')),
            'avgInContent' => 0.0,
        ];

        $inContent = 0;
        $moneyInContent = 0;
        $moneyJuice = 0.0;
        $localInContent = 0;

        foreach ($this->pages as $key => $page) {
            if ($key === 'home' || $page['type'] === 'route') {
                continue;
            }
            $summary['pages']++;
            $tierKey = $page['tier'] . 'Pages';
            if (isset($summary[$tierKey])) {
                $summary[$tierKey]++;
            }

            if ($page['indexable']) {
                $summary['indexablePages']++;
                $inContent += $page['inContent'];
                if ($page['tier'] === 'money') {
                    $moneyInContent += $page['inContent'];
                    $moneyJuice += $page['linkJuice'];
                } elseif ($page['tier'] === 'local') {
                    $localInContent += $page['inContent'];
                }
            }
            foreach ($page['issues'] as $issue) {
                if (isset($summary[$issue])) {
                    $summary[$issue]++;
                }
            }
        }
        $summary['avgInContent'] = $summary['indexablePages'] ? round($inContent / $summary['indexablePages'], 1) : 0.0;
        $summary['avgMoneyInContent'] = $summary['moneyPages'] ? round($moneyInContent / $summary['moneyPages'], 1) : 0.0;
        $summary['avgMoneyJuice'] = $summary['moneyPages'] ? round($moneyJuice / $summary['moneyPages'], 1) : 0.0;
        $summary['avgLocalInContent'] = $summary['localPages'] ? round($localInContent / $summary['localPages'], 1) : 0.0;
        $summary['moneyStarved'] = $summary['money_starved'];
        $summary['localStarved'] = $summary['local_starved'];

        foreach ($this->links as $link) {
            if ($link['kind'] === 'menu') {
                $summary['menuLinks']++;
            } else {
                $summary['contentLinks']++;
                $summary[$link['status'] === 'external' ? 'externalLinks' : 'internalLinks']++;
            }

            if (isset($summary[$link['status']]) && in_array($link['status'], ['broken', 'redirect', 'noncanonical', 'uncrawlable'], true)) {
                $summary[$link['status']]++;
            }
            foreach ($link['flags'] as $flag) {
                if (isset($summary[$flag])) {
                    $summary[$flag]++;
                }
            }
        }

        return $summary;
    }

    private function score(array $s): int
    {
        $pages = max(1, $s['indexablePages']);
        $moneyAndLocal = max(1, $s['moneyPages'] + $s['localPages']);
        $links = max(1, $s['internalLinks'] + $s['menuLinks']);
        $ratio = static fn (int $count, int $total, float $factor = 1.0) => min(1.0, $factor * $count / $total);

        $penalty = 25 * $ratio($s['orphan'] + $s['unreachable'], $pages)
            + 20 * $ratio($s['broken'] + $s['uncrawlable'], $links, 5)
            + 15 * $ratio($s['money_starved'] + $s['local_starved'], $moneyAndLocal)
            + 12 * $ratio($s['no_contextual'], $pages)
            + 8 * $ratio($s['no_money_link'] + $s['project_no_local'] + $s['dead_end'] + $s['deep'], $pages)
            + 10 * $ratio($s['redirect'] + $s['noncanonical'] + $s['noindex_target'], $links, 3)
            + 5 * $ratio($s['generic'] + $s['empty'] + $s['long'] + $s['adjacent'] + $s['cannibal_anchor'], $links, 3)
            + 5 * $ratio($s['nofollow'], $links, 5);

        return (int) max(0, round(100 - $penalty));
    }

    /**
     * @return string[]
     */
    private function resolveOwnHosts(): array
    {
        $hosts = [
            $this->router->getContext()->getHost(),
            'kientruckimanh.com',
            'www.kientruckimanh.com',
            'localhost',
        ];

        return array_values(array_unique(array_filter($hosts)));
    }

    private static function isChained(\DOMElement $node): bool
    {
        for ($sibling = $node->previousSibling; $sibling !== null; $sibling = $sibling->previousSibling) {
            if ($sibling instanceof \DOMText && trim($sibling->textContent, " \t\n\r\0\x0B\xC2\xA0") === '') {
                continue;
            }

            return $sibling instanceof \DOMElement && strtolower($sibling->tagName) === 'a';
        }

        return false;
    }

    private static function collectHtml($data): string
    {
        if (is_string($data)) {
            return str_contains($data, '<a') ? ' ' . $data : '';
        }

        $html = '';
        foreach (is_array($data) ? $data : [] as $value) {
            $html .= self::collectHtml($value);
        }

        return $html;
    }

    /**
     * @return string[]
     */
    private static function extractEntityKeywords(?string $rawKeywords, string $title, string $slug): array
    {
        $keywords = [];
        foreach (explode(',', (string) $rawKeywords) as $keyword) {
            $keyword = mb_strtolower(self::normalizeText($keyword));
            if ($keyword !== '' && str_contains($keyword, ' ') && mb_strlen($keyword) >= 6) {
                $keywords[$keyword] = true;
            }
        }

        if (empty($keywords) && $title !== '') {
            $parts = preg_split('/[:|\-–—()]/u', $title);
            foreach ($parts as $part) {
                $clean = mb_strtolower(self::normalizeText($part));
                $clean = (string) preg_replace('/\b(năm\s+)?20\d{2}\b/u', '', $clean);
                $clean = self::normalizeText($clean);
                $words = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
                if (count($words) >= 3 && count($words) <= 7) {
                    $keywords[$clean] = true;
                }
            }
        }

        return array_slice(array_keys($keywords), 0, 8);
    }

    private static function normalizeText(string $value): string
    {
        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $value));
    }
}
