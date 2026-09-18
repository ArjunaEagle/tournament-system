<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_api_auth();
$d=json_decode(file_get_contents('php://input'),true)?:$_POST;
verify_csrf($d['csrf_token']??($_SERVER['HTTP_X_CSRF_TOKEN']??null)); $id=(int)($d['match_id']??0);
if($_SESSION['user']['role']==='judge'){$s=$pdo->prepare('SELECT assigned_judge_id FROM matches WHERE id=?');$s->execute([$id]);if((int)$s->fetchColumn()!==(int)$_SESSION['user']['id'])json_response(false,'This match is not assigned to you',null,403);}
try{$result=finish_match($pdo,$id);json_response(true,'Match finished and winner advanced',$result);}catch(Throwable $e){json_response(false,$e->getMessage(),null,422);}
