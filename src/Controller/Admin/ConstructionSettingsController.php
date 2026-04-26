<?php

namespace App\Controller\Admin;

use App\Form\ConstructionSettingsType;
use App\Service\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin/settings/construction")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ConstructionSettingsController extends Controller
{
    /**
     * @Route("/", name="admin_settings_construction")
     */
    public function index(Request $request, SettingsManager $settingsManager): Response
    {
        $settings = $settingsManager->all();

        $form = $this->createForm(ConstructionSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $settingsManager->setMany($data);

            $this->addFlash('success', 'Cài đặt giá vốn đã được cập nhật.');

            return $this->redirectToRoute('admin_settings_construction');
        }

        return $this->render('admin/construction_settings/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
