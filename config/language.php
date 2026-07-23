<?php
// Language configuration
// Session is already started in config.php

// Available languages
$available_languages = [
    'en' => 'English',
    'ar' => 'العربية'
];

// Default language
$default_language = 'en';

// Get current language from session or set default
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = $default_language;
}

// Handle language switch
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['language'] = $_GET['lang'];
    // Remove query parameter to keep URL clean
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $redirect_url");
    exit;
}

$lang = $_SESSION['language'];

// Translations array
$translations = [
    'en' => [
        // ====================================
        // App & Header
        // ====================================
        'app_title' => 'Restaurant POS',
        'waiter_terminal' => 'Waiter Terminal',
        'admin_dashboard' => 'Admin Dashboard',
        'logout' => 'Logout',
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'welcome_back' => 'Welcome back',
        'here_is_today' => "Here's what's happening today.",
        
        // ====================================
        // Admin Menu
        // ====================================
        'kitchens' => 'Kitchens & Printers',
        'categories' => 'Categories',
        'menu_items' => 'Menu Items',
        'printer_status' => 'Printer Status',
        'user_management' => 'User Management',
        'orders' => 'Orders',
        'reports' => 'Reports',
        
        // ====================================
        // Admin Dashboard
        // ====================================
        'today_orders' => "Today's Orders",
        'pending_items' => 'Pending Items',
        'active_users' => 'Active Users',
        'recent_orders' => 'Recent Orders Today',
        'view_all' => 'View All',
        'order_number' => 'Order #',
        'table' => 'Table',
        'items' => 'Items',
        'time' => 'Time',
        
        // ====================================
        // Kitchens Page
        // ====================================
        'kitchens_description' => 'Manage kitchen stations and their connected printers',
        'kitchen_list' => 'Kitchen List',
        'add_kitchen' => 'Add Kitchen',
        'kitchen_name' => 'Kitchen Name',
        'printer_ip' => 'Printer IP',
        'port' => 'Port',
        'status' => 'Status',
        'last_checked' => 'Last Checked',
        'actions' => 'Actions',
        'online' => 'Online',
        'offline' => 'Offline',
        'edit' => 'Edit',
        'test' => 'Test',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'printer_model' => 'Printer Model',
        'paper_size' => 'Paper Size',
        'notes' => 'Notes',
        'notes_placeholder' => 'Any special instructions...',
        'select_kitchen' => 'Select Kitchen',
        'select_category' => 'Select Category',
        'other' => 'Other',
        'create' => 'Create',
        'update' => 'Update',
        
        // ====================================
        // Categories Page
        // ====================================
        'categories_description' => 'Manage food categories and their kitchen assignments',
        'category_list' => 'Category List',
        'add_category' => 'Add Category',
        'category_name' => 'Category Name',
        'kitchen' => 'Kitchen',
        'sort_order' => 'Sort Order',
        'description' => 'Description',
        'description_placeholder' => 'Category description...',
        'items_count' => 'Items Count',
        'active' => 'Active',
        'inactive' => 'Inactive',
        
        // ====================================
        // Menu Items Page
        // ====================================
        'menu_items_description' => 'Manage your restaurant menu items, prices, and availability',
        'menu_list' => 'Menu Items List',
        'add_item' => 'Add Item',
        'edit_item' => 'Edit Menu Item',
        'item_name' => 'Item Name',
        'price' => 'Price',
        'prep_time' => 'Prep Time',
        'available' => 'Available',
        'unavailable' => 'Unavailable',
        'minutes' => 'min',
        'no_kitchen' => 'No kitchen',
        'item_saved' => 'Menu item saved successfully',
        'item_deleted' => 'Menu item deleted successfully',
        'confirm_delete_item' => 'Are you sure you want to delete this menu item?',
        
        // ====================================
        // Printer Status Page
        // ====================================
        'printer_status_description' => 'Monitor all kitchen printers and view print job history',
        'printer_monitoring' => 'Printer Status & Monitoring',
        'printer_cards' => 'Printer Cards',
        'recent_print_jobs' => 'Recent Print Jobs',
        'refresh' => 'Refresh',
        'test_printer' => 'Test Printer',
        'view_jobs' => 'View Jobs',
        'retry' => 'Retry',
        'failed_jobs' => 'failed jobs',
        'not_configured' => 'Not configured',
        'testing_printer' => 'Testing printer...',
        'printer_test' => 'Printer test',
        'test_failed' => 'Test failed',
        'retrying_print' => 'Retrying print job...',
        'print_retried' => 'Print job retried successfully',
        'showing_jobs_for' => 'Showing jobs for kitchen',
        'refreshed' => 'Refreshed',
        'no_print_jobs' => 'No print jobs found',
        'test_print' => 'Test Print',
        
        // ====================================
        // Orders Page
        // ====================================
        'orders_description' => 'View and manage all restaurant orders',
        'filter_orders' => 'Filter Orders',
        'date_range' => 'Date Range',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'all_time' => 'All Time',
        'all_status' => 'All Status',
        'pending' => 'Pending',
        'preparing' => 'Preparing',
        'ready' => 'Ready',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'all_waiters' => 'All Waiters',
        'reset' => 'Reset',
        'export_csv' => 'Export CSV',
        'view_details' => 'View Details',
        'print_receipt' => 'Print Receipt',
        'order_details' => 'Order Details',
        'update_status' => 'Update Status',
        'payment_status' => 'Payment Status',
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
        'orders_list' => 'Orders List',
        'date_time' => 'Date & Time',
        'total' => 'Total',
        'no_orders' => 'No orders found',
        'error_loading_orders' => 'Failed to load orders',
        
        // ====================================
        // ====================================
        // Settings Page
        // ====================================
        'restaurant_settings' => 'Restaurant Settings',
        'configure_restaurant' => 'Configure your restaurant details and table layout',
        'general_settings' => 'General Settings',
        'restaurant_name' => 'Restaurant Name',
        'total_tables' => 'Total Number of Tables',
        'table_prefix' => 'Table Name Prefix',
        'table_prefix_placeholder' => 'Table ',
        'save_settings' => 'Save Settings',
        'system_status' => 'System Status',
        'active_tables' => 'Active Tables',
        'active_printers' => 'Active Printers',
        'last_updated' => 'Last Updated',
        'table_preview' => 'Table Preview',
        'danger_zone' => 'Danger Zone',
        'regenerate_tables' => 'Regenerate Tables',
        'regenerate_tables_desc' => 'This will regenerate all tables based on your settings. Order history will be preserved.',
        'clear_all_tables' => 'Clear All Tables',
        'clear_all_tables_desc' => 'This will mark all tables as inactive. Order history will be preserved.',
        'reset_all_data' => 'Reset All Data',
        'reset_warning' => 'WARNING:',
        'reset_warning_desc' => 'This will delete ALL orders, order items, and print jobs! This action cannot be undone.',
        'type_reset_confirm' => 'Type "RESET" to confirm:',
        'type_reset' => 'Type RESET here',
        'confirm_reset' => 'Confirm Reset',
        'my_restaurant' => 'My Restaurant',
        'table' => 'Table',
        'and_more' => 'and',
        'more' => 'more',
        'no_tables' => 'No tables configured',
        'error_loading' => 'Failed to load settings',
        'settings_saved' => 'Settings saved successfully',
        'error_saving' => 'Failed to save settings',
        'confirm_regenerate' => 'Regenerate all tables? This will create new tables based on your settings.',
        'tables_regenerated' => 'Tables regenerated successfully',
        'confirm_clear_tables' => 'Clear all tables? This will mark all tables as inactive. Order history will be preserved.',
        'tables_cleared' => 'Tables cleared',
        'type_reset_error' => 'Please type "RESET" to confirm data deletion.',
        'reset_final_warning' => 'FINAL WARNING',
        'reset_warning_details' => 'This will permanently delete:\n- ALL orders\n- ALL order items\n- ALL print jobs\n\nThis action CANNOT be undone!',
        'are_you_sure' => 'Are you ABSOLUTELY sure?',
        'error_resetting' => 'Failed to reset database',
        'changing_regenerates' => 'Changing this will regenerate tables automatically',
        
        // ====================================
        // User Management
        // ====================================
        'users' => 'Users',
        'username' => 'Username',
        'password' => 'Password',
        'full_name' => 'Full Name',
        'role' => 'Role',
        'waiter' => 'Waiter',
        'admin' => 'Admin',
        'kitchen_staff' => 'Kitchen Staff',
        'add_user' => 'Add User',
        'edit_user' => 'Edit User',
        'delete_user' => 'Delete User',
        'confirm_delete_user' => 'Are you sure you want to delete this user?',
        'cannot_delete_self' => 'You cannot delete your own account',
        'user_deleted' => 'User deleted successfully',
        
        // ====================================
        // Common
        // ====================================
        'loading' => 'Loading...',
        'error' => 'Error',
        'success' => 'Success',
        'warning' => 'Warning',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'add' => 'Add',
        'close' => 'Close',
        'yes' => 'Yes',
        'no' => 'No',
        'search' => 'Search',
        // In the 'en' array add:
        'send_to_kitchen' => 'Send to Kitchen',
        // English ('en' section)
        'select_table' => 'Select Table',
        'choose_table' => 'Choose Table',
        'table_number' => 'Table Number',
        'click_to_add' => 'Click to add items',
        'current_order' => 'Current Order',
        'no_items' => 'No items in order',
        'total' => 'Total',
        'confirm_order' => 'Confirm Order',
        'confirm_send' => 'Send order to kitchen?',
        'yes_send' => 'Yes, Send',
        'cancel' => 'Cancel',
        'order_sent' => 'Order sent to kitchen!',
        'select_table_warning' => 'Please select a table',
        'no_items_warning' => 'No items to submit',
        'offline_mode' => 'Offline mode - Orders will be queued',
        'connected' => 'Connected',
        'offline' => 'Offline',
        'removed' => 'Removed',
        'special_instructions' => 'Special instructions...',
        'next' => 'Next',
        'back' => 'Back',
        'review_order' => 'Review Order',
        'choose_table_to_start' => 'Choose a table to start taking orders',
        'choose_food_category' => 'Choose a food category',
        'select_items' => 'Select Items',
        'add_to_order' => 'Add to Order',
        'select_category' => 'Select Category',
        'select_items' => 'Select Items',
        'review_order' => 'Review Order',
        'items' => 'items',
        // English
        'select_category_first' => 'Select a category first',
        'no_order_to_review' => 'No order to review',
        'cancel_order' => 'Cancel Order',
        'sending_order' => 'Sending order',
        'added' => 'added',
        'confirm_cancel_order' => 'Are you sure you want to cancel the entire order?',
        'order_cancelled' => 'Order cancelled',
        'no_items_to_send' => 'No items to send',
        'confirm_send_order' => 'Send order to kitchen?',
        'error_sending' => 'Failed to send order',
        // English
        'refresh_now' => 'Refresh Now',
        'auto_refresh' => 'Auto Refresh',
        'auto_refresh_on' => 'Auto refresh enabled',
        'auto_refresh_off' => 'Auto refresh disabled',
        'refreshing_orders' => 'Refreshing orders...',
        // Print functions
        'print_options' => 'Print Options',
        'print_to' => 'Print to',
        'local_print' => 'Local Print (Admin Printer)',
        'print_all_kitchens' => 'Print to All Kitchens',
        'items_sent' => 'items sent to kitchen',
        'printing' => 'Printing... Please wait',
        'already_printed' => 'This order has already been printed to',
        'reprint_confirm' => 'Reprint to all kitchens anyway?',
        'print_failed' => 'Failed to print',
        // English
        'printer_type' => 'Printer Type',
        'network_printer' => 'Network Printer',
        'windows_printer' => 'Windows Printer',
        'windows_printer_name' => 'Windows Printer Name',
        'select_printer' => 'Select Printer',
        'refresh_printers' => 'Refresh Printer List',
        'windows_printer_hint' => 'Select from installed Windows printers. Arabic prints correctly with this option.',
        'select_printer_warning' => 'Please select a printer',
        'enter_printer_ip' => 'Please enter printer IP address',
        'kitchen_saved' => 'Kitchen saved successfully',
        'error_saving' => 'Failed to save kitchen',
        // English
        'search_items' => 'Search items...',
        'no_items_found' => 'No items found',
        // English
        'sessions' => 'Sessions',
        'all_sessions' => 'All Sessions',
        'open_sessions' => 'Open Sessions',
        'closed_sessions' => 'Closed Sessions',
        'session_total' => 'Session Total',
        'no_sessions' => 'No sessions found',
        'no_orders_in_session' => 'No orders in this session',
        'guests' => 'guests',
        'print_options' => 'Print Options',
        'print_to' => 'Print to',
        'local_print' => 'Local Print (Admin Printer)',
        'print_all_kitchens' => 'Print to All Kitchens',
        'session' => 'Session',
        'order_updated' => 'Order updated successfully',
        'error_updating' => 'Failed to update order',
        'time' => 'Time',
        'kitchen_order' => 'KITCHEN ORDER',
        'reprint' => 'REPRINT',
        'total_items' => 'TOTAL ITEMS',
        'note' => 'Note',
        'order_number' => 'Order #',
        'table' => 'Table',
        'time' => 'Time',
        'order_receipt' => 'ORDER RECEIPT',
        'waiter' => 'Waiter',
        'subtotal' => 'SUBTOTAL',
        'tax' => 'TAX',
        'thank_you' => 'Thank you for your visit.',
        'add_order' => 'Add Order',
        'close_bill' => 'Close Bill',
        'open_table' => 'Open Table',
        'session_open' => 'Session Open',
        'currency' => 'SP',
        'category' => 'Category',
        'view_all_customer_sessions_and_their_orders' => 'View All Customer Sessions And Their Orders',
        'filter_session' => 'Filter Session',
        // English
        'close_session' => 'Close Session',
        'confirm_close_session' => 'Are you sure you want to close this session? This will finalize all orders in the session.',
        'session_closed' => 'Session closed successfully',
        // English
        'cancel_item' => 'Cancel Item',
        'cancelled_item' => 'CANCELLED ITEM',
        'cancelled_by_waiter' => 'Cancelled by waiter',
        'item_cancelled' => 'Item cancelled successfully',
        'print_cancel' => 'Print Cancel',
        'manage_orders' => 'Manage Orders',
        // English
        'confirm_cancel_item' => 'Are you sure you want to cancel this item?',
        // English
        'closed_by' => 'Closed by',
        'bill_details' => 'Bill Details',
        // English
        'print_bill_confirm' => 'Close this table and print the bill? Click OK to proceed, Cancel to go back.',
        // English
        'cancelled' => 'Cancelled',
        // English
        'controller_ticket' => 'CONTROLLER TICKET',
        'print_controller' => 'Print Controller Ticket',
        // English
        'controller_printer' => 'Controller Printer',
        'controller_printer_hint' => 'Select a Windows printer for controller tickets (orders without prices). Leave disabled if not needed.',
        'disable' => 'Disable',
        // English
        'accountant_printer' => 'Accountant Printer',
        'enable_printing' => 'Enable Printing',
        'yes' => 'Yes',
        'no' => 'No',
        'save_accountant_settings' => 'Save Accountant Settings',
        'save_controller_settings' => 'Save Controller Settings',
        'accountant_saved' => 'Accountant settings saved',
        'controller_saved' => 'Controller settings saved',
        // English
        'controller_ticket' => 'CONTROLLER TICKET',
        // In 'en' array
        'parking_request' => 'Parking Request',
        // English
        'parking_login' => 'Parking Staff Login',
        'invalid_credentials' => 'Invalid username or password',
        'parking_lots' => 'Parking Lots',
        'mark_completed' => 'Mark Completed',
        'waiting_for_requests' => 'Waiting for parking requests...',
        'logout' => 'Logout',
        // English
        'parking_staff' => 'Parking Staff',
        'parking_dashboard' => 'Parking Dashboard',
        'parking_lot_numbers' => 'Parking Lot Numbers',
        'send' => 'Send',
        // English
        'qty' => 'Qty',
        'item' => 'Item',
        'unit_price' => 'Unit',
        'total' => 'Total',
        // English
        'no_open_session' => 'No active session for this table. Please ask a waiter.',
        'request_sent' => 'Your request has been sent. Your car will be brought shortly.',
        // English
        'parking_reports' => 'Parking Reports',
        'total_requests' => 'Total Requests',
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'all_time' => 'All Time',
        'generate_report' => 'Generate Report',
        'date' => 'Date',
        'lot_numbers' => 'Lot Numbers',
        'table' => 'Table',
        'request_time' => 'Request Time',
        // English
        'view_parking_requests' => 'View parking requests statistics',
        'filter' => 'Filter',
        'period' => 'Period',
        'select_period_and_generate' => 'Select a period and click Generate Report',
        'no_requests' => 'No parking requests found',
        // English
        'print_on_controller' => 'Print on Controller Printer',
        'print_on_controller_hint' => 'Uncheck to exclude this item from the controller ticket.',
        // English
        'enable_sound' => 'Enable Sound',
        'sound_on' => 'Sound ON',
        // English
        'save_and_add_new' => 'Save & Add New',
        'item_description' => 'Description',   // English
        // English
        'reports' => 'Reports',
        'reports_description' => 'View reports on sessions, orders, and served tables',
        'filter_reports' => 'Filter Reports',
        'from_date' => 'From Date',
        'to_date' => 'To Date',
        'generate' => 'Generate',
        'select_dates_and_generate' => 'Select date range and click Generate',
        'opened_sessions' => 'Opened Sessions',
        'total_orders' => 'Total Orders',
        'tables_served_by_waiter' => 'Tables Served by Waiter',
        'waiter' => 'Waiter',
        'tables_served' => 'Tables Served',
        'total_tables_served' => 'Total Tables Served',
        'tables' => 'tables',
        'select_date_range' => 'Please select a date range',
        // English
        'developed_by' => 'Developed by',
        'phone' => 'Phone',
        // English
        'about' => 'About',
        'developer' => 'Developer',
        'phone' => 'Phone',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'close' => 'Close',
        // English
        'available_hint' => 'Uncheck to hide this item from the waiter panel',
        // English
        'reserved_table' => 'Reserved Table (Owner)',
        'reserved_table_hint' => 'Check this to skip accountant bill when closing',
        'is_reserved' => 'Reserved',
        // English
        'custom_range' => 'Custom Range',
        'total_revenue' => 'Total Revenue',
        'avg_order_value' => 'Avg Order Value',
        'top_selling_items' => 'Top Selling Items',
        'quantity_sold' => 'Quantity Sold',
        'orders_by_waiter' => 'Orders by Waiter',
        'orders_count' => 'Orders Count',
        'daily_breakdown' => 'Daily Breakdown',
        'cancelled_items' => 'Cancelled Items',
        'print' => 'Print',
        // English
        'orders_list' => 'Orders List',
        'cancelled_items_list' => 'Cancelled Items List',
        'revenue_breakdown' => 'Revenue Breakdown',
        'no_data' => 'No data available',
        'details' => 'Details',
        'quantity' => 'َQuantity',
        // English
        'reserved_for' => 'Reserved For',
        'reserved_for_placeholder' => 'e.g., John Smith, Owner',
        'reserved_for_hint' => 'Enter the name of the person or group this table is reserved for.',
        // English
        'customer_name' => 'Customer Name',
        'customer_name_placeholder' => 'e.g., John Smith',
        'customer_name_hint' => 'This name will appear on the accountant bill.',
        // English
        'custom_range' => 'Custom Range',
        'from_date' => 'From Date',
        'to_date' => 'To Date',
        'select_date_range' => 'Please select both dates',
        // English
        'other_kitchens' => 'Other Kitchens',
        'category_icon' => 'Category Icon',

    ],
    
    'ar' => [
        // ====================================
        // App & Header
        // ====================================
        'app_title' => 'نظام إدارة المطعم',
        'waiter_terminal' => 'محطة الكابتن',
        'admin_dashboard' => 'لوحة التحكم',
        'logout' => 'تسجيل خروج',
        'dashboard' => 'لوحة العرض',
        'settings' => 'الإعدادات',
        'welcome_back' => 'مرحباً بعودتك',
        'here_is_today' => 'إليك ملخص اليوم',
        
        // ====================================
        // Admin Menu
        // ====================================
        'kitchens' => 'المطابخ والطابعات',
        'categories' => 'الأقسام',
        'menu_items' => 'الأصناف',
        'printer_status' => 'حالة الطابعات',
        'user_management' => 'إدارة المستخدمين',
        'orders' => 'الطلبات',
        'reports' => 'التقارير',
        
        // ====================================
        // Admin Dashboard
        // ====================================
        'today_orders' => 'طلبات اليوم',
        'pending_items' => 'أصناف معلقة',
        'active_users' => 'مستخدمين نشطين',
        'recent_orders' => 'أحدث الطلبات اليوم',
        'view_all' => 'عرض الكل',
        'order_number' => 'رقم الطلب',
        'table' => 'طاولة',
        'items' => 'أصناف',
        'time' => 'الوقت',
        
        // ====================================
        // Kitchens Page
        // ====================================
        'kitchens_description' => 'إدارة المطابخ والطابعات',
        'kitchen_list' => 'قائمة المطابخ',
        'add_kitchen' => 'إضافة مطبخ',
        'kitchen_name' => 'اسم المطبخ',
        'printer_ip' => 'عنوان الطابعة',
        'port' => 'المنفذ',
        'status' => 'الحالة',
        'last_checked' => 'آخر اختبار',
        'actions' => 'إجراءات',
        'online' => 'متصل',
        'offline' => 'غير متصل',
        'edit' => 'تعديل',
        'test' => 'اختبار',
        'delete' => 'حذف',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'printer_model' => 'طراز الطابعة',
        'paper_size' => 'حجم الورق',
        'notes' => 'ملاحظات',
        'notes_placeholder' => 'أي تعليمات خاصة...',
        'select_kitchen' => 'اختر مطبخاً',
        'select_category' => 'اختر قسماً',
        'other' => 'أخرى',
        'create' => 'إنشاء',
        'update' => 'تحديث',
        
        // ====================================
        // Categories Page
        // ====================================
        'categories_description' => 'إدارة أقسام الطعام وتوزيعها على المطابخ',
        'category_list' => 'قائمة الأقسام',
        'add_category' => 'إضافة قسم',
        'category_name' => 'اسم القسم',
        'kitchen' => 'المطبخ',
        'sort_order' => 'ترتيب العرض',
        'description' => 'الوصف',
        'description_placeholder' => 'وصف القسم...',
        'items_count' => 'عدد الأصناف',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        
        // ====================================
        // Menu Items Page
        // ====================================
        'menu_items_description' => 'إدارة قائمة الطعام والأسعار والتوفر',
        'menu_list' => 'قائمة الأصناف',
        'add_item' => 'إضافة صنف',
        'edit_item' => 'تعديل الصنف',
        'item_name' => 'اسم الصنف',
        'price' => 'السعر',
        'prep_time' => 'وقت التحضير',
        'available' => 'متوفر',
        'unavailable' => 'غير متوفر',
        'minutes' => 'دقيقة',
        'no_kitchen' => 'لا يوجد مطبخ',
        'item_saved' => 'تم حفظ الصنف بنجاح',
        'item_deleted' => 'تم حذف الصنف بنجاح',
        'confirm_delete_item' => 'هل أنت متأكد من حذف هذا الصنف؟',
        
        // ====================================
        // Printer Status Page
        // ====================================
        'printer_status_description' => 'مراقبة جميع طابعات المطبخ وسجل مهام الطباعة',
        'printer_monitoring' => 'حالة الطابعات',
        'printer_cards' => 'بطاقات الطابعات',
        'recent_print_jobs' => 'مهام الطباعة الأخيرة',
        'refresh' => 'تحديث',
        'test_printer' => 'اختبار الطابعة',
        'view_jobs' => 'عرض المهام',
        'retry' => 'إعادة المحاولة',
        'failed_jobs' => 'مهام فاشلة',
        'not_configured' => 'غير مهيأ',
        'testing_printer' => 'جارٍ اختبار الطابعة...',
        'printer_test' => 'اختبار الطابعة',
        'test_failed' => 'فشل الاختبار',
        'retrying_print' => 'جارٍ إعادة محاولة الطباعة...',
        'print_retried' => 'تمت إعادة محاولة الطباعة بنجاح',
        'showing_jobs_for' => 'عرض مهام للمطبخ',
        'refreshed' => 'تم التحديث',
        'no_print_jobs' => 'لا توجد مهام طباعة',
        'test_print' => 'طباعة اختبارية',
        
        // ====================================
        // Orders Page
        // ====================================
        'orders_description' => 'عرض وإدارة جميع طلبات المطعم',
        'filter_orders' => 'تصفية الطلبات',
        'date_range' => 'النطاق الزمني',
        'today' => 'اليوم',
        'yesterday' => 'أمس',
        'this_week' => 'هذا الأسبوع',
        'this_month' => 'هذا الشهر',
        'all_time' => 'كل الوقت',
        'all_status' => 'كل الحالات',
        'pending' => 'معلق',
        'preparing' => 'قيد التحضير',
        'ready' => 'جاهز',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        'all_waiters' => 'كل الكباتن',
        'reset' => 'إعادة تعيين',
        'export_csv' => 'تصدير CSV',
        'view_details' => 'عرض التفاصيل',
        'print_receipt' => 'طباعة الإيصال',
        'order_details' => 'تفاصيل الطلب',
        'update_status' => 'تحديث الحالة',
        'payment_status' => 'حالة الدفع',
        'unpaid' => 'غير مدفوع',
        'paid' => 'مدفوع',
        'refunded' => 'مسترجع',
        'orders_list' => 'قائمة الطلبات',
        'date_time' => 'التاريخ والوقت',
        'total' => 'الإجمالي',
        'no_orders' => 'لا توجد طلبات',
        'error_loading_orders' => 'فشل في تحميل الطلبات',
        
        // ====================================
        // ====================================
        // Settings Page
        // ====================================
        'restaurant_settings' => 'إعدادات المطعم',
        'configure_restaurant' => 'إعدادات المطعم وتخطيط الطاولات',
        'general_settings' => 'الإعدادات العامة',
        'restaurant_name' => 'اسم المطعم',
        'total_tables' => 'عدد الطاولات الكلي',
        'table_prefix' => 'بادئة اسم الطاولة',
        'table_prefix_placeholder' => 'طاولة ',
        'save_settings' => 'حفظ الإعدادات',
        'system_status' => 'حالة النظام',
        'active_tables' => 'طاولات نشطة',
        'active_printers' => 'طابعات نشطة',
        'last_updated' => 'آخر تحديث',
        'table_preview' => 'معاينة الطاولات',
        'danger_zone' => 'منطقة الخطر',
        'regenerate_tables' => 'إعادة إنشاء الطاولات',
        'regenerate_tables_desc' => 'سيتم إعادة إنشاء جميع الطاولات بناءً على إعداداتك. سيتم الاحتفاظ بسجل الطلبات.',
        'clear_all_tables' => 'مسح كل الطاولات',
        'clear_all_tables_desc' => 'سيتم تعطيل جميع الطاولات. سيتم الاحتفاظ بسجل الطلبات.',
        'reset_all_data' => 'إعادة تعيين كل البيانات',
        'reset_warning' => 'تحذير:',
        'reset_warning_desc' => 'سيتم حذف جميع الطلبات وأصناف الطلبات ومهام الطباعة! هذا الإجراء لا يمكن التراجع عنه.',
        'type_reset_confirm' => 'اكتب "RESET" للتأكيد:',
        'type_reset' => 'اكتب RESET هنا',
        'confirm_reset' => 'تأكيد الحذف',
        'my_restaurant' => 'مطعمي',
        'table' => 'طاولة',
        'and_more' => 'و',
        'more' => 'المزيد',
        'no_tables' => 'لا توجد طاولات',
        'error_loading' => 'فشل في تحميل الإعدادات',
        'settings_saved' => 'تم حفظ الإعدادات بنجاح',
        'error_saving' => 'فشل في حفظ الإعدادات',
        'confirm_regenerate' => 'إعادة إنشاء جميع الطاولات؟ سيتم إنشاء طاولات جديدة بناءً على إعداداتك.',
        'tables_regenerated' => 'تم إعادة إنشاء الطاولات بنجاح',
        'confirm_clear_tables' => 'مسح جميع الطاولات؟ سيتم تعطيل جميع الطاولات. سيبقى سجل الطلبات محفوظاً.',
        'tables_cleared' => 'تم مسح الطاولات',
        'type_reset_error' => 'الرجاء كتابة "RESET" لتأكيد حذف البيانات.',
        'reset_final_warning' => 'تحذير أخير',
        'reset_warning_details' => 'سيتم حذف:\n- جميع الطلبات\n- جميع أصناف الطلبات\n- جميع مهام الطباعة\n\nهذا الإجراء لا يمكن التراجع عنه!',
        'are_you_sure' => 'هل أنت متأكد تماماً؟',
        'error_resetting' => 'فشل في إعادة تعيين قاعدة البيانات',
        'changing_regenerates' => 'تغيير هذا سيقوم بإعادة إنشاء الطاولات تلقائياً',
        'fallback_printer' => 'Fallback Printer',
        
        // ====================================
        // User Management
        // ====================================
        'users' => 'المستخدمين',
        'username' => 'اسم المستخدم',
        'password' => 'كلمة المرور',
        'full_name' => 'الاسم الكامل',
        'role' => 'الدور',
        'waiter' => 'كابتن',
        'admin' => 'مدير',
        'kitchen_staff' => 'طاقم المطبخ',
        'add_user' => 'إضافة مستخدم',
        'edit_user' => 'تعديل مستخدم',
        'delete_user' => 'حذف مستخدم',
        'confirm_delete_user' => 'هل أنت متأكد من حذف هذا المستخدم؟',
        'cannot_delete_self' => 'لا يمكنك حذف حسابك الخاص',
        'user_deleted' => 'تم حذف المستخدم بنجاح',
        
        // ====================================
        // Common
        // ====================================
        'loading' => 'جاري التحميل...',
        'error' => 'خطأ',
        'success' => 'تم بنجاح',
        'warning' => 'تنبيه',
        'confirm' => 'تأكيد',
        'back' => 'رجوع',
        'add' => 'إضافة',
        'close' => 'إغلاق',
        'yes' => 'نعم',
        'no' => 'لا',
        'search' => 'بحث',
        // In the 'ar' array add:
        'send_to_kitchen' => 'إرسال للمطبخ',
        // Arabic ('ar' section)
        'select_table' => 'اختر الطاولة',
        'choose_table' => 'اختر طاولة',
        'table_number' => 'رقم الطاولة',
        'click_to_add' => 'انقر لإضافة الأصناف',
        'current_order' => 'الطلب الحالي',
        'no_items' => 'لا توجد أصناف',
        'total' => 'الإجمالي',
        'confirm_order' => 'تأكيد الطلب',
        'confirm_send' => 'هل تريد إرسال الطلب للمطبخ؟',
        'yes_send' => 'نعم، أرسل',
        'cancel' => 'إلغاء',
        'order_sent' => 'تم إرسال الطلب للمطبخ!',
        'select_table_warning' => 'الرجاء اختيار طاولة',
        'no_items_warning' => 'لا توجد أصناف للإرسال',
        'offline_mode' => 'وضع غير متصل - سيتم حفظ الطلبات',
        'connected' => 'متصل',
        'offline' => 'غير متصل',
        'removed' => 'تم الإزالة',
        'special_instructions' => 'تعليمات خاصة...',
        // Arabic
        'next' => 'التالي',
        'back' => 'السابق',
        'review_order' => 'مراجعة الطلب',
        'choose_table_to_start' => 'اختر طاولة لبدء أخذ الطلبات',
        'choose_food_category' => 'اختر قسم الطعام',
        'select_items' => 'اختر الأصناف',
        'add_to_order' => 'إضافة للطلب',
        'select_category' => 'اختر القسم',
        'select_items' => 'اختر الأصناف',
        'review_order' => 'مراجعة الطلب',
        'items' => 'أصناف',

        // Arabic
        'select_category_first' => 'اختر قسماً أولاً',
        'no_order_to_review' => 'لا يوجد طلب للمراجعة',
        'cancel_order' => 'إلغاء الطلب',
        'sending_order' => 'جاري إرسال الطلب',
        'added' => 'تمت الإضافة',
        'confirm_cancel_order' => 'هل أنت متأكد من إلغاء الطلب بالكامل؟',
        'order_cancelled' => 'تم إلغاء الطلب',
        'no_items_to_send' => 'لا توجد أصناف للإرسال',
        'confirm_send_order' => 'إرسال الطلب للمطبخ؟',
        'error_sending' => 'فشل إرسال الطلب',
        // Arabic
        'refresh_now' => 'تحديث الآن',
        'auto_refresh' => 'تحديث تلقائي',
        'auto_refresh_on' => 'تم تفعيل التحديث التلقائي',
        'auto_refresh_off' => 'تم إيقاف التحديث التلقائي',
        'refreshing_orders' => 'جاري تحديث الطلبات...',
        // Print functions
        'print_options' => 'خيارات الطباعة',
        'print_to' => 'إرسال إلى',
        'local_print' => 'طباعة محلية (Admin Printer)',
        'print_all_kitchens' => 'طباعة على جميع الطابعات',
        'items_sent' => 'تم إرسال طلب الطباعة',
        'printing' => 'يتم الطباعة... الرجاء الانتظار',
        'already_printed' => 'لقد تم طباعة هذا الطلب مسبقا',
        'reprint_confirm' => 'طباعة إلى جميع الطابعات؟',
        'print_failed' => 'فشل الطباعة',
        // Arabic
        'printer_type' => 'نوع الطابعة',
        'network_printer' => 'طابعة شبكة',
        'windows_printer' => 'طابعة ويندوز',
        'windows_printer_name' => 'اسم طابعة ويندوز',
        'select_printer' => 'اختر طابعة',
        'refresh_printers' => 'تحديث قائمة الطابعات',
        'windows_printer_hint' => 'اختر من طابعات ويندوز المثبتة. العربية تطبع بشكل صحيح مع هذا الخيار.',
        'select_printer_warning' => 'الرجاء اختيار طابعة',
        'enter_printer_ip' => 'الرجاء إدخال عنوان الطابعة',
        'kitchen_saved' => 'تم حفظ المطبخ بنجاح',
        'error_saving' => 'فشل في حفظ المطبخ',
        // Arabic
        'search_items' => 'بحث عن الأصناف...',
        'no_items_found' => 'لا توجد أصناف مطابقة',
        // Arabic
        'sessions' => 'الجلسات',
        'all_sessions' => 'كل الجلسات',
        'open_sessions' => 'جلسات مفتوحة',
        'closed_sessions' => 'جلسات مغلقة',
        'session_total' => 'إجمالي الجلسة',
        'no_sessions' => 'لا توجد جلسات',
        'no_orders_in_session' => 'لا توجد طلبات في هذه الجلسة',
        'guests' => 'ضيوف',
        'print_options' => 'خيارات الطباعة',
        'print_to' => 'طباعة إلى',
        'local_print' => 'طباعة محلية',
        'print_all_kitchens' => 'طباعة لجميع المطابخ',
        'session' => 'جلسة',
        'order_updated' => 'تم تحديث الطلب بنجاح',
        'error_updating' => 'فشل تحديث الطلب',
        'time' => 'الوقت',
        'kitchen_order' => 'طلب المطبخ',
        'reprint' => 'إعادة طباعة',
        'total_items' => 'إجمالي الأصناف',
        'note' => 'ملاحظة',
        'order_number' => 'رقم الطلب',
        'table' => 'طاولة',
        'time' => 'الوقت',
        'order_receipt' => 'إيصال الطلب',
        'subtotal' => 'المجموع',
        'tax' => 'الضريبة',
        'thank_you' => 'نشكر لكم زيارتكم',
        'add_order' => 'إضافة طلب',
        'close_bill' => 'إغلاق الطاولة',
        'open_table' => 'فتح طاولة',
        'session_open' => 'طاولة مفتوحة',
        'currency' => 'ل.س',
        'category' => 'القسم',
        'Session Management' => 'إدارة الجلسات',
        'view_all_customer_sessions_and_their_orders' => 'عرض جميع الجلسات والطلبات المتعلقة بها.',
        'filter_session' => 'تصفية الجلسات',
        // Arabic
        'close_session' => 'إغلاق الجلسة',
        'confirm_close_session' => 'هل أنت متأكد من إغلاق هذه الجلسة؟ سيتم إنهاء جميع الطلبات في الجلسة.',
        'session_closed' => 'تم إغلاق الجلسة بنجاح',
        // Arabic
        'cancel_item' => 'إلغاء صنف',
        'cancelled_item' => 'صنف ملغي',
        'cancelled_by_waiter' => 'تم الإلغاء بواسطة الكابتن',
        'item_cancelled' => 'تم إلغاء الصنف بنجاح',
        'print_cancel' => 'طباعة إلغاء',
        // Arabic
        'manage_orders' => 'إدارة الطلبات',
        'confirm_cancel_item' => 'هل أنت متأكد من إلغاء هذا الصنف؟',
        // Arabic
        'closed_by' => 'تم الإغلاق بواسطة',
        'bill_details' => 'تفاصيل الفاتورة',
        // Arabic
        'print_bill_confirm' => 'إغلاق هذه الطاولة وطباعة الفاتورة؟ اضغط موافق للمتابعة، إلغاء للعودة.',
        // Arabic
        'cancelled' => 'ملغي',
        // Arabic
        'controller_ticket' => 'تذكرة المراقب',
        'print_controller' => 'طباعة تذكرة المراقب',
        // Arabic
        'controller_printer' => 'طابعة المراقب',
        'controller_printer_hint' => 'اختر طابعة ويندوز لتذاكر المراقب (طلبات بدون أسعار). اترك معطلاً إذا لم يكن مطلوباً.',
        'disable' => 'تعطيل',
        // Arabic
        'accountant_printer' => 'طابعة المحاسب',
        'enable_printing' => 'تفعيل الطباعة',
        'yes' => 'نعم',
        'no' => 'لا',
        'save_accountant_settings' => 'حفظ إعدادات طابعة المحاسب',
        'save_controller_settings' => 'حفظ إعدادات طابعة المراقب',
        'accountant_saved' => 'تم حفظ إعدادات طابعة المحاسب',
        'controller_saved' => 'تم حفظ إعدادات طابعة المراقب',
        // Arabic
        'controller_ticket' => 'تذكرة المراقب',
        // In 'ar' array
        'parking_request' => 'طلب سيارة',
        // Arabic
        'parking_login' => 'دخول موظف المرآب',
        'invalid_credentials' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
        'parking_lots' => 'أرقام المرآب',
        'mark_completed' => 'تأكيد التجهيز',
        'waiting_for_requests' => 'في انتظار طلبات المرآب...',
        'logout' => 'تسجيل خروج',
        // Arabic
        'parking_staff' => 'موظف مرآب',
        'parking_dashboard' => 'لوحة تحكم المرآب',
        'parking_lot_numbers' => 'رقم الركن',
        'send' => 'أرسل',
        // Arabic
        'qty' => 'الكمية',
        'item' => 'الصنف',
        'unit_price' => 'السعر',
        'total' => 'الإجمالي',
        // Arabic
        'no_open_session' => 'لا توجد جلسة نشطة لهذه الطاولة. الرجاء سؤال الكابتن.',
        'request_sent' => 'تم إرسال طلبك. سيتم إحضار سيارتك قريباً.',
        // Arabic
        'parking_reports' => 'تقارير مواقف السيارات',
        'total_requests' => 'إجمالي الطلبات',
        'today' => 'اليوم',
        'this_week' => 'هذا الأسبوع',
        'this_month' => 'هذا الشهر',
        'all_time' => 'كل الوقت',
        'generate_report' => 'إنشاء التقرير',
        'date' => 'التاريخ',
        'lot_numbers' => 'أرقام المواقف',
        'table' => 'طاولة',
        'request_time' => 'وقت الطلب',
        // Arabic
        'view_parking_requests' => 'عرض إحصائيات طلبات المواقف',
        'filter' => 'تصفية',
        'period' => 'الفترة',
        'select_period_and_generate' => 'اختر الفترة وانشئ التقرير',
        'no_requests' => 'لا توجد طلبات مواقف',
        // Arabic
        'print_on_controller' => 'طباعة على طابعة المراقب',
        'print_on_controller_hint' => 'قم بإلغاء التحديد لاستبعاد هذا الصنف من تذكرة المراقب.',
        // Arabic
        'enable_sound' => 'تفعيل الصوت',
        'sound_on' => 'الصوت مفعل',
        // Arabic
        'save_and_add_new' => 'حفظ وإضافة جديد',
        'item_description' => 'الوصف',          // Arabic
        // Arabic
        'reports' => 'التقارير',
        'reports_description' => 'عرض تقارير الجلسات والطلبات والطاولات التي تم تقديمها',
        'filter_reports' => 'تصفية التقارير',
        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
        'generate' => 'إنشاء',
        'select_dates_and_generate' => 'اختر نطاق التاريخ وانشئ التقرير',
        'opened_sessions' => 'الجلسات المفتوحة',
        'total_orders' => 'إجمالي الطلبات',
        'tables_served_by_waiter' => 'الطاولات التي خدمها الكابتن',
        'waiter' => 'الكابتن',
        'tables_served' => 'الطاولات المخدمة',
        'total_tables_served' => 'إجمالي الطاولات المخدمة',
        'tables' => 'طاولات',
        'select_date_range' => 'الرجاء اختيار نطاق التاريخ',
        // Arabic
        'developed_by' => 'تم التطوير بواسطة',
        'phone' => 'الهاتف',
        // Arabic
        'about' => 'حول',
        'developer' => 'المطور',
        'phone' => 'الهاتف',
        'email' => 'البريد الإلكتروني',
        'whatsapp' => 'واتساب',
        'close' => 'إغلاق',
        // Arabic
        'available_hint' => 'قم بإلغاء التحديد لإخفاء هذا الصنف من لوحة الكابتن',
        // Arabic
        'reserved_table' => 'طاولة محجوزة (للمالك)',
        'reserved_table_hint' => 'قم بتحديد هذا الخيار لتخطي طباعة فاتورة المحاسب عند إغلاق الطاولة',
        'is_reserved' => 'محجوزة',
        // Arabic
        'click_table_to_toggle_reserved' => 'انقر على طاولة لتحديدها كمحجوزة (للمالك) – لن تتم طباعة فاتورة عند الإغلاق.',
        'reserved_table' => 'طاولة محجوزة (للمالك)',
        'reserved_table_hint' => 'قم بإلغاء التحديد لإزالة حالة الحجز.',
        'is_reserved' => 'طاولة محجوزة',
        // Arabic
        'custom_range' => 'نطاق مخصص',
        'total_revenue' => 'إجمالي الإيرادات',
        'avg_order_value' => 'متوسط قيمة الطلب',
        'top_selling_items' => 'أكثر الأصناف مبيعاً',
        'quantity_sold' => 'الكمية المباعة',
        'orders_by_waiter' => 'الطلبات حسب الكابتن',
        'orders_count' => 'عدد الطلبات',
        'daily_breakdown' => 'تفصيل يومي',
        'cancelled_items' => 'الأصناف الملغية',
        'print' => 'طباعة',
        // Arabic
        'orders_list' => 'قائمة الطلبات',
        'cancelled_items_list' => 'قائمة الأصناف الملغية',
        'revenue_breakdown' => 'تفصيل الإيرادات',
        'no_data' => 'لا توجد بيانات',
        'details' => 'التفاصيل',
        'quantity' => 'الكمية',
        // Arabic
        'reserved_for' => 'محجوز لـ',
        'reserved_for_placeholder' => 'مثال: جون سميث، مالك',
        'reserved_for_hint' => 'أدخل اسم الشخص أو المجموعة المحجوزة لهذه الطاولة.',
        // Arabic
        'customer_name' => 'اسم العميل',
        'customer_name_placeholder' => 'مثال: جون سميث',
        'customer_name_hint' => 'سيظهر هذا الاسم على فاتورة المحاسب.',
        // Arabic
        'custom_range' => 'نطاق مخصص',
        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
        'select_date_range' => 'الرجاء اختيار كلا التاريخين',
        // Arabic
        'other_kitchens' => 'المطابخ الأخرى',
        'fallback_printer' => 'الطابعة الاحتياطية',
        'category_icon' => 'ايقونة التصنيف',
    ]
];

// Helper function to get translation
function t($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}

// Get current direction
function get_dir() {
    global $lang;
    return $lang === 'ar' ? 'rtl' : 'ltr';
}
?>