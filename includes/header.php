<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$isAdmin = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Sistem manajemen turnamen dan live scoring">
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?> · <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <?php if (basename($_SERVER['PHP_SELF']) === 'bracket.php'): ?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bracket.css"><?php endif; ?>
  <script>window.APP_BASE = <?= json_encode(BASE_URL) ?>;</script>
  <script defer src="<?= BASE_URL ?>/assets/js/app.js"></script>
</head>
<body class="<?= $isAdmin ? 'admin-body' : '' ?>">
<?php if (!$isAdmin): ?>
<header class="public-nav">
  <a class="brand" href="<?= BASE_URL ?>/"><span class="brand-mark"><i class="fa-solid fa-trophy"></i></span><span>ARENA<span>FLOW</span></span></a>
  <button class="nav-toggle" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
  <nav>
    <a href="<?= BASE_URL ?>/">Home</a><a href="<?= BASE_URL ?>/bracket.php">Bracket</a><a href="<?= BASE_URL ?>/matches.php">Matches</a><a href="<?= BASE_URL ?>/standings.php">Standings</a><a href="<?= BASE_URL ?>/participants.php">Participants</a><a href="<?= BASE_URL ?>/results.php">Results</a>
  </nav>
</header>
<?php endif; ?>
