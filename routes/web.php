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
$router->post('/users/suspend', 'UserController', 'suspend');
$router->post('/users/reactivate', 'UserController', 'reactivate');
$router->post('/users/delete', 'UserController', 'delete');
$router->post('/users/reset-password', 'UserController', 'resetPassword');
$router->get('/users/assignments', 'ManagerSiteController', 'index');
$router->post('/users/assignments/save', 'ManagerSiteController', 'assign');

// Clients
$router->get('/clients', 'ClientController', 'index');
$router->post('/clients/create', 'ClientController', 'create');

// Attendance
$router->get('/attendance', 'AttendanceController', 'index');
$router->get('/attendance/register', 'AttendanceController', 'register');
$router->get('/attendance/register/export', 'AttendanceController', 'exportRegister');
$router->get('/attendance/manager', 'AttendanceController', 'managerAttendance');
$router->get('/attendance/manager/register', 'AttendanceController', 'managerRegister');
$router->post('/attendance/manager/save', 'AttendanceController', 'saveManagerAttendance');
$router->get('/attendance/my', 'AttendanceController', 'viewMyAttendance');
$router->post('/attendance/save', 'AttendanceController', 'saveBulk');

// Billing & Payroll
$router->get('/billing', 'BillingController', 'index');
$router->post('/billing/generate', 'BillingController', 'generate');
$router->get('/invoices', 'InvoiceController', 'index');
$router->get('/payroll', 'PayrollController', 'index');
$router->get('/payroll/export', 'PayrollController', 'export');
$router->post('/payroll/approve', 'PayrollController', 'approve');

// Sites
$router->post('/sites/create', 'SiteController', 'create');
$router->post('/sites/delete', 'SiteController', 'delete');
$router->post('/sites/restore', 'SiteController', 'restore');

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
$router->get('/reports', 'ReportController', 'index');


