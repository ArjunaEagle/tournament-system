<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_api_auth();
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
verify_csrf($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));
$matchId=(int)($data['match_id']??0); $slot=(int)($data['slot']??0); $delta=isset($data['delta'])?(int)$data['delta']:null; $absolute=isset($data['score'])?(int)$data['score']:null;
if (!$matchId || !in_array($slot,[1,2],true) || ($absolute===null && !in_array($delta,[-1,1],true)) || ($absolute!==null && ($absolute<0 || $absolute>999))) json_response(false,'Invalid score request',null,422);
try {
    $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT * FROM matches WHERE id=? FOR UPDATE'); $s->execute([$matchId]); $m=$s->fetch();
    if (!$m) throw new RuntimeException('Match not found.');
    if ($_SESSION['user']['role']==='judge' && (int)$m['assigned_judge_id']!==(int)$_SESSION['user']['id']) throw new RuntimeException('This match is not assigned to you.');
    if ($m['status']!=='live') throw new RuntimeException('Start the match before changing score.');
    $column=$slot===1?'score1':'score2'; $participant=$slot===1?$m['participant1_id']:$m['participant2_id']; $old=(int)$m[$column]; $new=$absolute!==null?$absolute:max(0,$old+(int)$delta);
    $pdo->prepare("UPDATE matches SET {$column}=?,updated_at=NOW() WHERE id=?")->execute([$new,$matchId]);
    $pdo->prepare('INSERT INTO match_events (match_id,participant_id,event_type,score_change,score_after) VALUES (?,?,"score",?,?)')->execute([$matchId,$participant,$new-$old,$new]);
    audit_log($pdo,'update_score','match',$matchId,"Score slot {$slot}: {$old} → {$new}");
    $pdo->commit(); json_response(true,'Score updated',['score'=>$new]);
} catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); json_response(false,$e->getMessage(),null,422); }
