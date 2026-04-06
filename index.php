<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Movie Upvote Test</title>

    <link rel="stylesheet" href="css/style.css?v=6">
</head>

<body>
<header><h1>Upvote Movies!</h1></header>

<main>
    <!-- Error container -->
    <div id="error" class="error" role="alert" aria-live="assertive"></div>

    <!-- Movies Container -->
    <section class="movies" aria-label="Movie list">
        <!-- Movies injected via JS -->
    </section>
</main>

<script
        src="https://code.jquery.com/jquery-4.0.0.min.js"
        integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao="
        crossorigin="anonymous"></script>
<script src="/js/script.js?v=4"></script>
</body>
</html>