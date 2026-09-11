<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>404 - Page Not Found</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        body {

            background: #f5f7fb;

            font-family: Arial, sans-serif;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .error-container {

            text-align: center;

            padding: 40px;

        }


        .error-code {

            font-size: 120px;

            font-weight: bold;

            color: #0d6efd;

            line-height: 1;

        }


        .error-icon {

            font-size: 70px;

            color: #6c757d;

            margin-bottom: 20px;

        }


        .btn-home {

            padding: 12px 30px;

            border-radius: 8px;

            font-weight: bold;

        }

    </style>

</head>


<body>


<div class="error-container">


    <div class="error-icon">

        <i class="bi bi-exclamation-triangle"></i>

    </div>


    <div class="error-code">

        404

    </div>


    <h2 class="fw-bold mt-3">

        Page Not Found

    </h2>


    <p class="text-muted">

        Sorry, the page you are looking for

        does not exist or has been moved.

    </p>


    <a href="index.php"
       class="btn btn-primary btn-home mt-3">

        <i class="bi bi-house"></i>

        Back to Home

    </a>


</div>


</body>

</html>