You are acting as a Principal Full-Stack Engineer and Lead Software Architect.

You are building a complete, production-ready, enterprise-grade LPG Distribution & Cylinder Inventory Management System named "Perfect LPG (Pvt.) LTD" using CodeIgniter 4 (PHP 8.x), MySQL, Bootstrap 5.3, HTML5, CSS3, and JavaScript/jQuery.

======================================================================
CRITICAL OPERATIONAL RULE: TOKEN & CREDIT OPTIMIZATION PROTOCOL
======================================================================
1. At the start of EVERY response, check if a context file named `claude.md` exists or is provided in the project workspace.
2. Read `claude.md` immediately to determine:
   - System Module Architecture & Completed Features.
   - Current Module In-Progress (and exact file/function level status).
   - Remaining Backlog & Pending Tasks.
3. Do NOT regenerate already completed code or repeat unnecessary system explanations. Save tokens and start execution DIRECTLY from the exact step where the previous agent stopped.
4. After completing any sub-task or code block, UPDATE `claude.md` with:
   - [x] Completed tasks
   - [/] In-Progress task with current line/function details
   - [ ] Next pending steps
5. If credit runs out or connection drops, the user will simply prompt "Continue based on claude.md". You must read `claude.md` and resume seamlessly without losing state or re-explaining the project.

======================================================================
TECH STACK & ARCHITECTURE REQUIREMENTS
======================================================================
- Backend: CodeIgniter 4 Framework (PHP 8.x) - Strict MVC Pattern, Controller Validation, Service Classes, Session Auth, CSRF Protection, Password Hashing.
- Database: MySQL with clean relational schemas (Foreign keys, indexes, daily auto-closing stock logic, ledger constraints).
- UI/UX: Bootstrap 5.3, Clean Left Dark Sidebar, Soft Shadows, Clean Tables, Dynamic Modal Popups, jQuery AJAX DataTables.

======================================================================
CORE BUSINESS LOGIC & DUAL SALE MODEL REQUIREMENTS
======================================================================
1. DUAL SELLING MECHANISM:
   - Cylinder-Based Sale: User selects cylinder capacity (6 kg, 11.8 kg, 15 kg, 35 kg, 45 kg, 45.2 kg). Rate is per cylinder. Total Kg = `Cylinder Count * Capacity`.
   - Gas Weight (Kg)-Based Sale: User inputs exact Gas Weight sold in Kg (e.g., 225 kg, 450 kg). Rate is per Kg. Auto-calculates total price and updates corresponding empty/filled cylinder counts.

2. RECEIVING & PAYMENT FLOW:
   - POS / Sale Entry Receiving: Allows entering Cash and Online amounts during sale creation. Billed total minus payments automatically routes to Customer Credit/Balance.
   - Direct Customer Payment Receiving: Dedicated "+ Record Payment" modal in Customer Profile/Directory. Allows receiving partial/full payments via Cash/Online/Cheque, updating Customer Credit Due and reflecting instantly in the Daily Cash Report & Ledger.

======================================================================
COMPLETE MODULE SYSTEM ARCHITECTURE
======================================================================

1. AUTHENTICATION & LOGIN SYSTEM:
   - Modern, professional Login View (`/login`) with username/email and password fields, remember me, error alerts, and CSRF protection.
   - Auth Controller handling login validation, session storage (User ID, Role, Name, LoggedIn status), and Secure Logout.
   - Route Filters / Auth Middleware protecting all admin dashboard routes from unauthenticated access.

2. SYSTEM NAVIGATION (LEFT SIDEBAR LAYOUT):
   - Fixed Dark Left Sidebar with dynamic active links:
     * Dashboard (KPI Summary: Today's Sales, Cylinder Stock, Total Gas Stock in KG)
     * New Sale (Point of Sale with toggle for Cylinder-wise vs Kg-wise Sale)
     * Sales Search (Date Filters, Customer/Vehicle Filter, Cylinder Size Filter, Payment Status Filter, Inline Breakdown, Total Calculations)
     * Customers / Parties (Directory, Customer Ledger, Credit Due, Total Gas Purchased in KG, Record Payment Modal)
     * Cylinder Stock (Daily Filled Stock, Daily Empty Stock, Auto-calculated Closing Stock, Total Closing Weight in KG)
     * Daily Cash / Sale Report (Opening Cash, Cash Sale, Credit Sale, Online, Bowser/Salary Expenses, Hand Over Cash, Balance)
     * Settings & Users (User Management, Cylinder Capacities, Gas Rates)

3. SALES SEARCH & TRANSACTIONS MODULE:
   - Filters: From Date, To Date, Customer/Vehicle, Cylinder Size, Payment Status (All/Cash/Credit/Online).
   - Summary Cards: Results Count, Total Kg, Total Amount, Cash, Online, Credit.
   - Data Grid: Date, Customer Name, Vehicle No, Cylinder/Gas Mix Breakdown (e.g., "5x11.8kg @4,450" or "225 kg @ Rs 89.33/kg"), Total Kg, Empty Recv Count, Total Amount, Payment Modes, Action Buttons (Delete/Edit).

4. CUSTOMERS & LEDGER MANAGEMENT:
   - Customer Header: Name, Phone, Vehicle, Total Purchased (Rs), Credit Due (Rs) [Highlighted Red], Total Gas (Kg).
   - Modal Action: "+ Record Payment" (Date, Amount, Payment Mode [Cash/Online/Bank], Notes).
   - Ledger Table Columns: Sr No, Date, Vehicle No, Cylinder Quantities (11.8kg, 15kg, 35kg, 45kg), Total Kg, Rate, Credit, Cr Receive, Balance.

5. CYLINDER & GAS STOCK TRACKING:
   - Cylinder Categories: 6 kg, 11.8 kg, 15 kg, 35 kg, 45 kg, 45.2 kg.
   - Filled Gas Stock Grid: Opening, Received, Sold, Adjustment, Closing Stock (`Opening + Received - Sold + Adjustment`).
   - Empty Cylinders Grid: Opening, Recv from Customers, Sent for Refill, Adjustment, Closing Stock (`Opening + Recv - Sent + Adjustment`).
   - Total Weight Summary: Auto-calculated `SUM(Closing Stock * Size Weight in KG)`.

6. DAILY SALE SUMMARY REPORT:
   - Header: "Perfect LPG (Pvt.) LTD" - Daily Sale Report.
   - Columns: Date, Opening Cash, Cash Sale, Credit Sale, Online Sale, Credit Received, Total Sale, Bowser/Salary, Expenses, Hand Over, Balance.

======================================================================
INITIAL STEP TO EXECUTE RIGHT NOW
======================================================================
1. Create and output the initial `claude.md` project tracking context file with all the above modules cataloged as `[ ] Pending`.
2. Generate the MySQL Database Schema (`schema.sql`) with full relational constraints (including `users`, `customers`, `cylinder_types`, `daily_filled_stock`, `daily_empty_stock`, `sales`, `sale_items`, `customer_payments`, `daily_summaries`).
3. Build the Authentication Controller & Login View (`login.php`).
4. Set up the Base Dashboard Master Layout (`app.php`) featuring the Bootstrap 5 Left Sidebar navigation.

Start step 1, 2, 3, and 4 now, and update `claude.md` at the end of your response.