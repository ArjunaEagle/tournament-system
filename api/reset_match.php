<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_api_auth(['super_admin','admin']);
$d=json_decode(file_get_contents('php://input'),true)?:$_POST;
verify_csrf($d['csrf_token']??($_SERVER['HTTP_X_CSRF_TOKEN']??null)); $id=(int)($d['match_id']??0);
$s=$pdo->prepare("UPDATE matches SET score1=0,score2=0,status='scheduled',started_at=NULL,updated_at=NOW() WHERE id=? AND status<>'finished'");$s->execute([$id]);
if(!$s->rowCount())json_response(false,'Finished matches cannot be reset',null,422);
$pdo->prepare('INSERT INTO match_events (match_id,event_type) VALUES (?,"reset")')->execute([$id]);audit_log($pdo,'reset_match','match',$id,'Reset score to 0-0');
json_response(true,'Score reset',['match_id'=>$id]);

