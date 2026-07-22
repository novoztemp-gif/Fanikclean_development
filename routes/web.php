<?php
// Auth Routes
$router->get('/login', 'AuthController', 'login');
$router->post('/login', 'AuthController', 'authenticate');
$router->get('/logout', 'AuthController', 'logout');

// Dashboard
$router->get('/', 'DashboardController', 'index');
$router->get('/dashboard', 'DashboardController', 'index');

// Workers
$router->get('/workers', 'WorkerController', 'index');
$router->get('/workers/profile', 'WorkerController', 'profile');
$router->post('/workers/create', 'WorkerController', 'create');
$router->post('/workers/update', 'WorkerController', 'update');
$router->post('/workers/assets/add', 'WorkerController', 'addAsset');
$router->post('/workers/assets/delete', 'WorkerController', 'deleteAsset');
$router->post('/workers/bulk/transfer', 'WorkerController', 'bulkTransfer');
$router->post('/workers/bulk/uniform', 'WorkerController', 'bulkUniform');

// Users
$router->get('/users', 'UserController', 'index');
$router->get('/users/profile', 'UserController', 'profile');
$router->post('/users/create', 'UserController', 'create');
$router->post('/users/update', 'UserController', 'update');
$router->get('/users/assignments', 'ManagerSiteController', 'index');
$router->post('/users/assignments/save', 'ManagerSiteController', 'assign');

// Clients
$router->get('/clients', 'ClientController', 'index');
$router->post('/clients/create', 'ClientController', 'create');

// Attendance
$router->get('/attendance', 'AttendanceController', 'index');
$router->get('/attendance/manager', 'AttendanceController', 'managerAttendance');
$router->post('/attendance/manager/save', 'AttendanceController', 'saveManagerAttendance');
$router->get('/attendance/my', 'AttendanceController', 'viewMyAttendance');
$router->post('/attendance/save', 'AttendanceController', 'saveBulk');

// Leave
$router->get('/leave', 'LeaveController', 'index');
$router->post('/leave/create', 'LeaveController', 'create');
$router->post('/leave/approve', 'LeaveController', 'approve');

// Billing & Payroll
$router->get('/billing', 'BillingController', 'index');
$router->post('/billing/generate', 'BillingController', 'generate');
$router->get('/invoices', 'InvoiceController', 'index');
$router->get('/payroll', 'PayrollController', 'index');
$router->get('/payroll/export', 'PayrollController', 'export');
$router->post('/payroll/approve', 'PayrollController', 'approve');

// Sites
$router->post('/sites/create', 'SiteController', 'create');

// API Endpoints
$router->get('/api/workers', 'WorkerController', 'apiGetBySite');

// Invoices upgrade
$router->post('/invoices/generate', 'InvoiceController', 'generate');
$router->post('/invoices/pay', 'InvoiceController', 'pay');
$router->get('/invoices/print', 'InvoiceController', 'print');

// Financial & Configuration (New Modules)
$router->get('/financial', 'FinancialController', 'index');
$router->get('/rates', 'RateController', 'index');
$router->post('/rates/updateDefault', 'RateController', 'updateDefault');
$router->get('/audit', 'AuditController', 'index');
$router->post('/leave/reject', 'LeaveController', 'reject');
$router->get('/reports', 'ReportController', 'index');


