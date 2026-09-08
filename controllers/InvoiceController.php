<?php
require_once __DIR__ . '/../models/Invoice.php';

class InvoiceController extends Controller {
    public function __construct() { 
        $this->requireRole([1, 2]); 
    }

    public function index() {
        $invModel = new Invoice();
        $siteScope = $this->isAdmin() ? null : $this->getAssignedSiteIds();

        $data = $invModel->getPendingAndInvoices($siteScope);
        $pendingBills = $data['pending'];
        $invoices = $data['invoices'];

        $this->view('invoices/index', [
            'pageTitle' => 'Financial: Billing & Invoicing',
            'pendingBills' => $pendingBills,
            'invoices' => $invoices
        ]);
    }

    public function generate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $billingId = $_POST['billing_id'] ?? null;
            if ($billingId) {
                $invModel = new Invoice();
                $invModel->generateFromBilling($billingId);
                $this->logAudit('Invoices', "Generated invoice from billing #$billingId");
                $_SESSION['toast'] = "Official Invoice Successfully Generated!";
            }
            $this->redirect('/invoices');
        }
    }

    public function pay() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $invoice_no = $_POST['invoice_no'] ?? null;
            if ($invoice_no) {
                $db = Database::connect();
                $stmt = $db->prepare("UPDATE invoices SET status = 'Paid', payment_date = CURRENT_DATE WHERE invoice_no = :inv");
                $stmt->execute(['inv' => $invoice_no]);
                $this->logAudit('Financial', "Marked invoice $invoice_no as Paid");
                $_SESSION['toast'] = "Invoice Status Updated: Paid";
            }
            $this->redirect('/invoices');
        }
    }

    public function print() {
        $invoiceNo = $_GET['inv'] ?? null;
        if (!$invoiceNo) { $this->redirect('/invoices'); return; }

        $invModel = new Invoice();
        $invoice = $invModel->getInvoiceDetails($invoiceNo);

        if (!$invoice) {
            $_SESSION['error'] = "Document retrieval failed: Record not found";
            $this->redirect('/invoices');
            return;
        }

        extract($invoice);
        require __DIR__ . '/../views/invoices/print.php';
    }
}
