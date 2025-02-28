<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\News;

class HomepageController extends Controller
{
    public function indexAction(Request $request)
    {
        $listPrices = $this->get('settings_manager')->get('listPrices');
        $listCategoriesOnHomepage = $this->get('settings_manager')->get('listCategoryOnHomepage');
        $blockPricesOnHomepage = array();
        $blocksOnHomepage = array();

        if (!empty($listPrices)) {
            $listPricesArray = explode(',', $listPrices);
            if (is_array($listPricesArray) && count($listPricesArray) > 0) {
                
                for ($i = 0; $i < count($listPricesArray); $i++) {
                    $post = $this->getDoctrine()
                                ->getRepository(News::class)
                                ->find($listPricesArray[$i]);
                    if ($post) {
                        $blockPricesOnHomepage[] = $post;
                    }
                }
            }
        }

        if (!empty($listCategoriesOnHomepage)) {
            $listCategoriesOnHomepage = json_decode($listCategoriesOnHomepage, true);

            if (is_array($listCategoriesOnHomepage)) {
                for ($i = 0; $i < count($listCategoriesOnHomepage); $i++) {
                    $blockOnHomepage = [];
                    $category = $this->getDoctrine()
                                    ->getRepository(NewsCategory::class)
                                    ->find($listCategoriesOnHomepage[$i]["id"]);

                    if ($category) {
                        $listCategoriesIds = array($category->getId());
                        $listSubIds = explode(",", $listCategoriesOnHomepage[$i]["subId"]);
                        $listSubTabs = [];

                        if ($listCategoriesOnHomepage[$i]["subId"] != null && count($listSubIds) > 0) {
                            for ($j = 0; $j < count($listSubIds); $j++) {
                                $subCat = $this->getDoctrine()
                                        ->getRepository(NewsCategory::class)
                                        ->find($listSubIds[$j]);

                                if ($subCat) {
                                    $posts = $this->getDoctrine()
                                        ->getRepository(News::class)
                                        ->createQueryBuilder('n')
                                        ->leftJoin('n.category', 't')
                                        ->where('t.id =:subCat')
                                        ->andWhere('n.enable = :enable')
                                        ->setParameter('subCat', $subCat->getId())
                                        ->setParameter('enable', 1)
                                        ->orderBy('n.createdAt', 'DESC')
                                        ->setMaxResults( $listCategoriesOnHomepage[$i]["items"] )
                                        ->getQuery()->getResult();
                                }

                                $listSubTabs[] = (object) array('subCategory' => $subCat, 'posts' => $posts);
                            }
                        } else {
                            $posts = $this->getDoctrine()
                                ->getRepository(News::class)
                                ->createQueryBuilder('n')
                                ->leftJoin('n.category', 't')
                                ->where('t.id =:subCat')
                                ->andWhere('n.enable = :enable')
                                ->setParameter('subCat', $category->getId())
                                ->setParameter('enable', 1)
                                ->orderBy('n.createdAt', 'DESC')
                                ->setMaxResults( $listCategoriesOnHomepage[$i]["items"] )
                                ->getQuery()->getResult();
                        }
                    }

                    $blockOnHomepage = (object) array('category' => $category, 'listSubTabs' => $listSubTabs, 'posts' => $posts, 'description' => $listCategoriesOnHomepage[$i]["description"], 'title' => $listCategoriesOnHomepage[$i]["title"]);
                    $blocksOnHomepage[] = $blockOnHomepage;
                }
            }
        }

        return $this->render('homepage/index.html.twig', [
            'blocksOnHomepage' => $blocksOnHomepage,
            'blockPricesOnHomepage' => $blockPricesOnHomepage,
            'showSlide' => true
        ]);
    }
}
