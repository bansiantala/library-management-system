<?php

require_once "../../config/auth.php";
require_once "../../config/database.php";

requireUser();

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


$sql = "SELECT
            books.*,
            categories.category_name
        FROM books
        INNER JOIN categories
            ON books.category_id = categories.id";

if ($search !== "") {

    $sql .= " WHERE
                books.title LIKE ?
                OR books.author LIKE ?
                OR books.isbn LIKE ?
                OR categories.category_name LIKE ?";

    $sql .= " ORDER BY books.title ASC";

    $stmt = $conn->prepare($sql);

    $search_value = "%" . $search . "%";

    $stmt->bind_param(
        "ssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $sql .= " ORDER BY books.title ASC";

    $result = $conn->query($sql);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Search Books</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/style.css">

    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/css/user.css">

</head>

<body>

<?php include "../../includes/user_sidebar.php"; ?>


<div class="user-main">

    <!-- Navbar -->

    <nav class="user-navbar">

        <div class="d-flex align-items-center gap-3">

            <button
                class="sidebar-toggle"
                onclick="toggleSidebar()">

                <i class="bi bi-list"></i>

            </button>

            <h5>Search Books</h5>

        </div>


        <div class="user-info">

            <div class="user-info-icon">

                <i class="bi bi-person-fill"></i>

            </div>

            <span>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>

        </div>

    </nav>


    <div class="dashboard-content">


        <div class="mb-4">

            <h2>
                Search Library Books
            </h2>

            <p class="text-muted">
                Search books by title, author, ISBN or category.
            </p>

        </div>


        <!-- Search Form -->

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-body p-4">

                <form method="GET">

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-search"></i>

                        </span>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search title, author, ISBN or category..."
                            value="<?php echo htmlspecialchars($search); ?>">

                        <button
                            type="submit"
                            class="btn btn-primary">

                            Search

                        </button>

                        <?php if ($search !== ""): ?>

                            <a
                                href="search.php"
                                class="btn btn-secondary">

                                Clear

                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>


        <!-- Search Result -->

        <?php if ($search !== ""): ?>

            <div class="mb-3">

                <strong>
                    Search results for:
                </strong>

                "<?php echo htmlspecialchars($search); ?>"

            </div>

        <?php endif; ?>


        <div class="row g-4">

            <?php if ($result->num_rows > 0): ?>

                <?php while ($book = $result->fetch_assoc()): ?>

                    <div class="col-xl-4 col-lg-6 col-md-6">

                        <div class="card h-100 border-0 shadow-sm">

                            <div class="card-body p-4">

                                <div class="text-center mb-3">

                                    <div
                                        class="mx-auto rounded-circle
                                               bg-primary bg-opacity-10
                                               d-flex align-items-center
                                               justify-content-center"
                                        style="width:75px;height:75px;">

                                        <i
                                            class="bi bi-book text-primary"
                                            style="font-size:32px;">
                                        </i>

                                    </div>

                                </div>


                                <h5 class="text-center fw-bold">

                                    <?php
                                    echo htmlspecialchars(
                                        $book['title']
                                    );
                                    ?>

                                </h5>


                                <p class="text-center text-muted">

                                    <?php
                                    echo htmlspecialchars(
                                        $book['author']
                                    );
                                    ?>

                                </p>


                                <div class="d-flex
                                            justify-content-between
                                            mb-2">

                                    <span class="text-muted">
                                        Category
                                    </span>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $book['category_name']
                                        );
                                        ?>
                                    </strong>

                                </div>


                                <div class="d-flex
                                            justify-content-between
                                            mb-3">

                                    <span class="text-muted">
                                        Status
                                    </span>

                                    <?php if (
                                        $book['available_quantity'] > 0
                                    ): ?>

                                        <span class="badge bg-success">

                                            Available

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">

                                            Out of Stock

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <a
                                    href="details.php?id=<?php
                                    echo $book['id'];
                                    ?>"
                                    class="btn btn-outline-primary w-100">

                                    <i class="bi bi-eye"></i>

                                    View Details

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">

                    <div class="card border-0 shadow-sm">

                        <div
                            class="card-body text-center py-5">

                            <i
                                class="bi bi-search"
                                style="font-size:50px;color:#94a3b8;">
                            </i>

                            <h4 class="mt-3">
                                No Books Found
                            </h4>

                            <p class="text-muted">
                                Try another title, author,
                                ISBN or category.
                            </p>

                            <a
                                href="search.php"
                                class="btn btn-primary">

                                View All Books

                            </a>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<script>

function toggleSidebar()
{
    document
        .querySelector('.user-sidebar')
        .classList.toggle('show');
}

</script>

</body>

</html>