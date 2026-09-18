<?php $pageTitle='Standings';require_once dirname(__DIR__).'/includes/header.php';require_once dirname(__DIR__).'/includes/auth.php';require_auth();require dirname(__DIR__).'/includes/sidebar.php';?>
<section class="card"><div class="section-head"><div><div class="eyebrow">Public statistics</div><h2>Calculated standings</h2></div><a class="btn" href="<?=BASE_URL?>/standings.php" target="_blank">Open standings</a></div><p style="color:var(--text-secondary)">Standings are calculated directly from finished match scores, so there is no duplicate data to maintain.</p></section>
<?php require dirname(__DIR__).'/includes/admin_footer.php';?>

