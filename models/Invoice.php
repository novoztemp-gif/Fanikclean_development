<?php
require_once __DIR__ . '/../config/Database.php';

class Invoice {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    public function getAllInvoices($siteIds = null) {
        // LEFT JOINs so invoices whose billing/client/site rows are missing
        // (orphaned records) still appear in the ledger and can be settled —
        // an INNER JOIN silently hid them, leaving unpaid invoices unreachable.
        $query = "
            SELECT i.invoice_no, i.issue_date, i.amount, i.status,
                   COALESCE(c.company_name, '— Unlinked (no billing record)') AS company_name,
                   b.month_year, b.from_date, b.to_date, s.name as site_name
            FROM invoices i
            LEFT JOIN billing b ON i.billing_id = b.id
            LEFT JOIN clients c ON b.client_id = c.id
            LEFT JOIN sites s ON b.site_id = s.id
        ";
        
        $params = [];
        if (!empty($siteIds)) {
            if (is_array($siteIds)) {
                $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
                $query .= " WHERE b.site_id IN ($placeholders) ";
                $params = $siteIds;
            } else {
                $query .= " WHERE b.site_id = ? ";
                $params = [$siteIds];
            }
        }
        
        $query .= " ORDER BY i.id DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPendingBilling($siteIds = null) {
        $query = "
            SELECT b.*, c.company_name, s.name as site_name, b.from_date, b.to_date 
            FROM billing b 
            JOIN clients c ON b.client_id = c.id
            JOIN sites s ON b.site_id = s.id
            WHERE b.status != 'Invoiced'
        ";
        
        $params = [];
        if (!empty($siteIds)) {
            if (is_array($siteIds)) {
                $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
                $query .= " AND b.site_id IN ($placeholders) ";
                $params = $siteIds;
            } else {
                $query .= " AND b.site_id = ? ";
                $params = [$siteIds];
            }
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateFromBilling($billingId, $templateId = null) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT grand_total FROM billing WHERE id = :bid");
            $stmt->execute(['bid' => $billingId]);
            $bill = $stmt->fetch();

            if (!$bill) { throw new Exception("Billing missing"); }

            $invNo = 'INV-' . date('ym') . '-' . rand(100, 999);

            $ins = $this->db->prepare("
                INSERT INTO invoices (billing_id, invoice_no, issue_date, due_date, amount, status, template_id) 
                VALUES (:bid, :inv, :iss, :due, :amt, 'Unpaid', :tid)
            ");
            $ins->execute([
                'bid' => $billingId,
                'inv' => $invNo,
                'iss' => date('Y-m-d'),
                'due' => date('Y-m-d', strtotime('+15 days')),
                'amt' => $bill['grand_total'],
                'tid' => $templateId
            ]);

            $upd = $this->db->prepare("UPDATE billing SET status = 'Invoiced' WHERE id = :bid");
            $upd->execute(['bid' => $billingId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getInvoiceDetails($invoiceNo) {
        $stmt = $this->db->prepare("
            SELECT i.*, b.month_year, b.from_date, b.to_date, b.site_id, c.company_name, c.contact_person, c.address, c.gstin, c.id as client_id,
                   s.name as site_name, s.address as site_address, t.slug as template_slug
            FROM invoices i
            JOIN billing b ON i.billing_id = b.id
            JOIN clients c ON b.client_id = c.id
            JOIN sites s ON b.site_id = s.id
            LEFT JOIN invoice_templates t ON i.template_id = t.id
            WHERE i.invoice_no = :inv
        ");
        $stmt->execute(['inv' => $invoiceNo]);
        $data = $stmt->fetch();

        if (!$data) return null;

        // Use date range if available, fall back to month_year for legacy data
        if (!empty($data['from_date']) && !empty($data['to_date'])) {
            $itemsStmt = $this->db->prepare("
                SELECT wc.name as description, 
                       SUM(CASE WHEN a.status='p' THEN 1 WHEN a.status='h' THEN 0.5 WHEN a.status='off' THEN 1 ELSE 0 END) as quantity, 
                       wc.default_rate as rate
                FROM attendance a
                JOIN workers w ON a.worker_id = w.id
                JOIN worker_categories wc ON w.category_id = wc.id
                WHERE a.site_id = :sid AND a.attendance_date BETWEEN :fd AND :td
                GROUP BY wc.name, wc.default_rate
            ");
            $itemsStmt->execute(['sid' => $data['site_id'], 'fd' => $data['from_date'], 'td' => $data['to_date']]);
        } else {
            $itemsStmt = $this->db->prepare("
                SELECT wc.name as description, 
                       SUM(CASE WHEN a.status='p' THEN 1 WHEN a.status='h' THEN 0.5 WHEN a.status='off' THEN 1 ELSE 0 END) as quantity, 
                       wc.default_rate as rate
                FROM attendance a
                JOIN workers w ON a.worker_id = w.id
                JOIN worker_categories wc ON w.category_id = wc.id
                WHERE a.site_id = :sid AND TO_CHAR(a.attendance_date, 'YYYY-MM') = :my
                GROUP BY wc.name, wc.default_rate
            ");
            $itemsStmt->execute(['sid' => $data['site_id'], 'my' => $data['month_year']]);
        }
        $data['items'] = $itemsStmt->fetchAll();

        return $data;
    }
}
