<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Redirect;
use AppBundle\Form\RedirectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage redirects.
 *
 * @Route("/admin/redirect")
 * @Security("has_role('ROLE_ADMIN')")
 */
class RedirectController extends Controller
{
    /**
     * Lists all Redirect entities.
     *
     * @Route("/", name="admin_redirect_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->getRepository(Redirect::class)->createQueryBuilder('r')->orderBy('r.id', 'DESC');
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/redirect/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * Creates a new Redirect entity.
     *
     * @Route("/new", name="admin_redirect_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $redirect = new Redirect();
        $form = $this->createForm(RedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($redirect);
            $em->flush();

            $this->addFlash('success', 'action.created_successfully');

            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render('admin/redirect/new.html.twig', [
            'object' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Redirect entity.
     *
     * @Route("/{id}/edit", requirements={"id": "\d+"}, name="admin_redirect_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Redirect $redirect)
    {
        $form = $this->createForm(RedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'action.updated_successfully');
            
            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render('admin/redirect/edit.html.twig', [
            'object' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Deletes a Redirect entity.
     *
     * @Route("/{id}/delete", name="admin_redirect_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, Redirect $redirect)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_redirect_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($redirect);
        $em->flush();

        $this->addFlash('success', 'action.deleted_successfully');

        return $this->redirectToRoute('admin_redirect_index');
    }
}
