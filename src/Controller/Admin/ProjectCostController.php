<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\ConstructionMaterial;
use App\Entity\ConstructionProject;
use App\Form\ConstructionProjectType;
use App\Service\ActivityLogService;
use App\Service\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/project-costing")
 * @Security("has_role('ROLE_ADMIN')")
 */
class ProjectCostController extends Controller
{
    /**
     * @Route("/", name="admin_project_costing_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $projects = $this->getDoctrine()->getRepository(ConstructionProject::class)->findRecentProjects();

        return $this->render('admin/project_costing/index.html.twig', array(
            'projects' => $projects,
        ));
    }

    /**
     * @Route("/new", name="admin_project_costing_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $project = new ConstructionProject();
        $project->setProjectName('Nhà phố Thạnh Xuân');
        $project->setType(1);
        $project->setFinishLevel(2);
        $project->setContractValue(2850000000);
        $project->setTargetMargin(18);
        $project->setWide(4.5);
        $project->setLong(18);
        $project->setFloor(4);
        $project->setBasement(0);
        $project->setMong(2);
        $project->setMai(1);
        $project->setAlley(2);
        $project->setSubcontract(2);
        $project->setStatus('draft');

        return $this->handleProjectForm($request, $project, true);
    }

    /**
     * @Route("/{id}/edit", name="admin_project_costing_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ConstructionProject $project)
    {
        return $this->handleProjectForm($request, $project, false);
    }

    /**
     * @Route("/{id}/delete", name="admin_project_costing_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, ConstructionProject $project)
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_project_costing_index');
        }

        $projectId = $project->getId();
        $projectName = $project->getProjectName();

        $em = $this->getDoctrine()->getManager();
        $em->remove($project);
        $em->flush();

        $this->get(ActivityLogService::class)->log(
            ActivityLog::ACTION_DELETE,
            ActivityLog::ENTITY_CONSTRUCTION_PROJECT,
            $projectId,
            $projectName
        );

        $this->addFlash('success', 'Đã xóa dự án.');

        return $this->redirectToRoute('admin_project_costing_index');
    }

    private function handleProjectForm(Request $request, ConstructionProject $project, $isNew)
    {
        $form = $this->createForm(ConstructionProjectType::class, $project);
        $form->handleRequest($request);

        $materialPriceMap = $this->getDoctrine()->getRepository(ConstructionMaterial::class)->getPriceMap();
        $settingsManager = $this->get('settings_manager');
        $scenario = $this->buildCostScenario($project, $materialPriceMap, $settingsManager);
        $errorMessage = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $scenario = $this->buildCostScenario($project, $materialPriceMap, $settingsManager);

            if ($scenario['error']) {
                $errorMessage = $scenario['error'];
            } elseif ($form->get('saveProject')->isClicked()) {
                $em = $this->getDoctrine()->getManager();
                $isNewRecord = $project->getId() === null;
                $diffDetails = $isNewRecord ? null : $this->get(ActivityLogService::class)->getEntityDiff($project);

                if ($isNewRecord) {
                    $em->persist($project);
                }
                $em->flush();

                $this->get(ActivityLogService::class)->log(
                    $isNewRecord ? ActivityLog::ACTION_CREATE : ActivityLog::ACTION_UPDATE,
                    ActivityLog::ENTITY_CONSTRUCTION_PROJECT,
                    $project->getId(),
                    $project->getProjectName(),
                    $diffDetails
                );

                $this->addFlash('success', $isNewRecord ? 'Đã lưu dự án mới.' : 'Đã cập nhật dự án.');

                return $this->redirectToRoute('admin_project_costing_edit', array(
                    'id' => $project->getId()
                ));
            }
        }

        $projects = $this->getDoctrine()->getRepository(ConstructionProject::class)->findRecentProjects();

        return $this->render('admin/project_costing/manage.html.twig', array(
            'form' => $form->createView(),
            'scenario' => $scenario,
            'projects' => $projects,
            'object' => $project,
            'errorMessage' => $errorMessage,
            'is_new' => $isNew,
        ));
    }

    private function buildCostScenario(ConstructionProject $project, array $materialPriceMap = array(), SettingsManager $settingsManager = null)
    {
        $wide = (float) $project->getWide();
        $long = (float) $project->getLong();
        $contractValue = (float) $project->getContractValue();

        $type = (int) $project->getType();
        $finishLevel = (int) $project->getFinishLevel();
        $targetMargin = (int) $project->getTargetMargin();
        $floor = (int) $project->getFloor();
        $basement = (int) $project->getBasement();
        $mong = (int) $project->getMong();
        $mai = (int) $project->getMai();
        $alley = (int) $project->getAlley();
        $subcontract = (int) $project->getSubcontract();

        $error = null;
        if ($wide <= 0 || $long <= 0) {
            $error = 'Vui lòng nhập đúng chiều rộng và chiều dài công trình.';
        }

        $typeLabels = array(1 => 'Nhà phố', 2 => 'Biệt thự', 3 => 'Nhà cấp 4');
        $finishLabels = array(1 => 'Gói cơ bản', 2 => 'Gói tiêu chuẩn', 3 => 'Gói cao cấp');
        $foundationLabels = array(1 => 'Móng cọc', 2 => 'Móng băng', 3 => 'Móng đơn', 4 => 'Móng bè');
        $roofLabels = array(1 => 'Mái BTCT đúc bằng', 2 => 'Mái tôn lạnh', 3 => 'Mái ngói kèo thép', 4 => 'Mái BTCT lợp ngói');
        $alleyLabels = array(1 => 'Mặt tiền xe tải', 2 => 'Hẻm 3m - 5m', 3 => 'Hẻm dưới 3m');
        $subcontractLabels = array(1 => 'Tự quản lý tổ đội', 2 => 'Khoán hỗn hợp', 3 => 'Khoán thầu phụ nhiều hạng mục');
        $basementLabels = array(0 => 'Không có tầng hầm', 1 => 'Bán hầm', 2 => '1 tầng hầm');
        $statusLabels = array(
            'draft' => 'Nháp',
            'estimating' => 'Đang báo giá',
            'active' => 'Đang thi công',
            'completed' => 'Hoàn thành',
            'paused' => 'Tạm dừng',
        );

        $foundationCoefficients = array(
            1 => $settingsManager ? $settingsManager->get('foundation_c_1', 0.50) : 0.50,
            2 => $settingsManager ? $settingsManager->get('foundation_c_2', 0.55) : 0.55,
            3 => $settingsManager ? $settingsManager->get('foundation_c_3', 0.30) : 0.30,
            4 => $settingsManager ? $settingsManager->get('foundation_c_4', 0.90) : 0.90
        );
        $roofCoefficients = array(
            1 => $settingsManager ? $settingsManager->get('roof_c_1', 0.70) : 0.70,
            2 => $settingsManager ? $settingsManager->get('roof_c_2', 0.30) : 0.30,
            3 => $settingsManager ? $settingsManager->get('roof_c_3', 0.70) : 0.70,
            4 => $settingsManager ? $settingsManager->get('roof_c_4', 1.00) : 1.00
        );
        $basementCoefficients = array(
            0 => $settingsManager ? $settingsManager->get('basement_c_0', 0.00) : 0.00,
            1 => $settingsManager ? $settingsManager->get('basement_c_1', 0.65) : 0.65,
            2 => $settingsManager ? $settingsManager->get('basement_c_2', 1.40) : 1.40
        );
        $alleyFactors = array(1 => 1.00, 2 => 1.03, 3 => 1.08);
        $subcontractFactors = array(1 => 1.00, 2 => 1.025, 3 => 1.055);
        $typeCostFactors = array(1 => 1.00, 2 => 1.07, 3 => 0.95);
        $finishFactors = array(1 => 0.96, 2 => 1.00, 3 => 1.08);
        $progressByStatus = array(
            'draft' => 0.18,
            'estimating' => 0.28,
            'active' => min(0.96, 0.34 + ($floor * 0.08)),
            'completed' => 1.00,
            'paused' => 0.42,
        );

        $saleRates = array(
            1 => array(
                1 => $settingsManager ? $settingsManager->get('sale_rate_1_1', 4800000) : 4800000,
                2 => $settingsManager ? $settingsManager->get('sale_rate_1_2', 5600000) : 5600000,
                3 => $settingsManager ? $settingsManager->get('sale_rate_1_3', 6700000) : 6700000,
            ),
            2 => array(
                1 => $settingsManager ? $settingsManager->get('sale_rate_2_1', 5600000) : 5600000,
                2 => $settingsManager ? $settingsManager->get('sale_rate_2_2', 6500000) : 6500000,
                3 => $settingsManager ? $settingsManager->get('sale_rate_2_3', 7900000) : 7900000,
            ),
            3 => array(
                1 => $settingsManager ? $settingsManager->get('sale_rate_3_1', 4100000) : 4100000,
                2 => $settingsManager ? $settingsManager->get('sale_rate_3_2', 4900000) : 4900000,
                3 => $settingsManager ? $settingsManager->get('sale_rate_3_3', 5800000) : 5800000,
            ),
        );

        $costStructures = array(
            1 => array(
                array('code' => '01.01', 'label' => 'Móng và tầng hầm', 'share' => 0.14, 'phase' => 1.00),
                array('code' => '01.02', 'label' => 'Khung bê tông cốt thép', 'share' => 0.28, 'phase' => 0.90),
                array('code' => '02.01', 'label' => 'Xây tô và chống thấm', 'share' => 0.14, 'phase' => 0.68),
                array('code' => '03.01', 'label' => 'MEP cơ bản', 'share' => 0.08, 'phase' => 0.52),
                array('code' => '04.01', 'label' => 'Hoàn thiện', 'share' => 0.16, 'phase' => 0.38),
                array('code' => '05.01', 'label' => 'Tổ đội và thầu phụ', 'share' => 0.12, 'phase' => 0.58),
                array('code' => '06.01', 'label' => 'Chi phí chung và dự phòng', 'share' => 0.08, 'phase' => 0.32),
            ),
            2 => array(
                array('code' => '01.01', 'label' => 'Móng và tầng hầm', 'share' => 0.13, 'phase' => 1.00),
                array('code' => '01.02', 'label' => 'Khung bê tông cốt thép', 'share' => 0.25, 'phase' => 0.92),
                array('code' => '02.01', 'label' => 'Xây tô và chống thấm', 'share' => 0.13, 'phase' => 0.72),
                array('code' => '03.01', 'label' => 'MEP tiêu chuẩn', 'share' => 0.10, 'phase' => 0.58),
                array('code' => '04.01', 'label' => 'Hoàn thiện', 'share' => 0.21, 'phase' => 0.46),
                array('code' => '05.01', 'label' => 'Tổ đội và thầu phụ', 'share' => 0.10, 'phase' => 0.60),
                array('code' => '06.01', 'label' => 'Chi phí chung và dự phòng', 'share' => 0.08, 'phase' => 0.36),
            ),
            3 => array(
                array('code' => '01.01', 'label' => 'Móng và tầng hầm', 'share' => 0.12, 'phase' => 1.00),
                array('code' => '01.02', 'label' => 'Khung bê tông cốt thép', 'share' => 0.23, 'phase' => 0.92),
                array('code' => '02.01', 'label' => 'Xây tô và chống thấm', 'share' => 0.12, 'phase' => 0.74),
                array('code' => '03.01', 'label' => 'MEP cao cấp', 'share' => 0.12, 'phase' => 0.60),
                array('code' => '04.01', 'label' => 'Hoàn thiện', 'share' => 0.24, 'phase' => 0.54),
                array('code' => '05.01', 'label' => 'Tổ đội và thầu phụ', 'share' => 0.09, 'phase' => 0.63),
                array('code' => '06.01', 'label' => 'Chi phí chung và dự phòng', 'share' => 0.08, 'phase' => 0.40),
            ),
        );

        $footprintArea = round($wide * $long, 2);
        $foundationArea = round($footprintArea * $foundationCoefficients[$mong], 2);
        $roofArea = round($footprintArea * $roofCoefficients[$mai], 2);
        $basementArea = round($footprintArea * $basementCoefficients[$basement], 2);
        $constructedArea = round(($footprintArea * $floor) + $foundationArea + $roofArea + $basementArea, 2);

        $saleRate = $saleRates[$type][$finishLevel];
        $benchmarkContractValue = $saleRate * $constructedArea;
        if ($contractValue <= 0) {
            $contractValue = round($benchmarkContractValue);
        }

        $progressRatio = isset($progressByStatus[$project->getStatus()]) ? $progressByStatus[$project->getStatus()] : 0.28;
        $targetBudget = round($contractValue * (1 - ($targetMargin / 100)));
        $riskFactor = $alleyFactors[$alley] * $subcontractFactors[$subcontract] * $typeCostFactors[$type] * $finishFactors[$finishLevel];
        if ($basement === 1) {
            $riskFactor = $riskFactor * 1.04;
        } elseif ($basement === 2) {
            $riskFactor = $riskFactor * 1.08;
        }

        $forecastCost = round($targetBudget * $riskFactor);
        $committedCost = round($forecastCost * min(0.96, $progressRatio + 0.19));
        $actualCost = round($forecastCost * max(0.12, $progressRatio - 0.08));
        $projectedProfit = $contractValue - $forecastCost;
        $projectedMargin = $contractValue > 0 ? round(($projectedProfit / $contractValue) * 100, 1) : 0;

        $costGroups = array();
        foreach ($costStructures[$finishLevel] as $group) {
            $budget = round($targetBudget * $group['share']);
            $forecast = round($forecastCost * $group['share']);
            $committed = round($forecast * min(1.02, ($progressRatio + 0.16) * $group['phase']));
            $actual = round($forecast * max(0.08, ($progressRatio - 0.06) * $group['phase']));
            $variance = $budget - $forecast;
            $status = 'Trong tầm kiểm soát';

            if ($forecast > $budget * 1.06) {
                $status = 'Có nguy cơ vượt';
            }
            if ($forecast > $budget * 1.12) {
                $status = 'Vượt mạnh';
            }

            $costGroups[] = array(
                'code' => $group['code'],
                'label' => $group['label'],
                'share' => round($group['share'] * 100, 1),
                'budget' => $budget,
                'committed' => $committed,
                'actual' => $actual,
                'forecast' => $forecast,
                'variance' => $variance,
                'status' => $status,
            );
        }

        $structuralConcrete = round(($foundationArea + ($footprintArea * $floor)) * 0.22, 2);
        $steelWeight = round($structuralConcrete * ($type === 2 ? 118 : 108), 0);
        $brickPieces = round((($footprintArea * $floor * 3.2) * ($type === 3 ? 0.72 : 0.88)) * 55, 0);
        $plasterSand = round(($footprintArea * $floor * 3.2) * 0.035, 2);
        $paintArea = round(($footprintArea * $floor * 3.2) * 2.1, 0);
        $tileArea = round(($footprintArea * $floor) * ($finishLevel === 1 ? 0.78 : 0.92), 0);
        $cableLength = round($constructedArea * ($finishLevel === 3 ? 8.8 : 7.2), 0);
        $pipeLength = round($constructedArea * ($type === 2 ? 1.8 : 1.35), 0);

        $materials = array(
            $this->buildMaterialLine('BETONG', 'Kết cấu', 'Bê tông thương phẩm', 'm3', $structuralConcrete, 1450000, 'Khối lượng kết cấu cho móng, cột, dầm, sàn', 'Mô hình theo hệ số 0.22 m3/m2 kết cấu', $materialPriceMap),
            $this->buildMaterialLine('THEP', 'Kết cấu', 'Thép xây dựng', 'kg', $steelWeight, 16800, 'Suất thép theo loại công trình', 'Mô hình suất thép 108-118 kg/m3 bê tông', $materialPriceMap),
            $this->buildMaterialLine('GACH', 'Xây tô', 'Gạch xây', 'vien', $brickPieces, 1250, 'Tường bao và tường ngăn', 'Ước lượng từ diện tích tường x 55 viên/m2', $materialPriceMap),
            $this->buildMaterialLine('CAT', 'Xây tô', 'Cát xây tô', 'm3', $plasterSand, 410000, 'Vữa xây tô và bù hao hụt', 'Mô hình 0.035 m3/m2 diện tích tường', $materialPriceMap),
            $this->buildMaterialLine('SON', 'Hoàn thiện', 'Sơn nước', 'm2', $paintArea, 72000, 'Bả + lót + phủ', 'Ước lượng từ diện tích hoàn thiện nội ngoại thất', $materialPriceMap),
            $this->buildMaterialLine('GACHLAT', 'Hoàn thiện', 'Gạch hoàn thiện sàn', 'm2', $tileArea, $finishLevel === 3 ? 315000 : 225000, 'Khu vực khô và ướt', 'Tỷ lệ hoàn thiện thay đổi theo gói bàn giao', $materialPriceMap),
            $this->buildMaterialLine('DAYDIEN', 'MEP', 'Dây điện tổng hợp', 'm', $cableLength, 24500, 'Điện chiếu sáng và ổ cắm', 'Suất dây theo diện tích xây dựng quy đổi', $materialPriceMap),
            $this->buildMaterialLine('ONGNUOC', 'MEP', 'Ống nước và phụ kiện', 'm', $pipeLength, 69000, 'Cấp thoát nước âm tường', 'Suất ống theo loại công trình', $materialPriceMap),
        );

        $cashFlow = array(
            array('label' => 'Ký hợp đồng - tạm ứng', 'percent' => 15, 'value' => round($contractValue * 0.15)),
            array('label' => 'Hoàn tất móng và hầm', 'percent' => 20, 'value' => round($contractValue * 0.20)),
            array('label' => 'Hoàn tất kết cấu', 'percent' => 28, 'value' => round($contractValue * 0.28)),
            array('label' => 'MEP và hoàn thiện', 'percent' => 25, 'value' => round($contractValue * 0.25)),
            array('label' => 'Bàn giao và quyết toán', 'percent' => 12, 'value' => round($contractValue * 0.12)),
        );

        $alerts = array();
        if ($projectedMargin < $targetMargin) {
            $alerts[] = array(
                'tone' => 'danger',
                'title' => 'Biên lợi nhuận đang thấp hơn mục tiêu',
                'text' => 'Forecast hiện tại thấp hơn mục tiêu, cần rà lại giá tổ đội và gói hoàn thiện.'
            );
        } else {
            $alerts[] = array(
                'tone' => 'success',
                'title' => 'Biên lợi nhuận vẫn giữ được vùng an toàn',
                'text' => 'Dự án còn khoảng đệm lợi nhuận, có thể dùng làm baseline để theo dõi mua hàng.'
            );
        }

        if ($alley === 3 || $basement === 2) {
            $alerts[] = array(
                'tone' => 'warning',
                'title' => 'Rủi ro logistic và thi công nền cao',
                'text' => 'Cần theo dõi riêng chi phí vận chuyển, ép cọc, chống thấm và phát sinh hiện trường.'
            );
        }

        return array(
            'error' => $error,
            'projectName' => $project->getProjectName(),
            'projectStatus' => isset($statusLabels[$project->getStatus()]) ? $statusLabels[$project->getStatus()] : $project->getStatus(),
            'typeLabel' => $typeLabels[$type],
            'finishLabel' => $finishLabels[$finishLevel],
            'foundationLabel' => $foundationLabels[$mong],
            'roofLabel' => $roofLabels[$mai],
            'alleyLabel' => $alleyLabels[$alley],
            'subcontractLabel' => $subcontractLabels[$subcontract],
            'basementLabel' => $basementLabels[$basement],
            'footprintArea' => $footprintArea,
            'foundationArea' => $foundationArea,
            'roofArea' => $roofArea,
            'basementArea' => $basementArea,
            'constructedArea' => $constructedArea,
            'saleRate' => $saleRate,
            'benchmarkContractValue' => round($benchmarkContractValue),
            'contractValue' => round($contractValue),
            'targetBudget' => $targetBudget,
            'forecastCost' => $forecastCost,
            'committedCost' => $committedCost,
            'actualCost' => $actualCost,
            'projectedProfit' => $projectedProfit,
            'projectedMargin' => $projectedMargin,
            'targetMargin' => $targetMargin,
            'riskFactor' => round($riskFactor, 3),
            'progressPercent' => round($progressRatio * 100),
            'costGroups' => $costGroups,
            'materials' => $materials,
            'cashFlow' => $cashFlow,
            'alerts' => $alerts,
            'baselineDisclaimer' => 'Bảng baseline đang là mô hình kiểm soát nội bộ để so budget, committed, actual và forecast. Nó chưa thay thế bảng bóc tách thi công hoặc BOQ mua hàng cuối cùng.',
            'linkedMaterials' => array_values(array_filter($materials, function ($item) {
                return !$item['isFallback'];
            })),
        );
    }

    private function buildMaterialLine($code, $fallbackGroup, $fallbackName, $fallbackUnit, $quantity, $fallbackRate, $fallbackNote, $formulaNote, array $materialPriceMap)
    {
        $config = isset($materialPriceMap[$code]) ? $materialPriceMap[$code] : array();
        $rate = isset($config['unitPrice']) ? (float) $config['unitPrice'] : (float) $fallbackRate;
        $wastagePercent = isset($config['wastagePercent']) ? (float) $config['wastagePercent'] : 0;
        $adjustedQuantity = round($quantity * (1 + ($wastagePercent / 100)), 2);

        return array(
            'code' => $code,
            'group' => isset($config['category']) ? $config['category'] : $fallbackGroup,
            'name' => isset($config['name']) ? $config['name'] : $fallbackName,
            'unit' => isset($config['unit']) ? $config['unit'] : $fallbackUnit,
            'quantity' => $adjustedQuantity,
            'baseQuantity' => $quantity,
            'rate' => $rate,
            'cost' => round($adjustedQuantity * $rate),
            'note' => isset($config['note']) && $config['note'] ? $config['note'] : $fallbackNote,
            'formulaNote' => $formulaNote,
            'wastagePercent' => $wastagePercent,
            'isFallback' => !isset($materialPriceMap[$code]),
        );
    }
}
