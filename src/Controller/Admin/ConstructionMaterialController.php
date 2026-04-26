<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\ConstructionMaterial;
use App\Form\ConstructionMaterialType;
use App\Service\ActivityLogService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/construction-material")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ConstructionMaterialController extends Controller
{
    /**
     * @Route("/", name="admin_construction_material_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $objects = $this->getDoctrine()->getRepository(ConstructionMaterial::class)
            ->findBy(array(), array('category' => 'ASC', 'name' => 'ASC'));

        return $this->render('admin/construction_material/index.html.twig', array(
            'objects' => $objects,
        ));
    }

    /**
     * @Route("/new", name="admin_construction_material_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $object = new ConstructionMaterial();
        $form = $this->createForm(ConstructionMaterialType::class, $object);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($object);
            $em->flush();

            $this->get(ActivityLogService::class)->log(
                ActivityLog::ACTION_CREATE,
                ActivityLog::ENTITY_CONSTRUCTION_MATERIAL,
                $object->getId(),
                $object->getName()
            );

            $this->addFlash('success', 'Đã tạo vật tư mới.');

            return $this->redirectToRoute('admin_construction_material_index');
        }

        return $this->render('admin/construction_material/new.html.twig', array(
            'object' => $object,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/edit", name="admin_construction_material_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ConstructionMaterial $object)
    {
        $form = $this->createForm(ConstructionMaterialType::class, $object);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $diffDetails = $this->get(ActivityLogService::class)->getEntityDiff($object);

            $this->getDoctrine()->getManager()->flush();

            $this->get(ActivityLogService::class)->log(
                ActivityLog::ACTION_UPDATE,
                ActivityLog::ENTITY_CONSTRUCTION_MATERIAL,
                $object->getId(),
                $object->getName(),
                $diffDetails
            );

            $this->addFlash('success', 'Đã cập nhật vật tư.');

            return $this->redirectToRoute('admin_construction_material_index');
        }

        return $this->render('admin/construction_material/edit.html.twig', array(
            'object' => $object,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/delete", name="admin_construction_material_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, ConstructionMaterial $object)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_construction_material_index');
        }

        $name = $object->getName();
        $id = $object->getId();

        $em = $this->getDoctrine()->getManager();
        $em->remove($object);
        $em->flush();

        $this->get(ActivityLogService::class)->log(
            ActivityLog::ACTION_DELETE,
            ActivityLog::ENTITY_CONSTRUCTION_MATERIAL,
            $id,
            $name
        );

        $this->addFlash('success', 'Đã xóa vật tư.');

        return $this->redirectToRoute('admin_construction_material_index');
    }
}
