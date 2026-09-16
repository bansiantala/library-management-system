//user
<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

/*
|--------------------------------------------------------------------------
| Dompdf Autoload
|--------------------------------------------------------------------------
*/

$autoloadPath = "../../vendor/autoload.php";

if (!file_exists($autoloadPath)) {
    die("Dompdf not found. Please run: composer require dompdf/dompdf");
}

require_once $autoloadPath;

/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$role = $_GET['role'] ?? '';

$download = $_GET['download'] ?? '';

$report_type = $_GET['report_type'] ?? '';

/*
|--------------------------------------------------------------------------
| Get Users For Web Report
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        name,
        email,
        phone,
        role,
        created_at
    FROM users
    WHERE 1 = 1
";

$params = [];
$types = "";

/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/

if ($role === 'admin' || $role === 'user') {

    $sql .= " AND role = ?";

    $params[] = $role;

    $types .= "s";
}

$sql .= " ORDER BY id DESC";

/*
|--------------------------------------------------------------------------
| Prepare Query
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "User report query failed: " .
        htmlspecialchars($conn->error)
    );
}

/*
|--------------------------------------------------------------------------
| Bind Parameters
|--------------------------------------------------------------------------
*/

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

/*
|--------------------------------------------------------------------------
| Execute
|--------------------------------------------------------------------------
*/

$stmt->execute();

