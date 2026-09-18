<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
$t = active_tournament($pdo);
if (!$t) json_response(true, 'No active tournament', []);
$sql = "SELECT p.id,p.name,COUNT(m.id) played,COALESCE(SUM(m.winner_id=p.id),0) wins,COALESCE(SUM(m.winner_id<>p.id),0) losses,COALESCE(SUM(CASE WHEN m.participant1_id=p.id THEN m.score1 ELSE m.score2 END),0) score_for,COALESCE(SUM(CASE WHEN m.participant1_id=p.id THEN m.score2 ELSE m.score1 END),0) score_against FROM participants p LEFT JOIN matches m ON m.status='finished' AND (m.participant1_id=p.id OR m.participant2_id=p.id) WHERE p.tournament_id=? GROUP BY p.id ORDER BY wins DESC,(score_for-score_against) DESC";
$s = $pdo->prepare($sql); $s->execute([$t['id']]);
json_response(true, 'Standings loaded', $s->fetchAll());

