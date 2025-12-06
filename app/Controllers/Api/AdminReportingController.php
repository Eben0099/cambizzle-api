<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;

class AdminReportingController extends BaseApiController
{
    /**
     * Statistiques générales du dashboard
     */
    public function globalStats()
    {
        try {
            $db = \Config\Database::connect();

            // Statistiques des annonces
            $adsStats = $db->table('ads')
                ->select('
                    COUNT(*) as total_ads,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_ads,
                    SUM(CASE WHEN moderation_status = "pending" THEN 1 ELSE 0 END) as pending_moderation,
                    SUM(CASE WHEN moderation_status = "approved" THEN 1 ELSE 0 END) as approved_ads,
                    SUM(CASE WHEN moderation_status = "rejected" THEN 1 ELSE 0 END) as rejected_ads,
                    SUM(view_count) as total_views,
                    AVG(view_count) as avg_views_per_ad,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as ads_last_24h,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as ads_last_7d,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ads_last_30d
                ')
                ->get()
                ->getRowArray();

            // Statistiques des utilisateurs
            $usersStats = $db->table('users')
                ->select('
                    COUNT(*) as total_users,
                    SUM(CASE WHEN deleted IS NULL THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN is_suspended = 1 THEN 1 ELSE 0 END) as suspended_users,
                    SUM(CASE WHEN is_identity_verified = 1 THEN 1 ELSE 0 END) as verified_users,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as users_last_24h,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as users_last_7d,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as users_last_30d
                ')
                ->get()
                ->getRowArray();

            // Statistiques des messages
            $messagesStats = $db->table('messages')
                ->select('
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as messages_last_24h,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as messages_last_7d
                ')
                ->get()
                ->getRowArray();

            // Statistiques des signalements
            $reportsStats = $db->table('reports')
                ->select('
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_reports,
                    SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_reports,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as reports_last_24h,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as reports_last_7d
                ')
                ->get()
                ->getRowArray();

            // Statistiques financières (basique)
            $financialStats = $db->table('payments')
                ->select('
                    COUNT(*) as total_payments,
                    COALESCE(SUM(amount), 0) as total_revenue,
                    AVG(amount) as avg_payment,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END) as revenue_last_30d
                ')
                ->get()
                ->getRowArray();

            // Top catégories par nombre d'annonces
            $topCategories = $db->table('ads a')
                ->select('c.name, c.slug, COUNT(a.id) as ad_count')
                ->join('subcategories s', 'a.subcategory_id = s.id')
                ->join('categories c', 's.category_id = c.id')
                ->where('a.moderation_status', 'approved')
                ->where('a.status', 'active')
                ->groupBy('c.id')
                ->orderBy('ad_count', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();

            // Évolution des annonces par jour (7 derniers jours)
            $adsEvolution = $db->table('ads')
                ->select('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
                ->groupBy('DATE(created_at)')
                ->orderBy('date', 'ASC')
                ->get()
                ->getResultArray();

            // Évolution des utilisateurs par jour (7 derniers jours)
            $usersEvolution = $db->table('users')
                ->select('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
                ->where('deleted IS NULL')
                ->groupBy('DATE(created_at)')
                ->orderBy('date', 'ASC')
                ->get()
                ->getResultArray();

            // Répartition des annonces par statut de modération
            $moderationStatusDistribution = $db->table('ads')
                ->select('moderation_status, COUNT(*) as count')
                ->groupBy('moderation_status')
                ->get()
                ->getResultArray();

            // Taux de conversion (annonces vues vs contactées)
            $conversionRate = $db->table('ads a')
                ->select('
                    COUNT(DISTINCT a.id) as total_ads_with_views,
                    COUNT(DISTINCT CASE WHEN m.ad_id IS NOT NULL THEN a.id END) as ads_with_messages,
                    AVG(a.view_count) as avg_views
                ')
                ->join('messages m', 'a.id = m.ad_id', 'left')
                ->where('a.view_count > 0')
                ->get()
                ->getRowArray();

            return $this->success([
                'ads' => [
                    'total' => (int)$adsStats['total_ads'],
                    'active' => (int)$adsStats['active_ads'],
                    'pending_moderation' => (int)$adsStats['pending_moderation'],
                    'approved' => (int)$adsStats['approved_ads'],
                    'rejected' => (int)$adsStats['rejected_ads'],
                    'total_views' => (int)$adsStats['total_views'],
                    'avg_views_per_ad' => round((float)$adsStats['avg_views_per_ad'], 2),
                    'new_last_24h' => (int)$adsStats['ads_last_24h'],
                    'new_last_7d' => (int)$adsStats['ads_last_7d'],
                    'new_last_30d' => (int)$adsStats['ads_last_30d'],
                    'moderation_distribution' => $moderationStatusDistribution
                ],
                'users' => [
                    'total' => (int)$usersStats['total_users'],
                    'active' => (int)$usersStats['active_users'],
                    'suspended' => (int)$usersStats['suspended_users'],
                    'verified' => (int)$usersStats['verified_users'],
                    'new_last_24h' => (int)$usersStats['users_last_24h'],
                    'new_last_7d' => (int)$usersStats['users_last_7d'],
                    'new_last_30d' => (int)$usersStats['users_last_30d']
                ],
                'messages' => [
                    'total' => (int)$messagesStats['total_messages'],
                    'last_24h' => (int)$messagesStats['messages_last_24h'],
                    'last_7d' => (int)$messagesStats['messages_last_7d']
                ],
                'reports' => [
                    'total' => (int)$reportsStats['total_reports'],
                    'pending' => (int)$reportsStats['pending_reports'],
                    'resolved' => (int)$reportsStats['resolved_reports'],
                    'last_24h' => (int)$reportsStats['reports_last_24h'],
                    'last_7d' => (int)$reportsStats['reports_last_7d']
                ],
                'financial' => [
                    'total_payments' => (int)$financialStats['total_payments'],
                    'total_revenue' => round((float)$financialStats['total_revenue'], 2),
                    'avg_payment' => round((float)$financialStats['avg_payment'], 2),
                    'revenue_last_30d' => round((float)$financialStats['revenue_last_30d'], 2)
                ],
                'conversion' => [
                    'ads_with_views' => (int)$conversionRate['total_ads_with_views'],
                    'ads_with_messages' => (int)$conversionRate['ads_with_messages'],
                    'conversion_rate' => $conversionRate['total_ads_with_views'] > 0 ?
                        round(($conversionRate['ads_with_messages'] / $conversionRate['total_ads_with_views']) * 100, 2) : 0,
                    'avg_views_per_ad' => round((float)$conversionRate['avg_views'], 2)
                ],
                'top_categories' => $topCategories,
                'evolution' => [
                    'ads_last_7_days' => $adsEvolution,
                    'users_last_7_days' => $usersEvolution
                ]
            ], 'Statistiques globales récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Statistiques détaillées pour une période donnée
     */
    public function detailedStats()
    {
        try {
            $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');

            $db = \Config\Database::connect();

            // Statistiques par jour pour la période
            $dailyStats = $db->query("
                SELECT
                    DATE(d.date) as date,
                    COALESCE(a.ads_count, 0) as ads_created,
                    COALESCE(u.users_count, 0) as users_registered,
                    COALESCE(m.messages_count, 0) as messages_sent,
                    COALESCE(r.reports_count, 0) as reports_created,
                    COALESCE(p.payments_count, 0) as payments_made,
                    COALESCE(p.revenue, 0) as revenue
                FROM (
                    SELECT DATE(?) + INTERVAL (a.a + (10 * b.b) + (100 * c.c)) DAY as date
                    FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
                    CROSS JOIN (SELECT 0 as b UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
                    CROSS JOIN (SELECT 0 as c UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as c
                ) d
                LEFT JOIN (SELECT DATE(created_at) as date, COUNT(*) as ads_count FROM ads WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at)) a ON d.date = a.date
                LEFT JOIN (SELECT DATE(created_at) as date, COUNT(*) as users_count FROM users WHERE created_at BETWEEN ? AND ? AND deleted IS NULL GROUP BY DATE(created_at)) u ON d.date = u.date
                LEFT JOIN (SELECT DATE(created_at) as date, COUNT(*) as messages_count FROM messages WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at)) m ON d.date = m.date
                LEFT JOIN (SELECT DATE(created_at) as date, COUNT(*) as reports_count FROM reports WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at)) r ON d.date = r.date
                LEFT JOIN (SELECT DATE(created_at) as date, COUNT(*) as payments_count, SUM(amount) as revenue FROM payments WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at)) p ON d.date = p.date
                WHERE d.date BETWEEN ? AND ?
                ORDER BY d.date
            ", [$startDate, $startDate, $endDate . ' 23:59:59', $startDate, $endDate . ' 23:59:59', $startDate, $endDate . ' 23:59:59', $startDate, $endDate . ' 23:59:59', $startDate, $endDate . ' 23:59:59', $startDate, $endDate])
            ->getResultArray();

            // Statistiques par catégorie pour la période
            $categoryStats = $db->table('ads a')
                ->select('c.name as category_name, COUNT(a.id) as ads_count, AVG(a.view_count) as avg_views')
                ->join('subcategories s', 'a.subcategory_id = s.id')
                ->join('categories c', 's.category_id = c.id')
                ->where('a.created_at >=', $startDate . ' 00:00:00')
                ->where('a.created_at <=', $endDate . ' 23:59:59')
                ->where('a.moderation_status', 'approved')
                ->groupBy('c.id')
                ->orderBy('ads_count', 'DESC')
                ->get()
                ->getResultArray();

            // Statistiques par région
            $locationStats = $db->table('ads a')
                ->select('l.city, l.region, COUNT(a.id) as ads_count')
                ->join('locations l', 'a.location_id = l.id')
                ->where('a.created_at >=', $startDate . ' 00:00:00')
                ->where('a.created_at <=', $endDate . ' 23:59:59')
                ->where('a.moderation_status', 'approved')
                ->groupBy('l.id')
                ->orderBy('ads_count', 'DESC')
                ->limit(20)
                ->get()
                ->getResultArray();

            return $this->success([
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'daily_stats' => $dailyStats,
                'category_stats' => $categoryStats,
                'location_stats' => $locationStats
            ], 'Statistiques détaillées récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Export des données pour reporting externe
     */
    public function exportData()
    {
        try {
            $type = $this->request->getGet('type') ?? 'ads'; // ads, users, messages, reports
            $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');
            $format = $this->request->getGet('format') ?? 'json'; // json, csv

            $db = \Config\Database::connect();

            $data = [];
            $filename = "cambizzle_{$type}_{$startDate}_{$endDate}";

            switch ($type) {
                case 'ads':
                    $data = $db->table('ads a')
                        ->select('a.*, c.name as category_name, s.name as subcategory_name, l.city, l.region, u.first_name, u.last_name, u.email')
                        ->join('subcategories s', 'a.subcategory_id = s.id')
                        ->join('categories c', 's.category_id = c.id')
                        ->join('locations l', 'a.location_id = l.id')
                        ->join('users u', 'a.user_id = u.id_user')
                        ->where('a.created_at >=', $startDate . ' 00:00:00')
                        ->where('a.created_at <=', $endDate . ' 23:59:59')
                        ->orderBy('a.created_at', 'DESC')
                        ->get()
                        ->getResultArray();
                    break;

                case 'users':
                    $data = $db->table('users')
                        ->select('*')
                        ->where('created_at >=', $startDate . ' 00:00:00')
                        ->where('created_at <=', $endDate . ' 23:59:59')
                        ->orderBy('created_at', 'DESC')
                        ->get()
                        ->getResultArray();
                    break;

                case 'messages':
                    $data = $db->table('messages m')
                        ->select('m.*, a.title as ad_title, u.first_name, u.last_name, u.email')
                        ->join('ads a', 'm.ad_id = a.id', 'left')
                        ->join('users u', 'm.user_id = u.id_user')
                        ->where('m.created_at >=', $startDate . ' 00:00:00')
                        ->where('m.created_at <=', $endDate . ' 23:59:59')
                        ->orderBy('m.created_at', 'DESC')
                        ->get()
                        ->getResultArray();
                    break;

                case 'reports':
                    $data = $db->table('reports r')
                        ->select('r.*, a.title as ad_title, ru.first_name as reporter_first_name, ru.last_name as reporter_last_name, ru.email as reporter_email, au.first_name as accused_first_name, au.last_name as accused_last_name, au.email as accused_email')
                        ->join('ads a', 'r.reported_ad_id = a.id', 'left')
                        ->join('users ru', 'r.reporter_id = ru.id_user')
                        ->join('users au', 'r.reported_user_id = au.id_user', 'left')
                        ->where('r.created_at >=', $startDate . ' 00:00:00')
                        ->where('r.created_at <=', $endDate . ' 23:59:59')
                        ->orderBy('r.created_at', 'DESC')
                        ->get()
                        ->getResultArray();
                    break;
            }

            if ($format === 'csv') {
                // Générer CSV
                $csvContent = $this->arrayToCsv($data);

                // Retourner le fichier CSV
                return $this->response
                    ->setHeader('Content-Type', 'text/csv')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"")
                    ->setBody($csvContent);
            } else {
                // Retourner JSON
                return $this->success([
                    'metadata' => [
                        'type' => $type,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'total_records' => count($data),
                        'exported_at' => date('Y-m-d H:i:s')
                    ],
                    'data' => $data
                ], 'Données exportées avec succès');
            }

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Statistiques paiements/boosts pour le dashboard admin
     */
    public function paymentsStats()
    {
        try {
            $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');

            $db = \Config\Database::connect();

            // Sommaire global
            $summary = $db->table('payments')
                ->select('
                    COUNT(*) as total_count,
                    COALESCE(SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END), 0) as total_revenue,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as total_pending,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as total_failed,
                    SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as total_refunded
                ')
                ->where('created_at >=', $startDate . ' 00:00:00')
                ->where('created_at <=', $endDate . ' 23:59:59')
                ->get()->getRowArray();

            // CA par jour
            $revenueByDay = $db->table('payments')
                ->select('DATE(created_at) as date, COALESCE(SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END),0) as revenue, COUNT(*) as count')
                ->where('created_at >=', $startDate . ' 00:00:00')
                ->where('created_at <=', $endDate . ' 23:59:59')
                ->groupBy('DATE(created_at)')
                ->orderBy('date', 'ASC')
                ->get()->getResultArray();

            // Répartition par méthode de paiement
            $byMethod = $db->table('payments')
                ->select('payment_method, COUNT(*) as count, COALESCE(SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END),0) as revenue')
                ->where('created_at >=', $startDate . ' 00:00:00')
                ->where('created_at <=', $endDate . ' 23:59:59')
                ->groupBy('payment_method')
                ->orderBy('revenue', 'DESC')
                ->get()->getResultArray();

            // Annonces boostées = paiements paid (supposé) groupés par annonce
            $boostedAds = $db->table('payments p')
                ->select('p.ad_id, COUNT(p.id) as payments_count, COALESCE(SUM(CASE WHEN p.status = "paid" THEN p.amount ELSE 0 END),0) as revenue')
                ->where('p.created_at >=', $startDate . ' 00:00:00')
                ->where('p.created_at <=', $endDate . ' 23:59:59')
                ->groupBy('p.ad_id')
                ->orderBy('revenue', 'DESC')
                ->limit(10)
                ->get()->getResultArray();

            // Top 10 transactions (récentes)
            $latest = $db->table('payments p')
                ->select('p.id, p.reference, p.amount, p.status, p.payment_method, p.created_at, p.processed_at, p.user_id, p.ad_id')
                ->orderBy('p.created_at', 'DESC')
                ->limit(10)
                ->get()->getResultArray();

            return $this->success([
                'period' => [ 'start_date' => $startDate, 'end_date' => $endDate ],
                'summary' => [
                    'total_transactions' => (int)$summary['total_count'],
                    'total_revenue' => round((float)$summary['total_revenue'], 2),
                    'paid' => (int)$summary['total_paid'],
                    'pending' => (int)$summary['total_pending'],
                    'failed' => (int)$summary['total_failed'],
                    'refunded' => (int)$summary['total_refunded']
                ],
                'revenue_by_day' => $revenueByDay,
                'by_method' => $byMethod,
                'top_boosted_ads' => $boostedAds,
                'latest_transactions' => $latest
            ], 'Statistiques de paiements récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Liste paginée/filtrée des transactions pour le tableau récapitulatif
     */
    public function paymentsTransactions()
    {
        try {
            $page = max(1, (int)($this->request->getGet('page') ?? 1));
            $perPage = min(200, max(1, (int)($this->request->getGet('per_page') ?? 25)));
            $status = $this->request->getGet('status'); // paid, pending, failed, refunded
            $method = $this->request->getGet('method'); // mobile_money, card, etc.
            $startDate = $this->request->getGet('start_date');
            $endDate = $this->request->getGet('end_date');

            $db = \Config\Database::connect();
            $builder = $db->table('payments p')
                ->select('p.*, a.title as ad_title, u.email as user_email')
                ->join('ads a', 'a.id = p.ad_id', 'left')
                ->join('users u', 'u.id_user = p.user_id', 'left');

            if (!empty($status)) {
                $builder->where('p.status', $status);
            }
            if (!empty($method)) {
                $builder->where('p.payment_method', $method);
            }
            if (!empty($startDate)) {
                $builder->where('p.created_at >=', $startDate . ' 00:00:00');
            }
            if (!empty($endDate)) {
                $builder->where('p.created_at <=', $endDate . ' 23:59:59');
            }

            $total = (clone $builder)->countAllResults(false);
            $rows = $builder->orderBy('p.created_at', 'DESC')
                ->limit($perPage, ($page - 1) * $perPage)
                ->get()->getResultArray();

            return $this->success([
                'transactions' => $rows,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => (int)ceil($total / $perPage)
                ]
            ], 'Transactions récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Convertit un array en CSV
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // En-têtes
        fputcsv($output, array_keys($data[0]));

        // Données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
