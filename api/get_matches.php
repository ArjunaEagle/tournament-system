<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
$t = active_tournament($pdo);
if (!$t) json_response(true, 'No active tournament', []);
$status = $_GET['status'] ?? 'all';
$sql = "SELECT m.*,p1.name participant1_name,p2.name participant2_name FROM matches m LEFT JOIN participants p1 ON p1.id=m.participant1_id LEFT JOIN participants p2 ON p2.id=m.participant2_id WHERE m.tournament_id=?";
$params = [$t['id']];
if (in_array($status, ['scheduled','live','paused','finished'], true)) { $sql .= ' AND m.status=?'; $params[] = $status; }
$sql .= ' ORDER BY m.round_number,m.match_number';
$s = $pdo->prepare($sql); $s->execute($params);
json_response(true, 'Matches loaded', $s->fetchAll());

