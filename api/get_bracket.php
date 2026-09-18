<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
$t = active_tournament($pdo);
if (!$t) json_response(true, 'No active tournament', []);
$s = $pdo->prepare("SELECT m.*,p1.name participant1_name,p1.seed participant1_seed,p2.name participant2_name,p2.seed participant2_seed FROM matches m LEFT JOIN participants p1 ON p1.id=m.participant1_id LEFT JOIN participants p2 ON p2.id=m.participant2_id WHERE m.tournament_id=? ORDER BY round_number,match_number");
$s->execute([$t['id']]); $rounds = [];
foreach ($s->fetchAll() as $m) $rounds[$m['round_number']][] = $m;
json_response(true, 'Bracket loaded', ['tournament'=>$t,'rounds'=>$rounds]);

