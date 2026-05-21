<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\NewsCategory;
use App\Entity\News;
use App\Entity\AIContentAudit;
use App\Entity\Rating;
use App\Form\NewsCategoryType;
use App\Form\NewsType;
use App\Service\ActivityLogService;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller used to manage post contents in the backend.
 *
 * @Route("/admin/news")
 * @Security("has_role('ROLE_ADMIN')")
 */

class NewsController extends Controller
{
    /**
     * Lists all News entities.
     *
     * @Route("/", name="admin_news_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(News::class);
        $searchQuery = trim((string) $request->query->get('q', ''));

        if ($searchQuery !== '') {
            $news = $repository->searchPosts($searchQuery);
        } else {
            $news = $repository->findAllPosts();
        }

        return $this->render('admin/news/index.html.twig', [
            'objects' => $news,
            'search_query' => $searchQuery,
        ]);
    }

    /**
     * Lists all News entities by category.
     *
     * @Route("/list/{categoryId}", name="admin_news_list_by_category")
     * @Method("GET")
     */
    public function listAction(Request $request, $categoryId)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(News::class);
        $searchQuery = trim((string) $request->query->get('q', ''));

        if ($searchQuery !== '') {
            $news = $repository->searchPosts($searchQuery, $categoryId);
        } else {
            $news = $repository
                ->createQueryBuilder('n')
                ->leftJoin('n.category', 'c')
                ->where('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId)
                ->orderBy('n.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('admin/news/list.html.twig', [
            'objects' => $news,
            'search_query' => $searchQuery,
            'category_id' => $categoryId,
        ]);
    }

    

