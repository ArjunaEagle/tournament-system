<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) json_response(false, 'Invalid match ID', null, 422);
$s = $pdo->prepare("SELECT m.*,p1.name participant1_name,p1.logo participant1_logo,p2.name participant2_name,p2.logo participant2_logo FROM matches m LEFT JOIN participants p1 ON p1.id=m.participant1_id LEFT JOIN participants p2 ON p2.id=m.participant2_id WHERE m.id=?");
$s->execute([$id]); $m = $s->fetch();
if (!$m) json_response(false, 'Match not found', null, 404);
$ev = $pdo->prepare("SELECT me.*,p.name participant_name FROM match_events me LEFT JOIN participants p ON p.id=me.participant_id WHERE me.match_id=? ORDER BY me.created_at DESC LIMIT 50");
$ev->execute([$id]); $m['events'] = $ev->fetchAll();
json_response(true, 'Match loaded', $m);

