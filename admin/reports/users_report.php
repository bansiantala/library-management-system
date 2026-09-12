<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireAdmin();

$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';

/*
|--------------------------------------------------------------------------
| User Report Query
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

if ($role === 'admin' || $role === 'user') {

    $sql .= " AND role = ?";

    $params[] = $role;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("User report query failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Report | Library Management System</title>

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

        .report-page {
            padding: 30px;
        }

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

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: #2563eb;
            color: #fff;
            padding: 10px 16px;
            border-radius: 9px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

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
            box-shadow: 0 4px 15px rgba(15, 23, 42, .05);
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

        .filter-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
        }

        .filter-card form {
            display: grid;
            grid-template-columns: 1fr 220px auto auto;
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
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
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
        }

        .search-btn {
            background: #2563eb;
            color: #fff;
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

        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .04);
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

        .report-footer {
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 900px) {

            .report-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-card form {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 600px) {

            .report-page {
                padding: 20px 15px;
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

        }

        @media print {

            .admin-sidebar,
            .sidebar,
            .navbar,
            .admin-navbar,
            .filter-card,
            .print-btn,
            .no-print {
                display: none !important;
            }

            .report-page {
                padding: 0;
            }

            .summary-card,
            .table-card {
                box-shadow: none;
            }

            .table-card {
                border: 1px solid #ddd;
            }

            .report-table {
                min-width: 0;
            }

            body {
                background: #fff !important;
            }

        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- Admin Sidebar -->
    <?php include "../../includes/admin_sidebar.php"; ?>

    <div class="admin-main">

        <!-- Navbar -->
        <?php include "../../includes/navbar.php"; ?>

        <main class="report-page">

            <!-- Header -->

            <div class="report-header">

                <div class="report-title">

                    <h2>
                        <i class="bi bi-people-fill"></i>
                        User Report
                    </h2>

                    <p>
                        View and filter registered library users.
                    </p>

                </div>

                <button
                    type="button"
                    class="print-btn no-print"
                    onclick="window.print()"
                >
                    <i class="bi bi-printer"></i>
                    Print Report
                </button>

            </div>


            <!-- Summary -->

            <div class="report-summary">

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


            <!-- Filters -->

            <div class="filter-card no-print">

                <form method="GET">

                    <div class="filter-group">

                        <label for="search">
                            Search User
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by name, email or phone..."
                        >

                    </div>


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
                                <?php echo $role === 'admin' ? 'selected' : ''; ?>
                            >
                                Admin
                            </option>

                            <option
                                value="user"
                                <?php echo $role === 'user' ? 'selected' : ''; ?>
                            >
                                User
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="search-btn"
                    >
                        <i class="bi bi-search"></i>
                        Search
                    </button>


                    <a
                        href="users_report.php"
                        class="reset-btn"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                        Reset
                    </a>

                </form>

            </div>


            <!-- User Table -->

            <div class="table-card">

                <div class="table-header">

                    <h4>
                        User Records
                    </h4>

                    <span>
                        <?php echo count($users); ?> records found
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

                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Registration Date</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($users as $index => $user): ?>

                                <tr>

                                    <td>
                                        <?php echo $index + 1; ?>
                                    </td>

                                    <td>

                                        <div class="user-name">

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
                                            class="role-badge
                                            <?php
                                            echo $user['role'] === 'admin'
                                                ? 'role-admin'
                                                : 'role-user';
                                            ?>"
                                        >

                                            <i
                                                class="bi
                                                <?php
                                                echo $user['role'] === 'admin'
                                                    ? 'bi-shield-fill-check'
                                                    : 'bi-person-fill';
                                                ?>"
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
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $user['created_at']
                                            )
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                    <div class="report-footer">

                        Generated on
                        <?php echo date("d M Y h:i A"); ?>

                        &nbsp; | &nbsp;

                        Library Management System

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>