<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\News;
use AppBundle\Form\PageType;
use AppBundle\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage page contents in the backend.
 *
 * @Route("/admin/page")
 * @Security("has_role('ROLE_ADMIN')")
 */

class PageController extends Controller
{
    /**
     * Lists all News entities.
     *
     * @Route("/", name="admin_page_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(News::class);
        $searchQuery = trim((string) $request->query->get('q', ''));

        if ($searchQuery !== '') {
            $pages = $repository->searchPages($searchQuery);
            $pageLevels = [];

            foreach ($pages as $page) {
                $pageLevels[$page->getId()] = $this->getPageLevel($page);
            }
        } else {
            $pages = $repository->findPagesAsTree();
            $pageLevels = [];
        }

        return $this->render('admin/page/index.html.twig', [
            'pages' => $pages,
            'page_levels' => $pageLevels,
            'search_query' => $searchQuery,
        ]);
    }

    /**
     * Creates a new News entity.
     *
     * @Route("/new", name="admin_page_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Slugger $slugger)
    {
        $news = new News();
        $news->setAuthor($this->getUser());
        $news->setPostType('page');

        $form = $this->createForm(PageType::class, $news)
            ->add('save', SubmitType::class)
            ->add('saveAndCreateNew', SubmitType::class);

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

                $em->persist($news);
                $em->flush();

                $this->addFlash('success', 'action.created_successfully');

                if ($form->get('saveAndCreateNew')->isClicked()) {
                    return $this->redirectToRoute('admin_page_new');
                }

                return $this->redirectToRoute('admin_page_edit', array(
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
        }

        return $this->render('admin/page/new.html.twig', [
            'object' => $news,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing News entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_page_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, News $news, Slugger $slugger)
    {
        $form = $this->createForm(PageType::class, $news)
            ->add('save', SubmitType::class);
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
                $originalPostType = isset($originalData['postType']) ? $originalData['postType'] : 'page';
                $newPostType = $news->getPostType();

                // From page to post
                if ($originalPostType === 'page' && $newPostType === 'post') {
                    // Remove parent relationship
                    $news->setParent(null);
                }
                // From post to page
                elseif ($originalPostType === 'post' && $newPostType === 'page') {
                    // Remove relationships with categories and tags
                    $news->getCategory()->clear();
                    $news->getTags()->clear();
                    // Ensure parent is null for pages
                    $news->setParent(null);
                }

                $em->flush();
                $this->addFlash('success', 'action.updated_successfully');

                // If postType changed to post, redirect to news edit
                if ($originalPostType === 'page' && $newPostType === 'post') {
                    return $this->redirectToRoute('admin_news_edit', array(
                        'id' => $news->getId()
                    ));
                }

                return $this->redirectToRoute('admin_page_edit', array(
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

            return $this->render('admin/page/edit.html.twig', [
                'object' => $news,
                'form' => $form->createView(),
            ]);
        }

        return $this->render('admin/page/edit.html.twig', [
            'object' => $news,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a News entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_page_delete")
     */
    public function deleteAction(Request $request, $id, News $page)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_page_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($page);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_page_index');
    }

    /**
     * @Route("/disable", name="admin_page_disable")
     * @Method("POST")
     */
    public function disableAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $page = $this->getDoctrine()->getRepository(News::class)->find($request->request->get('newsId'));

        if ($page && $page->getPostType() === 'page') {
            $page->setEnable((bool) $request->request->get('enable'));
            $em->persist($page);
            $em->flush();
        }

        return new Response(
            json_encode(
                array(
                    'status' => 'success',
                    'message' => 'Thao tác thành công'
                )
            )
        );
    }

    private function getPageLevel(News $page)
    {
        $level = 0;
        $currentParent = $page->getParent();

        while (null !== $currentParent) {
            ++$level;
            $currentParent = $currentParent->getParent();
        }

        return $level;
    }
}
