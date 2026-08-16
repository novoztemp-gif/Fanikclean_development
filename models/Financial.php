<?php
require_once __DIR__ . '/../config/Database.php';

class Financial {
    private $db;
    public function __construct() { $this->db = Database::connect(); }

    public function getClientLedger() {
        // Invoice-based so the figures reconcile with the Dashboard and Reports:
        //   invoiced   = Σ issued invoice amounts (was Σ billing.grand_total, which
        //                counted billing runs that were never turned into invoices)
        //   collected  = Σ paid invoices
        //   outstanding (billed − collected in the view) = Σ unpaid invoices
        // This makes "Total Outstanding" identical to the invoice-derived figure
        // shown everywhere else.
        return $this->db->query("
            SELECT
                c.company_name,
                COALESCE(SUM(i.amount), 0) as total_billed,
                COALESCE(SUM(i.amount) FILTER (WHERE i.status = 'Paid'), 0) as total_collected
            FROM clients c
            LEFT JOIN billing b ON c.id = b.client_id
            LEFT JOIN invoices i ON b.id = i.billing_id
            GROUP BY c.id, c.company_name
            ORDER BY total_billed DESC
        ")->fetchAll();
    }
}
