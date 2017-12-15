<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;
use AppBundle\Entity\Page;
use AppBundle\Entity\Comments;
use AppBundle\Entity\Tags;

class NewsController extends Controller
{
    /**
     * @Route("{level1}/{page}",
     *      name="news_category",
     *      requirements={
     *          "level1" = "tin-tuc|du-an",
     *          "page": "\d+"
     *      })
     * @Route("{level1}/{level2}/{page}",
     *      name="list_category",
     *      requirements={
     *          "level1" = "tin-tuc|du-an",
     *          "page": "\d+"
     *      })
     */
    public function listAction($level1, $level2 = null, $page = 1)
    {
        $category = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->findOneBy(array('url' => $level1, 'enable' => 1));

        if (!$category) {
            throw $this->createNotFoundException("The item does not exist");
        }

        if ( !empty($level2) ) {
            $subCategory = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->findOneBy(array('url' => $level2, 'enable' => 1));

            if (!$subCategory) {
                throw $this->createNotFoundException("The item does not exist");
            }
        }

        // Init breadcrum for category page
        $breadcrumbs = $this->buildBreadcrums((!empty($subCategory) && $subCategory != null) ? $subCategory : $category, null, null);

        // Init pagination for category page.
        if (empty($subCategory)) {
            // Get all post for this category and sub category
            $catIds = array($category->getId());

            $allSubCategory = $this->getDoctrine()
                ->getRepository(NewsCategory::class)
                ->createQueryBuilder('c')
                ->where('c.parentcat = (:parentcat)')
                ->setParameter('parentcat', $category->getId())
                ->getQuery()->getResult();

            foreach ($allSubCategory as $value) {
                $catIds[] = $value->getId();
            }

            $posts = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('p')
                ->where('p.category IN (:catIds)')
                ->setParameter('catIds', $catIds)
                ->getQuery()->getResult();
        } else {
            // Get all post for this category
            $posts = $this->getDoctrine()
                ->getRepository(News::class)
                ->createQueryBuilder('p')
                ->where('p.category = :catId')
                ->setParameter('catId', $subCategory->getId())
                ->getQuery()->getResult();
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $posts,
            $page,
            10
        );

        return $this->render('news/list.html.twig', [
            'category' => (!empty($subCategory) && $subCategory != null) ? $subCategory : $category,
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("{slug}.html",
     *      defaults={"_format"="html"},
     *      name="news_show",
     *      requirements={
     *          "slug": "[^/\.]++"
     *      })
     */
    public function showAction($slug)
    {
        $post = $this->getDoctrine()
            ->getRepository(News::class)
            ->findOneBy(
                array('url' => $slug, 'enable' => 1)
            );

        if (!$post) {
            // Get page
            $page = $this->getDoctrine()
                ->getRepository(Page::class)
                ->findOneBy(array('url' => $slug));

            if (!$page) {
                throw $this->createNotFoundException("The item does not exist");
            }

            // Init breadcrum for this page
            $breadcrumbs = $this->buildBreadcrums(null, null, $page);

            return $this->render('news/page.html.twig', [
                'page' => $page,
            ]);
        }

        // Update viewCount for post
        $post->setViewCounts( $post->getViewCounts() + 1 );
        $this->getDoctrine()->getManager()->flush();

        // Get news related
        $relatedNews = $this->getDoctrine()
            ->getRepository(News::class)
            ->findAll();

        // Get the list tag for post
        $tags = $this->getDoctrine()
            ->getRepository(Tags::class)
            ->createQueryBuilder('t')
            ->innerJoin('t.news', 'n')
            ->where('n.id = :news_id')
            ->setParameter('news_id', $post->getId())
            ->getQuery()->getResult();

        // Get the list comment for post
        $comments = $this->getDoctrine()
            ->getRepository(Comments::class)
            ->createQueryBuilder('c')
            ->where('c.news_id = :news_id')
            ->andWhere('c.approved = :approved')
            ->setParameter('news_id', $post->getId())
            ->setParameter('approved', 1)
            ->getQuery()->getResult();

        // Render form comment for post.
        $form = $this->renderFormComment($post);

        // Init breadcrum for the post
        $breadcrumbs = $this->buildBreadcrums(null, $post, null);

        return $this->render('news/show.html.twig', [
            'post'          => $post,
            'relatedNews'   => $relatedNews,
            'form'          => $form->createView(),
            'tags'          => $tags,
            'comments'      => $comments,
        ]);
    }

    /**
     * Render list news by tag
     * @Route("/tag/{slug}.html", name="tag")
     */
    public function tagAction($slug, Request $request)
    {
        $tag = $this->getDoctrine()
            ->getRepository(Tags::class)
            ->findOneBy(
                array('url' => $slug)
            );

        // Get the list post related to tag
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('n')
            ->innerJoin('n.tags', 't')
            ->where('t.id = :tags_id')
            ->setParameter('tags_id', $tag->getId())
            ->getQuery()->getResult();

        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $posts,
            !empty($request->query->get('page')) ? $request->query->get('page') : 1,
            10
        );

        return $this->render('news/tags.html.twig', [
            'tag' => $tag,
            'pagination' => $pagination
        ]);
    }

    /**
     * Render list recent news
     * @return News
     */
    public function recentNewsAction()
    {
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->findBy(
                array(),
                array('created_at' => 'DESC'),
                10
            );

        return $this->render('news/recent.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * Render list hot news
     * @return News
     */
    public function hotNewsAction()
    {
        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->findBy(
                array(),
                array('viewCounts' => 'DESC'),
                10
            );

        return $this->render('news/hot.html.twig', [
            'posts' => $posts,
        ]);
    }

    /**
     * Render list news by category
     * @return News
     */
    public function listNewsByCategoryAction($categoryId)
    {
        $category = $this->getDoctrine()
            ->getRepository(NewsCategory::class)
            ->find($categoryId);

        $posts = $this->getDoctrine()
            ->getRepository(News::class)
            ->findAll();

        return $this->render('news/listByCategory.html.twig', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    /**
     * @Route("/search", name="news_search")
     */
    public function handleSearchFormAction(Request $request)
    {
        $form = $this->createFormBuilder(null, array(
                'csrf_protection' => false,
            ))
            ->setAction($this->generateUrl('news_search'))
            ->setMethod('POST')
            ->add('q', TextType::class)
            ->add('search', ButtonType::class, array('label' => 'Search'))
            ->getForm();

        $form->handleRequest($request);
        
        if ( !$form->isSubmitted() && empty($request->query->get('q')) ) {
            return $this->render('news/formSearch.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $q = $form->getData()['q'];
        if( !empty($q) ) {
            return $this->redirectToRoute('news_search', array('q' => $q));
        }

        $query = $this->getDoctrine()
            ->getRepository(News::class)
            ->createQueryBuilder('a')
            ->where('a.name LIKE :q')
            ->setParameter('q', '%'.$request->query->get('q').'%')
            ->getQuery();

        // Init breadcrum for the post
        $breadcrumbs = $this->buildBreadcrums(null, null, null);
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query->getResult(),
            1,
            10
        );

        return $this->render('news/search.html.twig', [
            'q' => ucfirst($request->query->get('q')),
            'pagination' => $pagination
        ]);
    }

    /**
     * Render form comment
     * @return Form
     **/
    private function renderFormComment($post)
    {
        $comment = new Comments();
        $comment->setIp( $this->container->get('request_stack')->getCurrentRequest()->getClientIp() );
        $comment->setNewsId( $post->getId() );

        $form = $this->createFormBuilder($comment)
            ->setAction($this->generateUrl('handle_comment_form'))
            ->add('contents', TextareaType::class)
            ->add('author', TextType::class)
            ->add('email', EmailType::class)
            ->add('ip', HiddenType::class)
            ->add('news_id', HiddenType::class)
            ->add('send', SubmitType::class, array('label' => 'Send'))
            ->getForm();

        return $form;
    }

    /**
     * Handle form comment
     * @Route("/comment", name="handle_comment_form")
     **/
    public function handleCommentFormAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return new Response(
                json_encode(
                    array(
                        'status'=>'fail',
                        'message' => 'You can access this only using Ajax!'
                    )
                )
            );
        } else {
            $em = $this->getDoctrine()->getManager();
            
            $comment = new Comments();
            $comment->setContents( $request->request->get('form')['contents'] );
            $comment->setAuthor( $request->request->get('form')['author'] );
            $comment->setEmail( $request->request->get('form')['email'] );
            $comment->setNewsId( $request->request->get('form')['news_id'] );
            $comment->setIp( $request->request->get('form')['ip'] );

            $em->persist($comment);
            $em->flush();
            
            if( null != $comment->getId() ) {
                return new Response(
                    json_encode(
                        array(
                            'status'=>'success',
                            'message' => 'Thank for your comment. We will review your comment before display on this page'
                        )
                    )
                );
            } else {
                return new Response(
                    json_encode(
                        array(
                            'status'=>'fail',
                            'message' => 'Have a problem on your comment. Please try again'
                        )
                    )
                );
            }
        }
    }

    /**
     * Handle the breadcrumb
     * @return Breadcrums
     **/
    public function buildBreadcrums($category = null, $post = null, $page = null)
    {
        // Init october breadcrum
        $breadcrumbs = $this->get("white_october_breadcrumbs");
        
        // Add home item into first breadcrum.
        $breadcrumbs->addItem("Home", $this->generateUrl("homepage"));
        
        // Breadcrum for category page
        if( !empty($category) ) {
            if( $category->getParentcat() == NULL) {
                $breadcrumbs->addItem($category->getName(), $this->generateUrl("news_category", array('level1' => $category->getUrl() )));
            } else {
                $breadcrumbs->addItem($category->getParentcat()->getName(), $this->generateUrl("news_category", array('level1' => $category->getParentcat()->getUrl() )));

                $breadcrumbs->addItem($category->getName(), $this->generateUrl("list_category", array('level1' => $category->getParentcat()->getUrl(), 'level2' => $category->getUrl() )));
            }
        }

        // Breadcrum for post page
        if ( !empty($post) ) {
            $category = $post->getCategory();

            if ( !empty($category) ) {
                if ($category->getParentcat() == NULL) {
                    $breadcrumbs->addItem($category->getName(), $this->generateUrl("news_category", array('level1' => $category->getUrl() )));

                    $breadcrumbs->addItem($post->getName());
                } else {
                    $parentCategory = $category->getParentcat();
                    
                    $breadcrumbs->addItem($parentCategory->getName(), $this->generateUrl("news_category", array('level1' => $parentCategory->getUrl() )));
                    
                    $breadcrumbs->addItem($category->getName(), $this->generateUrl("list_category", array('level1' => $parentCategory->getUrl(), 'level2' => $category->getUrl() )));
                    
                    $breadcrumbs->addItem($post->getName());
                }
            } else {
                $breadcrumbs->addItem($post->getName());
            }
        }

        // Breadcrum for page page
        if ( !empty($page) ) {
            // Add page item into first breadcrum.
            $breadcrumbs->addItem($page->getName());
        }

        return $breadcrumbs;
    }
}