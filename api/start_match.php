<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_api_auth();
$d=json_decode(file_get_contents('php://input'),true)?:$_POST;
verify_csrf($d['csrf_token']??($_SERVER['HTTP_X_CSRF_TOKEN']??null)); $id=(int)($d['match_id']??0);
$s=$pdo->prepare('SELECT * FROM matches WHERE id=?'); $s->execute([$id]); $m=$s->fetch();
if(!$m)json_response(false,'Match not found',null,404);
if($_SESSION['user']['role']==='judge'&&(int)$m['assigned_judge_id']!==(int)$_SESSION['user']['id'])json_response(false,'This match is not assigned to you',null,403);
if(!$m['participant1_id']||!$m['participant2_id'])json_response(false,'Both participants must be assigned',null,422);
$pdo->prepare("UPDATE matches SET status='live',started_at=COALESCE(started_at,NOW()),updated_at=NOW() WHERE id=? AND status IN ('scheduled','paused')")->execute([$id]);
$pdo->prepare('INSERT INTO match_events (match_id,event_type) VALUES (?,"start")')->execute([$id]); audit_log($pdo,'start_match','match',$id,'Started match');
json_response(true,'Match started',['match_id'=>$id]);

