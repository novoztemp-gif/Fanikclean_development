<?php
require_once __DIR__ . '/../config/Database.php';

class Payroll {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    public function generateMonthly($monthYear) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT w.id, w.category_id, w.daily_rate_override, wc.default_rate, 
                       rm.rate_per_day as client_rate,
                       SUM(CASE 
                            WHEN a.status='p' THEN 1 
                            WHEN a.status='h' THEN 0.5 
                            WHEN a.status='off' THEN 1 
                            WHEN a.status='pl' THEN 1 
                            WHEN a.status='sd' THEN 2 ELSE 0 END) as days, 
                       SUM(COALESCE(a.ot_hours, 0)) as ot_hrs
                FROM workers w
                JOIN worker_categories wc ON w.category_id = wc.id
                LEFT JOIN sites s ON w.site_id = s.id
                LEFT JOIN rate_master rm ON s.client_id = rm.client_id AND w.category_id = rm.category_id
                LEFT JOIN attendance a ON w.id = a.worker_id AND TO_CHAR(a.attendance_date, 'YYYY-MM') = :my
                GROUP BY w.id, w.daily_rate_override, wc.default_rate, rm.rate_per_day
            ");
            $stmt->execute(['my' => $monthYear]);
            $workers = $stmt->fetchAll();

            foreach ($workers as $w) {
                // Determine rate priority: 
                // 1. Individual worker override 
                // 2. Client-specific category rate (Rate Master)
                // 3. System-wide category default
                $rate = $w['daily_rate_override'] ?: ($w['client_rate'] ?: $w['default_rate']);
                
                $days = $w['days'] ?? 0;
                $ot = $w['ot_hrs'] ?? 0;
                
                $basicPay = $days * $rate;
                $otPay = $ot * ($rate / 8); // Assuming 8 hr standard day
                $netPay = $basicPay + $otPay;

                $ins = $this->db->prepare("
                    INSERT INTO payroll (worker_id, month_year, days_worked, basic_pay, ot_days, ot_pay, advance_deduction, net_pay, status)
                    VALUES (:wid, :my, :dw, :bp, :otd, :otp, 0, :np, 'Pending')
                    ON CONFLICT (worker_id, month_year) 
                    DO UPDATE SET 
                        days_worked = EXCLUDED.days_worked, 
                        basic_pay = EXCLUDED.basic_pay, 
                        ot_pay = EXCLUDED.ot_pay, 
                        net_pay = EXCLUDED.net_pay
                ");
                $ins->execute([
                    'wid' => $w['id'], 'my' => $monthYear,
                    'dw' => $days, 'bp' => $basicPay, 'otd' => $ot/8, 'otp' => $otPay, 'np' => $netPay
                ]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }
    public function getAll($siteIds = null, $month = null, $clientId = null) {
        $query = "
            SELECT p.*, w.full_name as name, wc.name as category_name, s.name as site_name, s.client_id
            FROM payroll p
            JOIN workers w ON p.worker_id = w.id
            JOIN worker_categories wc ON w.category_id = wc.id
            JOIN sites s ON w.site_id = s.id
            WHERE 1=1
        ";
        
        $params = [];
        if ($month) {
            $query .= " AND p.month_year = :month ";
            $params['month'] = $month;
        }

        if ($clientId) {
            $query .= " AND s.client_id = :cid ";
            $params['cid'] = $clientId;
        }

        if (is_array($siteIds)) {
            // A manager scoped to zero sites must see nothing -- not everything.
            if (count($siteIds) === 0) {
                $query .= " AND 1=0 ";
            } else {
                $placeholders = [];
                foreach ($siteIds as $i => $id) {
                    $key = "sid$i";
                    $placeholders[] = ":$key";
                    $params[$key] = $id;
                }
                $query .= " AND w.site_id IN (" . implode(',', $placeholders) . ") ";
            }
        } elseif ($siteIds !== null) {
            $query .= " AND w.site_id = :sid ";
            $params['sid'] = $siteIds;
        }
        
        $query .= " ORDER BY p.month_year DESC, w.full_name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