    /**
     * Creates a new News entity.
     *
     * @Route("/new", name="admin_news_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger)
    {
        $news = new News();
        $news->setAuthor($this->getUser());

        $form = $this->createForm(NewsType::class, $news)
            ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($news);
                $em->flush();

                // Update Ordering for post
                $news->setOrdering( $news->getId() );
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                // Activity Log
                $this->get(ActivityLogService::class)->log(
                    ActivityLog::ACTION_CREATE,
                    ActivityLog::ENTITY_NEWS,
                    $news->getId(),
                    $news->getTitle()
                );

                $this->addFlash('success', 'action.created_successfully');

                if ($form->get('saveAndCreateNew')->isClicked()) {
                    return $this->redirectToRoute('admin_news_new');
                }

                return $this->redirectToRoute('admin_news_edit', array(
                    'id' => $news->getId()
                ));
            } catch (\DBALException $e) {
                $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\PDOException $e) {
                $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\ORMException $e) {
                $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\Exception $e) {
                $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
            }

            $this->addFlash('error', $message);

            return $this->render('admin/news/new.html.twig', [
                'news' => $news,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('admin/news/new.html.twig', [
            'news' => $news,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing News entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_news_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, News $news, Slugger $slugger)
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $unitOfWork = $em->getUnitOfWork();
                $originalData = $unitOfWork->getOriginalEntityData($news);

                // Update createdAt if enable changed from false to true
                if (isset($originalData['enable']) && !$originalData['enable'] && $news->getEnable()) {
                    $news->setCreatedAt(new \DateTime());
                }

                // Handle postType change logic
                $originalPostType = isset($originalData['postType']) ? $originalData['postType'] : 'post';
                $newPostType = $news->getPostType();

                // From post to page
                if ($originalPostType === 'post' && $newPostType === 'page') {
                    // Remove relationships with categories and tags
                    $news->getCategory()->clear();
                    $news->getTags()->clear();
                    // Ensure parent is null for new pages
                    $news->setParent(null);
                }
                // From page to post
                elseif ($originalPostType === 'page' && $newPostType === 'post') {
                    // Remove parent relationship
                    $news->setParent(null);
                }

                // Capture changes before flush
                $diffDetails = $this->get(ActivityLogService::class)->getEntityDiff($news);

                $em->flush();

                // Activity Log
                $this->get(ActivityLogService::class)->log(
                    ActivityLog::ACTION_UPDATE,
                    ActivityLog::ENTITY_NEWS,
                    $news->getId(),
                    $news->getTitle(),
                    $diffDetails
                );

                $this->addFlash('success', 'action.updated_successfully');

                // If postType changed to page, redirect to page edit
                if ($originalPostType === 'post' && $newPostType === 'page') {
                    return $this->redirectToRoute('admin_page_edit', array(
                        'id' => $news->getId()
                    ));
                }

                return $this->redirectToRoute('admin_news_edit', array(
                    'id' => $news->getId()
                ));
            } catch (\DBALException $e) {
                $message = sprintf('DBALException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\PDOException $e) {
                $message = sprintf('PDOException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\ORMException $e) {
                $message = sprintf('ORMException [%i]: %s', $e->getCode(), $e->getMessage());
            } catch (\Exception $e) {
                $message = sprintf('Exception [%i]: %s', $e->getCode(), $e->getMessage());
            }

            $this->addFlash('error', $message);

            return $this->render('admin/news/edit.html.twig', [
                'news' => $news,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('admin/news/edit.html.twig', [
            'news' => $news,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a News entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_news_delete")
     */
    public function deleteAction(Request $request, $id, News $news)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_news_index');
        }

        $newsTitle = $news->getTitle();
        $newsId = $news->getId();

        $news->getTags()->clear();

        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();

        // Activity Log
        $this->get(ActivityLogService::class)->log(
            ActivityLog::ACTION_DELETE,
            ActivityLog::ENTITY_NEWS,
            $newsId,
            $newsTitle
        );

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_news_index');
    }

    /**
     * @Route("/disable", name="admin_news_disable")
     */
    public function disableAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $news = $this->getDoctrine()->getRepository(News::class)->find($request->request->get('newsId'));
        
        if ($news) {
            $news->setEnable($request->request->get('enable'));
        }

        $em->persist($news);

        $em->flush();

        // Activity Log
        $this->get(ActivityLogService::class)->log(
            ActivityLog::ACTION_TOGGLE,
            ActivityLog::ENTITY_NEWS,
            $news->getId(),
            $news->getTitle(),
            $news->getEnable() ? 'Bật hiển thị' : 'Tắt hiển thị'
        );

        return new Response(
            json_encode(
                array(
                    'status'=>'success',
                    'message' => 'Thao tác thành công'
                )
            )
        );
    }

    /**
     * Call local Python AI Service to audit a news article.
     *
     * @Route("/{id}/ai-audit", name="admin_news_ai_audit", methods={"POST"})
     */
    public function aiAuditAction(Request $request, News $news)
    {
        $title = $news->getTitle();
        // Lấy nội dung thô (hoặc xử lý strip tags nếu cần, nhưng FastAPI có clean_html rồi)
        $content = $news->getContents();

        $postData = json_encode([
            'title' => $title,
            'content' => $content
        ]);

        $aiServiceUrl = getenv('AI_SERVICE_URL') ?: ($_ENV['AI_SERVICE_URL'] ?? 'http://127.0.0.1:8000/analyze');
        $ch = curl_init($aiServiceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90); // Tăng timeout lên 90 giây cho AI/NLP

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return new Response(
                json_encode([
                    'status' => 'error',
                    'message' => 'Không thể kết nối tới Dịch vụ AI local. Lỗi: ' . ($curlError ?: 'HTTP Code ' . $httpCode)
                ]),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $data = json_decode($response, true);
        if (!$data) {
            return new Response(
                json_encode([
                    'status' => 'error',
                    'message' => 'Dữ liệu phản hồi từ AI không đúng định dạng.'
                ]),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $em = $this->getDoctrine()->getManager();
        $aiAudit = $news->getAiAudit();

        if (!$aiAudit) {
            $aiAudit = new AIContentAudit();
            $aiAudit->setNews($news);
            $news->setAiAudit($aiAudit);
        }

        $aiAudit->setEeatScore($data['eeat_score'] ?? null);
        $aiAudit->setEeatFeedback(json_encode($data['eeat_feedback'] ?? [], JSON_UNESCAPED_UNICODE));
        $aiAudit->setAeoScore($data['aeo_score'] ?? null);
        $aiAudit->setAeoFeedback(json_encode($data['aeo_feedback'] ?? [], JSON_UNESCAPED_UNICODE));
        $aiAudit->setGeoScore($data['geo_score'] ?? null);
        $aiAudit->setNlpMetrics(json_encode($data['nlp_metrics'] ?? [], JSON_UNESCAPED_UNICODE));
        $aiAudit->setSemanticTriples(json_encode($data['semantic_triples'] ?? [], JSON_UNESCAPED_UNICODE));
        $aiAudit->setUpdatedAt(new \DateTime());

        $em->persist($aiAudit);
        $em->flush();

        // Activity Log
        $this->get(ActivityLogService::class)->log(
            ActivityLog::ACTION_UPDATE,
            ActivityLog::ENTITY_NEWS,
            $news->getId(),
            $news->getTitle(),
            'Đánh giá chất lượng nội dung bằng AI'
        );

        return new Response(
            json_encode([
                'status' => 'success',
                'data' => [
                    'eeat_score' => $aiAudit->getEeatScore(),
                    'eeat_feedback' => $data['eeat_feedback'] ?? [],
                    'aeo_score' => $aiAudit->getAeoScore(),
                    'aeo_feedback' => $data['aeo_feedback'] ?? [],
                    'geo_score' => $aiAudit->getGeoScore(),
                    'nlp_metrics' => $data['nlp_metrics'] ?? [],
                    'semantic_triples' => $data['semantic_triples'] ?? []
                ]
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
