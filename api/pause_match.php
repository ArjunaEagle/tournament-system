<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_api_auth();
$d=json_decode(file_get_contents('php://input'),true)?:$_POST;
verify_csrf($d['csrf_token']??($_SERVER['HTTP_X_CSRF_TOKEN']??null)); $id=(int)($d['match_id']??0);
$sql='UPDATE matches SET status="paused",updated_at=NOW() WHERE id=? AND status="live"'.($_SESSION['user']['role']==='judge'?' AND assigned_judge_id=?':'');
$params=[$id]; if($_SESSION['user']['role']==='judge')$params[]=$_SESSION['user']['id']; $s=$pdo->prepare($sql); $s->execute($params);
if(!$s->rowCount())json_response(false,'Unable to pause this match',null,422);
$pdo->prepare('INSERT INTO match_events (match_id,event_type) VALUES (?,"pause")')->execute([$id]); audit_log($pdo,'pause_match','match',$id,'Paused match');
json_response(true,'Match paused',['match_id'=>$id]);

