<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\SitemapService;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\SiteUrlClassification;

class SyncUrlClassificationCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SitemapService
     */
    private $sitemapService;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * Constructor
     */
    public function __construct(
        EntityManagerInterface $em,
        SitemapService $sitemapService,
        UrlGeneratorInterface $router
    ) {
        parent::__construct();
        $this->em = $em;
        $this->sitemapService = $sitemapService;
        $this->router = $router;
    }

    protected function configure()
    {
        $this
            ->setName('app:silo:sync')
            ->setDescription('Quét và phân loại toàn bộ URL của website từ sitemap, phân tích cấu trúc liên kết nội bộ (SILO)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn = $this->em->getConnection();

        $output->writeln('<info>Bắt đầu dọn dẹp dữ liệu URL cũ...</info>');
        $conn->query('SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE site_url_classification; SET FOREIGN_KEY_CHECKS = 1;');

        // Lấy base URL từ router (để dùng chuẩn hoá URL)
        $baseUrl = $this->router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $output->writeln(sprintf('<comment>Base URL được xác định: %s</comment>', $baseUrl));

        $output->writeln('<info>1. Quét danh mục (NewsCategory) & Bài viết (News) đang kích hoạt để làm bản đồ...</info>');
        $categories = $this->em->getRepository(NewsCategory::class)->findBy(['enable' => true]);
        $newsList = $this->em->getRepository(News::class)->findBy(['enable' => true]);

        // Tạo bản đồ đối chiếu URL tương đối -> Thực thể Category
        $catMap = [];
        foreach ($categories as $category) {
            $fullUrl = $this->sitemapService->generateCategoryUrl($category);
            $path = $this->normalizePath($fullUrl, $baseUrl);
            $catMap[$path] = $category;
        }

        // Tạo bản đồ đối chiếu URL tương đối -> Thực thể News (hoặc Page)
        $newsMap = [];
        foreach ($newsList as $news) {
            $fullUrl = $this->sitemapService->generateNewsUrl($news);
            $path = $this->normalizePath($fullUrl, $baseUrl);
            $newsMap[$path] = $news;
        }

        $output->writeln('<info>2. Lập danh mục URL từ sitemap.xml và phân loại...</info>');
        $sitemapUrls = $this->sitemapService->generateSitemap();
        $urlMap = []; // path => SiteUrlClassification

        foreach ($sitemapUrls as $item) {
            $url = $item['url'];
            $path = $this->normalizePath($url, $baseUrl);

            // Tránh trùng lặp
            if (isset($urlMap[$path])) {
                continue;
            }

            $classification = new SiteUrlClassification();
            $classification->setUrl($path);
            $classification->setInboundLinks(0);
            $classification->setOutboundLinks(0);
            $classification->setUpdatedAt(new \DateTime());

            // 2.1. Phục vụ bóc tách danh mục liên quan của URL để phân loại chuẩn xác
            $categorySlugs = [];
            $title = '';
            $isPage = false;

            if (isset($catMap[$path])) {
                $cat = $catMap[$path];
                $classification->setSourceType('category');
                $classification->setSourceId($cat->getId());
                $title = $cat->getName();
                
                $categorySlugs[] = $cat->getUrl();
                if ($cat->getParentcat() !== 'root' && $cat->getParentcat() !== null) {
                    $parent = $cat->getParentcat();
                    if (is_object($parent)) {
                        $categorySlugs[] = $parent->getUrl();
                    }
                }
            } elseif (isset($newsMap[$path])) {
                $news = $newsMap[$path];
                $classification->setSourceType($news->isPage() ? 'page' : 'news');
                $classification->setSourceId($news->getId());
                $title = $news->getTitle();
                $isPage = $news->isPage();

                // Lấy tất cả category của bài viết này
                foreach ($news->getCategory() as $cat) {
                    $categorySlugs[] = $cat->getUrl();
                    if ($cat->getParentcat() !== 'root' && $cat->getParentcat() !== null) {
                        $parent = $cat->getParentcat();
                        if (is_object($parent)) {
                            $categorySlugs[] = $parent->getUrl();
                        }
                    }
                }
            } else {
                $classification->setSourceType('static');
                $classification->setSourceId(null);
                
                if ($path === '/') {
                    $title = 'Trang chủ';
                } elseif ($path === '/lien-he') {
                    $title = 'Liên hệ';
                } else {
                    $title = basename($path);
                }
            }

            // Tiến hành phân loại thông qua hàm chuyên biệt
            $type = $this->determineUrlType($path, $title, $categorySlugs, $isPage);
            $classification->setType($type);

            $this->em->persist($classification);
            $urlMap[$path] = $classification;
        }

        $this->em->flush();

        $output->writeln('<info>3. Phân tích đồ thị liên kết nội bộ (Internal Link Graph)...</info>');
        // Quét nội dung của tất cả bài viết để đếm liên kết trỏ tới sitemap URLs
        foreach ($newsList as $news) {
            $content = $news->getContents();
            if ($news->isPage() && $news->isPageBuilderEnabled()) {
                $content .= ' ' . $news->getPageBuilderData();
            }

            if (empty($content)) {
                continue;
            }

            // Tìm tất cả thuộc tính href="..." hoặc href='...'
            preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
            if (empty($matches[1])) {
                continue;
            }

            // Xác định path nguồn
            $sourceUrl = $this->sitemapService->generateNewsUrl($news);
            $sourcePath = $this->normalizePath($sourceUrl, $baseUrl);

            $sourceClassification = $urlMap[$sourcePath] ?? null;
            if (!$sourceClassification) {
                continue;
            }

            $outboundCount = 0;
            $loggedLinks = []; // Tránh đếm trùng lặp nhiều link trỏ tới cùng 1 đích trong cùng 1 bài viết

            foreach ($matches[1] as $href) {
                $targetPath = $this->normalizePath($href, $baseUrl);

                // Không đếm self-link (link trỏ về chính nó)
                if ($targetPath === $sourcePath) {
                    continue;
                }

                if (in_array($targetPath, $loggedLinks)) {
                    continue;
                }

                // Đối chiếu với danh sách URL được lập từ sitemap.xml
                if (isset($urlMap[$targetPath])) {
                    $targetObj = $urlMap[$targetPath];
                    $targetObj->setInboundLinks($targetObj->getInboundLinks() + 1);
                    $outboundCount++;
                    $loggedLinks[] = $targetPath;
                }
            }

            $sourceClassification->setOutboundLinks($outboundCount);
        }

        $this->em->flush();

        // Thống kê kết quả
        $totalMoney = 0;
        $totalInfo = 0;
        $totalTrust = 0;
        foreach ($urlMap as $obj) {
            if ($obj->getType() === 'money') $totalMoney++;
            if ($obj->getType() === 'info') $totalInfo++;
            if ($obj->getType() === 'trust') $totalTrust++;
        }

        $output->writeln('');
        $output->writeln('<info>Đồng bộ cấu trúc SILO hoàn tất!</info>');
        $output->writeln(sprintf('<comment>Tổng số URL phân loại trong Sitemap: %d</comment>', count($urlMap)));
        $output->writeln(sprintf(' - Money Pages: %d', $totalMoney));
        $output->writeln(sprintf(' - Info Pages: %d', $totalInfo));
        $output->writeln(sprintf(' - Trust Pages: %d', $totalTrust));
    }

    /**
     * Trích xuất và chuẩn hoá đường dẫn tương đối từ URL bất kỳ dựa trên Base URL của hệ thống.
     * Hỗ trợ xử lý cấu trúc URL phẳng và lọc bỏ locale, index.php tự động.
     *
     * @param string $url
     * @param string $baseUrl
     * @return string
     */
    private function normalizePath($url, $baseUrl)
    {
        // 1. Loại bỏ phần query string (?...) hoặc anchor (#...) nếu có
        $cleanUrl = preg_replace('/[?#].*$/', '', $url);

        $baseUrlPath = parse_url($baseUrl, PHP_URL_PATH);
        $urlPath = parse_url($cleanUrl, PHP_URL_PATH);

        if (empty($urlPath)) {
            return '/';
        }

        if (!empty($baseUrlPath)) {
            // Đảm bảo kết thúc bằng dấu gạch chéo để so sánh chính xác
            $baseUrlPath = rtrim($baseUrlPath, '/') . '/';
            
            // Cắt bỏ phần base URL path ở đầu
            if (strpos($urlPath, $baseUrlPath) === 0) {
                $urlPath = substr($urlPath, strlen($baseUrlPath));
            } else {
                // Thử trường hợp base URL không có locale hoặc không có index.php (ví dụ: /kientruc/public/index.php/)
                $alternativePath = str_replace('/index.php', '', $baseUrlPath);
                if (strpos($urlPath, $alternativePath) === 0) {
                    $urlPath = substr($urlPath, strlen($alternativePath));
                }
            }
        }

        // 2. Chuẩn hoá định dạng kết quả (xoá gạch chéo thừa ở đầu/cuối, thêm gạch chéo đầu)
        $normalized = '/' . trim(rtrim($urlPath, '/'), '/');
        
        return $normalized === '//' ? '/' : $normalized;
    }

    /**
     * Phân loại URL dựa trên quy tắc SEO E-E-A-T nâng cao
     * 
     * @param string $path
     * @param string $title
     * @param array $categorySlugs
     * @param bool $isPage
     * @return string
     */
    private function determineUrlType($path, $title, $categorySlugs = [], $isPage = false)
    {
        $pathLower = strtolower($path);
        $titleLower = strtolower($title);

        // 1. Phân loại Trust Pages (Trang tạo dựng lòng tin, E-E-A-T, Hồ sơ công trình thực tế)
        // 1.1. Trang tĩnh cốt lõi
        if ($path === '/' || $path === '') {
            return 'trust';
        }
        
        $trustStaticKeywords = ['gioi-thieu', 'giới thiệu', 'lien-he', 'liên hệ', 'contact', 'about', 'chinh-sach', 'chính sách', 'dieu-khoan', 'điều khoản', 'tuyen-dung', 'tuyển dụng'];
        foreach ($trustStaticKeywords as $kw) {
            if (strpos($pathLower, $kw) !== false || strpos($titleLower, $kw) !== false) {
                return 'trust';
            }
        }

        // 1.2. Công trình, dự án thi công thực tế (Case study minh chứng năng lực thực tế)
        foreach ($categorySlugs as $slug) {
            $slugLower = strtolower($slug);
            if (strpos($slugLower, 'cong-trinh') !== false || strpos($slugLower, 'du-an') !== false || strpos($slugLower, 'thuc-te') !== false) {
                return 'trust';
            }
        }
        if (strpos($pathLower, 'cong-trinh') !== false || strpos($pathLower, 'du-an') !== false) {
            return 'trust';
        }

        // 2. Phân loại Money Pages (Trang bán hàng, Landing page dịch vụ, Mẫu thiết kế thu hút Lead)
        $moneyKeywords = [
            'bao-gia', 'báo giá',
            'don-gia', 'đơn giá',
            'thi-cong', 'thi công',
            'chi-phi', 'chi phí',
            'thiet-ke', 'thiết kế',
            'xay-dung', 'xây dựng',
            'bang-gia', 'bảng giá',
            'dich-vu', 'dịch vụ',
            'mau-nha', 'mẫu nhà',
            'mau-biet-thu', 'mẫu biệt thự',
            'tinh-phi', 'tính phí',
            'calculator', 'sua-nha', 'sửa nhà'
        ];
        
        foreach ($moneyKeywords as $kw) {
            if (strpos($pathLower, $kw) !== false || strpos($titleLower, $kw) !== false) {
                return 'money';
            }
        }

        foreach ($categorySlugs as $slug) {
            $slugLower = strtolower($slug);
            if (strpos($slugLower, 'mau-nha') !== false || strpos($slugLower, 'thiet-ke') !== false || strpos($slugLower, 'thi-cong') !== false) {
                return 'money';
            }
        }

        // 3. Phân loại Info Pages (Trang cung cấp kiến thức, blog tin tức, phong thuỷ, hướng dẫn)
        return 'info';
    }

    private function isMoneyKeyword($str)
    {
        $keywords = [
            'bao-gia', 'báo giá',
            'don-gia', 'đơn giá',
            'thi-cong', 'thi công',
            'chi-phi', 'chi phí',
            'thiet-ke', 'thiết kế',
            'xay-dung', 'xây dựng',
            'bang-gia', 'bảng giá',
            'dich-vu', 'dịch vụ',
            'sua-nha', 'sửa nhà'
        ];

        foreach ($keywords as $kw) {
            if (strpos($str, $kw) !== false) {
                return true;
            }
        }
        return false;
    }
}
