<?php

namespace App\Controller;

use App\Service\ActivityLogService;
use App\Service\PaymentStatusService;
use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(
        ActivityLogService $activityLogService,
        StatisticsService $statisticsService,
        PaymentStatusService $paymentStatusService,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Pobierz statystyki z procentowymi zmianami
        $stats = $statisticsService->getStatsWithChanges($user);

        // Pobierz ostatnie aktywności użytkownika w jego zakresie
        $recentActivities = $activityLogService->getRecentActivitiesInScope($user, 5);

        // Pobierz status systemu
        $systemStatus = $statisticsService->getSystemStatus();

        // Pobierz status płatności użytkownika
        $paymentStatus = null;
        if ($this->isGranted('ROLE_CZLONEK_PARTII') || $this->isGranted('ROLE_KANDYDAT_PARTII')) {
            $paymentStatus = $paymentStatusService->getPaymentStatus($user);
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'user' => $user,
            'recent_activities' => $recentActivities,
            'system_status' => $systemStatus,
            'payment_status' => $paymentStatus,
        ]);
    }
}
