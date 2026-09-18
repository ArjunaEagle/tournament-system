<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="<?= BASE_URL ?>/admin/"><span class="brand-mark"><i class="fa-solid fa-trophy"></i></span><span>ARENA<span>FLOW</span></span></a>
  <div class="sidebar-user"><div class="avatar"><?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?></div><div><strong><?= e($_SESSION['user']['name']) ?></strong><small><?= e(ucwords(str_replace('_',' ', $_SESSION['user']['role']))) ?></small></div></div>
  <nav>
  <?php
  $links = [
    'index.php'=>['gauge-high','Dashboard'],'tournaments.php'=>['trophy','Tournament'],
    'participants.php'=>['people-group','Participants'],'matches.php'=>['calendar-days','Matches'],
    'bracket.php'=>['sitemap','Bracket'],'scoring.php'=>['stopwatch','Scoring'],
    'standings.php'=>['ranking-star','Standings'],'users.php'=>['user-shield','Users'],
    'settings.php'=>['gear','Settings']
  ];
  foreach ($links as $file=>$item): ?>
    <a class="<?= $current === $file ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/<?= $file ?>"><i class="fa-solid fa-<?= $item[0] ?>"></i><?= $item[1] ?></a>
  <?php endforeach; ?>
  </nav>
  <a class="logout-link" href="<?= BASE_URL ?>/admin/logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Logout</a>
</aside>
<div class="admin-shell">
  <header class="admin-topbar"><button class="sidebar-toggle" aria-label="Buka sidebar"><i class="fa-solid fa-bars"></i></button><div><small>Competition operations</small><h1><?= e($pageTitle) ?></h1></div><a class="icon-btn" href="<?= BASE_URL ?>/" target="_blank" title="Lihat website publik"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></header>
  <main class="admin-main">