$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {

    $users[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$totalUsers = count($users);

$totalAdmins = 0;

$totalNormalUsers = 0;

foreach ($users as $user) {

    if ($user['role'] === 'admin') {

        $totalAdmins++;

    } else {

        $totalNormalUsers++;
    }
}

/*
|--------------------------------------------------------------------------
| DOWNLOAD PDF
|--------------------------------------------------------------------------
|
| report_type = all
| report_type = role
|
*/

if ($download === 'pdf') {

    /*
    |--------------------------------------------------------------------------
    | Validate Role PDF
    |--------------------------------------------------------------------------
    */

    if (
        $report_type === 'role' &&
        $role !== 'admin' &&
        $role !== 'user'
    ) {

        header(
            "Location: users_report.php?error=select_role"
        );

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | PDF USER DATA
    |--------------------------------------------------------------------------
    */

    $pdfUsers = [];

    /*
    |--------------------------------------------------------------------------
    | ALL USERS PDF
    |--------------------------------------------------------------------------
    */

    if ($report_type === 'all') {

        $allSql = "
            SELECT
                id,
                name,
                email,
                phone,
                role,
                created_at
            FROM users
            ORDER BY id DESC
        ";

        $allResult = $conn->query($allSql);

        if ($allResult) {

            while ($row = $allResult->fetch_assoc()) {

                $pdfUsers[] = $row;
            }
        }

        $pdfTitle = "All Users Report";

        $pdfSubTitle = "Complete Library User Report";
    }

    /*
    |--------------------------------------------------------------------------
    | SELECTED ROLE PDF
    |--------------------------------------------------------------------------
    */

    else {

        $roleSql = "
            SELECT
                id,
                name,
                email,
                phone,
                role,
                created_at
            FROM users
            WHERE role = ?
            ORDER BY id DESC
        ";

        $roleStmt = $conn->prepare($roleSql);

        if (!$roleStmt) {

            die(
                "Role PDF query failed: " .
                htmlspecialchars($conn->error)
            );
        }

        $roleStmt->bind_param(
            "s",
            $role
        );

        $roleStmt->execute();

        $roleResult = $roleStmt->get_result();

        while ($row = $roleResult->fetch_assoc()) {

            $pdfUsers[] = $row;
        }

        $roleStmt->close();

        $pdfTitle =
            ucfirst($role) .
            " Users Report";

        $pdfSubTitle =
            "Selected Role User Report";
    }

    /*
    |--------------------------------------------------------------------------
    | PDF SUMMARY
    |--------------------------------------------------------------------------
    */

    $pdfTotalUsers = count($pdfUsers);

    $pdfTotalAdmins = 0;

    $pdfTotalNormalUsers = 0;

    foreach ($pdfUsers as $pdfUser) {

        if ($pdfUser['role'] === 'admin') {

            $pdfTotalAdmins++;

        } else {

            $pdfTotalNormalUsers++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Dompdf Options
    |--------------------------------------------------------------------------
    */

    $options = new \Dompdf\Options();

    $options->set(
        'defaultFont',
        'DejaVu Sans'
    );

    $options->set(
        'isRemoteEnabled',
        true
    );

    $dompdf = new \Dompdf\Dompdf(
        $options
    );

    /*
    |--------------------------------------------------------------------------
    | Safe Titles
    |--------------------------------------------------------------------------
    */

    $safePdfTitle = htmlspecialchars(
        $pdfTitle,
        ENT_QUOTES,
        'UTF-8'
    );

    $safePdfSubTitle = htmlspecialchars(
        $pdfSubTitle,
        ENT_QUOTES,
        'UTF-8'
    );

    /*
    |--------------------------------------------------------------------------
    | PDF HTML
    |--------------------------------------------------------------------------
    */

    $pdfHtml = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 30px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    color: #1e293b;
    font-size: 10px;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.header h1 {
    margin: 0;
    font-size: 21px;
    font-weight: bold;
    color: #1e293b;
}

.header h2 {
    margin: 6px 0 0;
    font-size: 16px;
    color: #2563eb;
}

.header h3 {
    margin: 5px 0 0;
    font-size: 11px;
    font-weight: normal;
    color: #64748b;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}

.info-table td {
    border: 1px solid #e2e8f0;
    padding: 7px;
}

.info-label {
    width: 25%;
    font-weight: bold;
    background: #f8fafc;
}

.summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.summary td {
    width: 33.33%;
    border: 1px solid #dbeafe;
    background: #eff6ff;
    padding: 10px;
    text-align: center;
}

.summary-number {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #2563eb;
}

.summary-label {
    display: block;
    margin-top: 4px;
    color: #475569;
    font-size: 9px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th {
    background: #2563eb;
    color: #ffffff;
    border: 1px solid #1d4ed8;
    padding: 7px;
    font-size: 8px;
    text-align: left;
}

.report-table td {
    border: 1px solid #e2e8f0;
    padding: 7px;
    font-size: 8px;
}

.report-table tr:nth-child(even) td {
    background: #f8fafc;
}

.admin-role {
    color: #dc2626;
    font-weight: bold;
}

.user-role {
    color: #2563eb;
    font-weight: bold;
}

.footer {
    text-align: center;
    margin-top: 18px;
    font-size: 8px;
    color: #64748b;
}

.no-data {
    text-align: center;
    padding: 15px;
}

</style>

</head>

<body>

<div class="header">

    <h1>
        Library Management System
    </h1>

    <h2>
        ' . $safePdfTitle . '
    </h2>

    <h3>
        ' . $safePdfSubTitle . '
    </h3>

</div>


<table class="info-table">

    <tr>

        <td class="info-label">
            Report Type
        </td>

        <td>
';

    if ($report_type === 'all') {

        $pdfHtml .= '
            All Users
        ';

    } else {

        $pdfHtml .= '
            Selected Role
        ';
    }

    $pdfHtml .= '

        </td>

    </tr>
';

    /*
    |--------------------------------------------------------------------------
    | Selected Role
    |--------------------------------------------------------------------------
    */

    if ($report_type === 'role') {

        $pdfHtml .= '

    <tr>

        <td class="info-label">
            Role
        </td>

        <td>
            ' .
            htmlspecialchars(
                ucfirst($role),
                ENT_QUOTES,
                'UTF-8'
            )
            . '
        </td>

    </tr>

        ';
    }

    /*
    |--------------------------------------------------------------------------
    | Generated Date
    |--------------------------------------------------------------------------
    */

    $pdfHtml .= '

    <tr>

        <td class="info-label">
            Generated On
        </td>

        <td>
            ' .
            htmlspecialchars(
                date("d M Y h:i A"),
                ENT_QUOTES,
                'UTF-8'
            )
            . '
        </td>

    </tr>

</table>


<table class="summary">

    <tr>

        <td>

            <span class="summary-number">
                ' . $pdfTotalUsers . '
            </span>

            <span class="summary-label">
                Total Users
            </span>

        </td>

        <td>

            <span class="summary-number">
                ' . $pdfTotalAdmins . '
            </span>

            <span class="summary-label">
                Administrators
            </span>

        </td>

        <td>

            <span class="summary-number">
                ' . $pdfTotalNormalUsers . '
            </span>

            <span class="summary-label">
                Normal Users
            </span>

        </td>

    </tr>

</table>


<table class="report-table">

    <thead>

        <tr>

            <th>#</th>

            <th>User ID</th>

            <th>Name</th>

            <th>Email</th>

            <th>Phone</th>

            <th>Role</th>

            <th>Registration Date</th>

        </tr>

    </thead>

    <tbody>
';

    /*
    |--------------------------------------------------------------------------
    | PDF USER TABLE
    |--------------------------------------------------------------------------
    */

    if (empty($pdfUsers)) {

        $pdfHtml .= '

        <tr>

            <td
                colspan="7"
                class="no-data"
            >
                No users found.
            </td>

        </tr>

        ';

    } else {

        foreach (
            $pdfUsers as $index => $pdfUser
        ) {

            $registrationDate =
                !empty($pdfUser['created_at'])
                    ? date(
                        "d M Y",
                        strtotime(
                            $pdfUser['created_at']
                        )
                    )
                    : 'N/A';

            $roleClass =
                $pdfUser['role'] === 'admin'
                    ? 'admin-role'
                    : 'user-role';

            $pdfHtml .= '

        <tr>

            <td>
                ' . ($index + 1) . '
            </td>

            <td>
                ' .
                (int)$pdfUser['id']
                . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $pdfUser['name'],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $pdfUser['email'],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td>
                ' .
                htmlspecialchars(
                    $pdfUser['phone'] ?? 'N/A',
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td class="' . $roleClass . '">
                ' .
                htmlspecialchars(
                    ucfirst($pdfUser['role']),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </td>

            <td>
                ' . $registrationDate . '
            </td>

        </tr>

            ';
        }
    }

    $pdfHtml .= '

    </tbody>

</table>


<div class="footer">

    Library Management System
    |
    ' . $safePdfTitle . '
    |
    Generated automatically

</div>


</body>

</html>
';

    /*
    |--------------------------------------------------------------------------
    | Generate PDF
    |--------------------------------------------------------------------------
    */

    $dompdf->loadHtml(
        $pdfHtml,
        'UTF-8'
    );

    $dompdf->setPaper(
        'A4',
        'landscape'
    );

    $dompdf->render();

    /*
    |--------------------------------------------------------------------------
    | PDF File Name
    |--------------------------------------------------------------------------
    */

    if ($report_type === 'all') {

        $fileName =
            'all_users_report_' .
            date('Y-m-d_H-i-s') .
            '.pdf';

    } else {

        $safeRole =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                $role
            );

        $safeRole =
            trim(
                $safeRole,
                '_'
            );

        if ($safeRole === '') {
            $safeRole = 'role';
        }

        $fileName =
            $safeRole .
            '_users_report_' .
            date('Y-m-d_H-i-s') .
            '.pdf';
    }

    /*
    |--------------------------------------------------------------------------
    | Download PDF
    |--------------------------------------------------------------------------
    */

    $dompdf->stream(
        $fileName,
        [
            'Attachment' => true
        ]
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| PDF URLs
|--------------------------------------------------------------------------
*/

/*
| ALL USERS PDF
*/

$allPdfQuery = http_build_query(
    [
        'download' => 'pdf',
        'report_type' => 'all'
    ]
);

/*
| SELECTED ROLE PDF
*/

$rolePdfQuery = http_build_query(
    [
        'download' => 'pdf',
        'report_type' => 'role',
        'role' => $role
    ]
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        User Report | Library Management System
    </title>

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css"
    >

    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/admin.css"
    >

    <style>

        /* =========================================================
           REPORT PAGE
        ========================================================= */

        .report-page {
            padding: 30px;
        }

        /* =========================================================
           ADMIN NAVBAR
        ========================================================= */

        .admin-navbar {

            width: 100%;
            min-height: 76px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            gap: 20px;
            box-sizing: border-box;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow:
                0 3px 15px
                rgba(15, 23, 42, 0.05);

        }

        .navbar-left {

            display: flex;
            align-items: center;
            min-width: 0;

        }

        .navbar-title h5 {

            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;

        }

        .navbar-title span {

            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;

        }

        .navbar-title span i {

            font-size: 12px;

        }

        .navbar-right {

            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex: 0 0 auto;
            min-width: max-content;
            white-space: nowrap;

        }

        .notification-btn,
        .theme-toggle-btn {

            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #ffffff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            font-size: 17px;
            transition: all 0.2s ease;
            padding: 0;

        }

        .notification-btn:hover,
        .theme-toggle-btn:hover {

            background: #f8fafc;
            border-color: #cbd5e1;
            color: #2563eb;

        }

        .header-divider {

            width: 1px;
            height: 34px;
            background: #e2e8f0;
            margin: 0 4px;

        }

        .nav-admin {

            display: flex;
            align-items: center;
            gap: 10px;

        }

        .nav-avatar {

            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex: 0 0 40px;
            box-shadow:
                0 3px 10px
                rgba(37,99,235,.12);

        }

        .nav-avatar i {

            color: #2563eb;
            font-size: 15px;

        }

        .nav-admin-info {

            display: flex;
            flex-direction: column;
            line-height: 1.1;

        }

        .nav-admin-info strong {

            color: #1e293b;
            font-size: 12px;
            font-weight: 700;

        }

        .nav-admin-info small {

            color: #64748b;
            font-size: 10px;
            margin-top: 3px;

        }

        .admin-logout-btn {

            min-height: 40px;
            padding: 0 13px;
            border: 1px solid #fecaca;
            border-radius: 9px;
            background: #fef2f2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.2s ease;

        }

        .admin-logout-btn:hover {

            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;

        }

        /* =========================================================
           REPORT HEADER
        ========================================================= */

        .report-header {

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;

        }

        .report-title h2 {

            margin: 0;
            color: #1e293b;
            font-size: 28px;
            font-weight: 700;

        }

        .report-title p {

            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;

        }

        /* =========================================================
           PDF BUTTONS
        ========================================================= */

        .report-actions {

            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;

        }

        .download-btn {

            min-height: 42px;
            padding: 0 15px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            transition: all .2s ease;

        }

        .download-all-btn {

            background: #2563eb;
            border: 1px solid #2563eb;
            color: #ffffff;

        }

        .download-all-btn:hover {

            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;

        }

        .download-role-btn {

            background: #059669;
            border: 1px solid #059669;
            color: #ffffff;

        }

        .download-role-btn:hover {

            background: #047857;
            border-color: #047857;
            color: #ffffff;

        }

        .download-disabled {

            background: #cbd5e1;
            border: 1px solid #cbd5e1;
            color: #64748b;
            cursor: not-allowed;

        }

        /* =========================================================
           SUMMARY
        ========================================================= */

        .report-summary {

            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 25px;

        }

        .summary-card {

            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 18px;
            box-shadow:
                0 4px 15px
                rgba(15, 23, 42, .05);

        }

        .summary-card .icon {

            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            margin-bottom: 10px;

        }

        .summary-card:nth-child(2) .icon {

            background: #fef2f2;
            color: #dc2626;

        }

        .summary-card:nth-child(3) .icon {

            background: #ecfdf5;
            color: #059669;

        }

        .summary-card h3 {

            margin: 0;
            font-size: 25px;
            font-weight: 700;
            color: #1e293b;

        }

        .summary-card p {

            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;

        }

        /* =========================================================
           FILTER
        ========================================================= */

        .filter-card {

            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow:
                0 4px 15px
                rgba(15, 23, 42, .04);

        }

        .filter-card form {

            display: grid;
            grid-template-columns:
                minmax(0,1fr)
                220px
                auto
                auto;
            gap: 12px;
            align-items: end;

        }

        .filter-group label {

            display: block;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;

        }

        .filter-group input,
        .filter-group select {

            width: 100%;
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
            font-size: 13px;
            background: #fff;
            color: #334155;

        }

        .filter-group input:focus,
        .filter-group select:focus {

            border-color: #2563eb;
            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, .10);

        }

        .search-btn,
        .reset-btn {

            height: 42px;
            padding: 0 15px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            white-space: nowrap;

        }

        .search-btn {

            background: #2563eb;
            color: #fff;
            cursor: pointer;

        }

        .search-btn:hover {

            background: #1d4ed8;
            color: #fff;

        }

        .reset-btn {

            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;

        }

        .reset-btn:hover {

            background: #e2e8f0;
            color: #334155;

        }

        .selected-role-info {

            margin-top: 14px;
            padding: 10px 13px;
            border-radius: 8px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;

        }

        /* =========================================================
           TABLE
        ========================================================= */

        .table-card {

            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            overflow: hidden;
            box-shadow:
                0 4px 15px
                rgba(15, 23, 42, .04);

        }

        .table-header {

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 18px 20px;
            border-bottom: 1px solid #e2e8f0;

        }

        .table-header h4 {

            margin: 0;
            color: #1e293b;
            font-size: 17px;
            font-weight: 700;

        }

        .table-header span {

            color: #64748b;
            font-size: 13px;

        }

        .table-responsive {

            overflow-x: auto;

        }

        .report-table {

            width: 100%;
            border-collapse: collapse;
            min-width: 800px;

        }

        .report-table th {

            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            padding: 13px 15px;
            text-align: left;
            white-space: nowrap;

        }

        .report-table td {

            padding: 14px 15px;
            border-top: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;

        }

        .report-table tbody tr:hover {

            background: #f8fafc;

        }

        .user-name {

            font-weight: 700;
            color: #1e293b;

        }

        /* =========================================================
           ROLE BADGES
        ========================================================= */

        .role-badge {

            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;

        }

        .role-admin {

            background: #fef2f2;
            color: #dc2626;

        }

        .role-user {

            background: #eff6ff;
            color: #2563eb;

        }

        /* =========================================================
           EMPTY
        ========================================================= */

        .empty-report {

            text-align: center;
            padding: 60px 20px;
            color: #64748b;

        }

        .empty-report i {

            display: block;
            font-size: 50px;
            color: #94a3b8;
            margin-bottom: 12px;

        }

        .empty-report h4 {

            color: #334155;
            font-weight: 700;
            margin-bottom: 6px;

        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .report-footer {

            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;

        }

        /* =========================================================
           DARK MODE
        ========================================================= */

        body.library-dark-mode {

            background: #0f172a !important;
            color: #e2e8f0 !important;

        }

        body.library-dark-mode .admin-main {

            background: #0f172a !important;

        }

        body.library-dark-mode .admin-navbar {

            background: #1e293b !important;
            border-bottom-color: #334155 !important;

        }

        body.library-dark-mode .navbar-title h5 {

            color: #f8fafc !important;

        }

        body.library-dark-mode .navbar-title span,
        body.library-dark-mode .navbar-title span i {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .notification-btn,
        body.library-dark-mode .theme-toggle-btn {

            background: #273449 !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;

        }

        body.library-dark-mode .theme-toggle-btn {

            color: #facc15 !important;

        }

        body.library-dark-mode .header-divider {

            background: #475569 !important;

        }

        body.library-dark-mode .nav-avatar {

            background: #334155 !important;
            color: #93c5fd !important;

        }

        body.library-dark-mode .nav-avatar i {

            color: #93c5fd !important;

        }

        body.library-dark-mode .nav-admin-info strong {

            color: #f8fafc !important;

        }

        body.library-dark-mode .nav-admin-info small {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .admin-logout-btn {

            background: #3f1d2a !important;
            border-color: #7f1d3c !important;
            color: #fb7185 !important;

        }

        body.library-dark-mode .report-title h2 {

            color: #f8fafc !important;

        }

        body.library-dark-mode .report-title p {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .summary-card,
        body.library-dark-mode .filter-card,
        body.library-dark-mode .table-card {

            background: #1e293b !important;
            border-color: #334155 !important;

        }

        body.library-dark-mode .summary-card h3 {

            color: #f8fafc !important;

        }

        body.library-dark-mode .summary-card p {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .summary-card .icon {

            background: #273449 !important;
            color: #60a5fa !important;

        }

        body.library-dark-mode .filter-group label {

            color: #cbd5e1 !important;

        }

        body.library-dark-mode .filter-group input,
        body.library-dark-mode .filter-group select {

            background: #111827 !important;
            border-color: #475569 !important;
            color: #f8fafc !important;

        }

        body.library-dark-mode .filter-group input::placeholder {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .selected-role-info {

            background: #172554 !important;
            border-color: #1e40af !important;
            color: #93c5fd !important;

        }

        body.library-dark-mode .reset-btn {

            background: #334155 !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;

        }

        body.library-dark-mode .table-header {

            border-bottom-color: #334155 !important;

        }

        body.library-dark-mode .table-header h4 {

            color: #f8fafc !important;

        }

        body.library-dark-mode .table-header span {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .report-table th {

            background: #273449 !important;
            color: #f8fafc !important;

        }

        body.library-dark-mode .report-table td {

            background: #1e293b !important;
            color: #cbd5e1 !important;
            border-top-color: #334155 !important;

        }

        body.library-dark-mode
        .report-table tbody tr:hover td {

            background: #273449 !important;

        }

        body.library-dark-mode .user-name {

            color: #f8fafc !important;

        }

        body.library-dark-mode .role-user {

            background: #273449 !important;
            color: #93c5fd !important;

        }

        body.library-dark-mode .role-admin {

            background: #3f1d2a !important;
            color: #fb7185 !important;

        }

        body.library-dark-mode .empty-report {

            color: #94a3b8 !important;

        }

        body.library-dark-mode .empty-report h4 {

            color: #f8fafc !important;

        }

        body.library-dark-mode .report-footer {

            border-top-color: #334155 !important;
            color: #94a3b8 !important;

        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .filter-card form {

                grid-template-columns: 1fr 1fr;

            }

            .report-summary {

                grid-template-columns: repeat(2, 1fr);

            }

        }

        @media (max-width: 800px) {

            .admin-navbar {

                padding: 0 15px;

            }

            .nav-admin-info {

                display: none;

            }

            .admin-logout-btn span {

                display: none;

            }

            .admin-logout-btn {

                width: 40px;
                padding: 0;

            }

            .report-actions {

                width: 100%;
                justify-content: flex-start;

            }

        }

        @media (max-width: 600px) {

            .report-page {

                padding: 20px 15px;

            }

            .admin-navbar {

                min-height: 68px;

            }

            .navbar-title span {

                display: none;

            }

            .notification-btn,
            .theme-toggle-btn,
            .admin-logout-btn {

                width: 37px;
                height: 37px;
                flex-basis: 37px;

            }

            .nav-avatar {

                width: 34px;
                height: 34px;
                flex-basis: 34px;

            }

            .report-title h2 {

                font-size: 23px;

            }

            .report-summary {

                grid-template-columns: 1fr;

            }

            .filter-card form {

                grid-template-columns: 1fr;

            }

            .report-actions {

                flex-direction: column;
                align-items: stretch;

            }

            .download-btn {

                width: 100%;

            }

        }

        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            .admin-sidebar,
            .sidebar,
            .navbar,
            .admin-navbar,
            .filter-card,
            .report-actions,
            .no-print {

                display: none !important;

            }

            .report-page {

                padding: 0;

            }

            .summary-card,
            .table-card {

                box-shadow: none !important;

            }

            .table-card {

                border: 1px solid #ddd;

            }

            .report-table {

                min-width: 0;

            }

            body {

                background: #ffffff !important;

            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

    <?php include "../../includes/admin_sidebar.php"; ?>


    <div class="admin-main">

        <!-- =====================================================
             NAVBAR
        ====================================================== -->

     
        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->

        <main class="report-page">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="report-header">

                <div class="report-title">

                    <h2>
                        User Report
                    </h2>

                    <p>
                        Download all users or selected role users as PDF.
                    </p>

                </div>


                <!-- =================================================
                     TWO PDF OPTIONS
                ================================================== -->

                <div class="report-actions">


                    <!-- ALL USERS PDF -->

                    <a
                        href="?<?php echo htmlspecialchars($allPdfQuery); ?>"
                        class="
                            download-btn
                            download-all-btn
                            no-print
                        "
                        title="Download all users PDF"
                    >

                        <i class="bi bi-file-earmark-pdf-fill"></i>

                        Download All PDF

                    </a>


                    <!-- SELECTED ROLE PDF -->

                    <?php if (
                        $role === 'admin' ||
                        $role === 'user'
                    ): ?>

                        <a
                            href="?<?php echo htmlspecialchars($rolePdfQuery); ?>"
                            class="
                                download-btn
                                download-role-btn
                                no-print
                            "
                            title="Download selected role PDF"
                        >

                            <i class="bi bi-person-badge-fill"></i>

                            Download
                            <?php echo htmlspecialchars(ucfirst($role)); ?>
                            PDF

                        </a>

                    <?php else: ?>

                        <span
                            class="
                                download-btn
                                download-disabled
                                no-print
                            "
                            title="Select a role first"
                        >

                            <i class="bi bi-person-badge"></i>

                            Select Role PDF

                        </span>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="report-summary">


                <!-- TOTAL USERS -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-people"></i>

                    </div>

                    <h3>

                        <?php echo $totalUsers; ?>

                    </h3>

                    <p>
                        Total Users
                    </p>

                </div>


                <!-- ADMINISTRATORS -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-shield-lock"></i>

                    </div>

                    <h3>

                        <?php echo $totalAdmins; ?>

                    </h3>

                    <p>
                        Administrators
                    </p>

                </div>


                <!-- NORMAL USERS -->

                <div class="summary-card">

                    <div class="icon">

                        <i class="bi bi-person-check"></i>

                    </div>

                    <h3>

                        <?php echo $totalNormalUsers; ?>

                    </h3>

                    <p>
                        Normal Users
                    </p>

                </div>


            </div>


            <!-- =================================================
                 FILTERS
            ================================================== -->

            <div class="filter-card no-print">

                <form method="GET">

                    <!-- SEARCH -->

                    <div class="filter-group">

                        <label for="search">
                            Search User
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php
                            echo htmlspecialchars($search);
                            ?>"
                            placeholder="Search by name, email or phone..."
                        >

                    </div>


                    <!-- ROLE -->

                    <div class="filter-group">

                        <label for="role">
                            Role
                        </label>

                        <select
                            id="role"
                            name="role"
                        >

                            <option value="">
                                All Roles
                            </option>

                            <option
                                value="admin"
                                <?php
                                echo
                                    $role === 'admin'
                                        ? 'selected'
                                        : '';
                                ?>
                            >
                                Admin
                            </option>

                            <option
                                value="user"
                                <?php
                                echo
                                    $role === 'user'
                                        ? 'selected'
                                        : '';
                                ?>
                            >
                                User
                            </option>

                        </select>

                    </div>


                    <!-- SEARCH BUTTON -->

                    <button
                        type="submit"
                        class="search-btn"
                    >

                        <i class="bi bi-search"></i>

                        Search

                    </button>


                    <!-- RESET -->

                    <a
                        href="users_report.php"
                        class="reset-btn"
                    >

                        <i class="bi bi-arrow-clockwise"></i>

                        Reset

                    </a>

                </form>


                <?php if (
                    $role === 'admin' ||
                    $role === 'user'
                ): ?>

                    <div class="selected-role-info">

                        <i class="bi bi-check-circle-fill"></i>

                        Selected Role:

                        <?php
                        echo htmlspecialchars(
                            ucfirst($role)
                        );
                        ?>

                        — Role PDF contains only
                        <?php
                        echo htmlspecialchars(
                            ucfirst($role)
                        );
                        ?>
                        users.

                    </div>

                <?php endif; ?>


            </div>


            <!-- =================================================
                 USER TABLE
            ================================================== -->

            <div class="table-card">


                <div class="table-header">

                    <h4>
                        User Records
                    </h4>

                    <span>

                        <?php echo count($users); ?>

                        records found

                    </span>

                </div>


                <?php if (empty($users)): ?>


                    <div class="empty-report">

                        <i class="bi bi-people"></i>

                        <h4>
                            No Users Found
                        </h4>

                        <p>
                            No user records match your search criteria.
                        </p>

                    </div>


                <?php else: ?>


                    <div class="table-responsive">

                        <table class="report-table">

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Name
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Phone
                                    </th>

                                    <th>
                                        Role
                                    </th>

                                    <th>
                                        Registration Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach (
                                $users
                                as $index =>
                                $user
                            ): ?>

                                <tr>

                                    <td>

                                        <?php
                                        echo $index + 1;
                                        ?>

                                    </td>


                                    <td>

                                        <div
                                            class="user-name"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $user['name']
                                            );

                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $user['email']
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo htmlspecialchars(
                                            $user['phone']
                                            ?? 'N/A'
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                role-badge
                                                <?php
                                                echo
                                                    $user['role']
                                                    === 'admin'
                                                        ? 'role-admin'
                                                        : 'role-user';
                                                ?>
                                            "
                                        >

                                            <i
                                                class="
                                                    bi
                                                    <?php
                                                    echo
                                                        $user['role']
                                                        === 'admin'
                                                            ? 'bi-shield-fill-check'
                                                            : 'bi-person-fill';
                                                    ?>
                                                "
                                            ></i>

                                            &nbsp;

                                            <?php

                                            echo htmlspecialchars(
                                                ucfirst(
                                                    $user['role']
                                                )
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        echo !empty(
                                            $user['created_at']
                                        )

                                            ? date(
                                                "d M Y",
                                                strtotime(
                                                    $user['created_at']
                                                )
                                            )

                                            : 'N/A';

                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <div class="report-footer">

                        Generated on

                        <?php

                        echo date(
                            "d M Y h:i A"
                        );

                        ?>

                        &nbsp; | &nbsp;

                        Library Management System

                    </div>


                <?php endif; ?>


            </div>


        </main>

    </div>

</div>


<!-- =========================================================
     SIDEBAR TOGGLE
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const sidebarToggle =
            document.getElementById(
                "sidebarToggle"
            );

        const adminSidebar =
            document.getElementById(
                "adminSidebar"
            );

        if (
            sidebarToggle &&
            adminSidebar
        ) {

            sidebarToggle.addEventListener(
                "click",
                function () {

                    adminSidebar.classList.toggle(
                        "show"
                    );

                }
            );

            document.addEventListener(
                "click",
                function (event) {

                    if (

                        window.innerWidth <= 992 &&

                        adminSidebar.classList.contains(
                            "show"
                        ) &&

                        !adminSidebar.contains(
                            event.target
                        ) &&

                        !sidebarToggle.contains(
                            event.target
                        )

                    ) {

                        adminSidebar.classList.remove(
                            "show"
                        );

                    }

                }
            );

        }

    }
);

</script>


<!-- =========================================================
     GLOBAL THEME
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const body =
            document.body;

        const themeButton =
            document.getElementById(
                "adminThemeToggle"
            );

        if (!themeButton) {
            return;
        }

        function updateThemeButton() {

            const isDark =
                body.classList.contains(
                    "library-dark-mode"
                );

            themeButton.innerHTML =
                isDark
                    ? '<i class="bi bi-sun-fill"></i>'
                    : '<i class="bi bi-moon-fill"></i>';

            themeButton.title =
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode";

            themeButton.setAttribute(
                "aria-label",
                isDark
                    ? "Switch to Light Mode"
                    : "Switch to Dark Mode"
            );
        }

        const savedTheme =
            localStorage.getItem(
                "library_theme"
            );

        if (savedTheme === "dark") {

            body.classList.add(
                "library-dark-mode"
            );

        } else {

            body.classList.remove(
                "library-dark-mode"
            );

        }

        updateThemeButton();

        themeButton.addEventListener(
            "click",
            function () {

                body.classList.toggle(
                    "library-dark-mode"
                );

                const isDark =
                    body.classList.contains(
                        "library-dark-mode"
                    );

                localStorage.setItem(
                    "library_theme",
                    isDark
                        ? "dark"
                        : "light"
                );

                updateThemeButton();

            }
        );

    }
);

</script>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>
</body>

</html>

