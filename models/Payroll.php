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
    // Same result as getAll() + the clients/sites dropdown lookups, in one round trip.
    // $siteScope: null (admin, unfiltered), int/string (single site), or array (manager scope; [] = no access).
    // $sitesDropdownIds: null (admin -> all active sites) or array of ids to restrict the sites dropdown to.
    public function getAllWithDropdowns($siteScope, $month, $clientId, $sitesDropdownIds) {
        $params = ['month' => $month];

        $payrollFilter = '';
        if ($clientId) {
            $payrollFilter .= " AND s.client_id = :cid ";
            $params['cid'] = $clientId;
        }
        if (is_array($siteScope)) {
            if (count($siteScope) === 0) {
                $payrollFilter .= " AND 1=0 ";
            } else {
                $params['payroll_site_ids'] = '{' . implode(',', array_map('intval', $siteScope)) . '}';
                $payrollFilter .= " AND w.site_id = ANY(:payroll_site_ids::int[]) ";
            }
        } elseif ($siteScope !== null) {
            $params['sid'] = $siteScope;
            $payrollFilter .= " AND w.site_id = :sid ";
        }

        $sitesFilter = '';
        if (is_array($sitesDropdownIds)) {
            if (count($sitesDropdownIds) === 0) {
                $sitesFilter = ' AND 1=0 ';
            } else {
                $params['dropdown_site_ids'] = '{' . implode(',', array_map('intval', $sitesDropdownIds)) . '}';
                $sitesFilter = ' AND id = ANY(:dropdown_site_ids::int[]) ';
            }
        }

        $sql = "SELECT
            (SELECT COALESCE(json_agg(p), '[]'::json) FROM (
                SELECT pr.*, w.full_name as name, wc.name as category_name, s.name as site_name, s.client_id
                FROM payroll pr
                JOIN workers w ON pr.worker_id = w.id
                JOIN worker_categories wc ON w.category_id = wc.id
                JOIN sites s ON w.site_id = s.id
                WHERE pr.month_year = :month $payrollFilter
                ORDER BY pr.month_year DESC, w.full_name ASC
            ) p) AS payroll_json,
            (SELECT COALESCE(json_agg(x), '[]'::json) FROM (SELECT id, company_name FROM clients ORDER BY company_name) x) AS clients_json,
            (SELECT COALESCE(json_agg(x), '[]'::json) FROM (SELECT id, name, client_id FROM sites WHERE is_active = TRUE $sitesFilter ORDER BY name) x) AS sites_json
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'payrolls' => json_decode($row['payroll_json'], true) ?: [],
            'clients' => json_decode($row['clients_json'], true) ?: [],
            'sites' => json_decode($row['sites_json'], true) ?: [],
        ];
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
