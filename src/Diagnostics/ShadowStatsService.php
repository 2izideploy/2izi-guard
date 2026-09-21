<?php
declare(strict_types=1);

namespace TwoIzi\Guard\Diagnostics;

use TwoIzi\Guard\Storage\Database;

final class ShadowStatsService
{
    public function __construct(private Database $db) {}

    public function summary(int $hours = 168): array
    {
        $hours = max(1, min(24 * 90, $hours));
        $pdo = $this->db->pdo();
        $sql = 'SELECT action, COUNT(*) total, ROUND(AVG(risk_score),1) avg_risk, '
             . "SUM(predicted_decision='pass') predicted_pass, SUM(predicted_decision='pow') predicted_pow, "
             . "SUM(predicted_decision='interactive') predicted_interactive, SUM(predicted_decision='deny') predicted_deny, "
             . 'SUM(risk_score >= 70) risk_70_plus, SUM(risk_score >= 90) risk_90_plus '
             . 'FROM guard_events WHERE event_time >= DATE_SUB(NOW(6), INTERVAL ' . $hours . ' HOUR) '
             . "AND mode='shadow' AND predicted_decision IS NOT NULL GROUP BY action ORDER BY total DESC";
        $rows = $pdo->query($sql)->fetchAll();
        foreach ($rows as &$row) {
            foreach (['total','predicted_pass','predicted_pow','predicted_interactive','predicted_deny','risk_70_plus','risk_90_plus'] as $k) {
                $row[$k] = (int)$row[$k];
            }
            $row['avg_risk'] = (float)$row['avg_risk'];
        }
        return ['hours' => $hours, 'actions' => $rows];
    }
}
