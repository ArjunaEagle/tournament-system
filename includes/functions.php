<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): void
{
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(false, 'Invalid CSRF token', null, 419);
    }
}

function json_response(bool $success, string $message, mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function active_tournament(PDO $pdo): ?array
{
    $stmt = $pdo->query("SELECT * FROM tournaments ORDER BY FIELD(status,'live','upcoming','finished'), start_date DESC LIMIT 1");
    return $stmt->fetch() ?: null;
}

function tournament_stats(PDO $pdo, int $tournamentId): array
{
    $stmt = $pdo->prepare("SELECT COUNT(*) total, SUM(status='finished') finished, SUM(status='live') live, SUM(status='scheduled') scheduled FROM matches WHERE tournament_id=?");
    $stmt->execute([$tournamentId]);
    return $stmt->fetch() ?: ['total' => 0, 'finished' => 0, 'live' => 0, 'scheduled' => 0];
}

function audit_log(PDO $pdo, string $action, string $entityType, ?int $entityId, string $description): void
{
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,description) VALUES (?,?,?,?,?)');
    $stmt->execute([$_SESSION['user']['id'] ?? null, $action, $entityType, $entityId, $description]);
}

function round_label(int $roundNumber, int $totalRounds): string
{
    $remaining = $totalRounds - $roundNumber;
    return match ($remaining) {
        0 => 'Final',
        1 => 'Semi Final',
        2 => 'Quarter Final',
        default => 'Round of ' . (2 ** ($remaining + 1)),
    };
}

function next_power_of_two(int $number): int
{
    $power = 1;
    while ($power < $number) $power *= 2;
    return $power;
}

/**
 * Creates every match row first, links each match to its parent, then fills round one.
 * Byes are progressed immediately, so the persisted bracket is always renderable.
 */
function generate_bracket(PDO $pdo, int $tournamentId, string $mode = 'manual'): int
{
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT status,type FROM tournaments WHERE id=? FOR UPDATE');
        $lock->execute([$tournamentId]);
        $tournament = $lock->fetch();
        if (!$tournament) throw new RuntimeException('Tournament not found.');
        if ($tournament['type'] !== 'single_elimination') throw new RuntimeException('Only single elimination is currently supported.');

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM matches WHERE tournament_id=?');
        $countStmt->execute([$tournamentId]);
        if ((int)$countStmt->fetchColumn() > 0) throw new RuntimeException('Bracket already exists. Delete/reset matches before regenerating.');

        $sql = "SELECT id, seed FROM participants WHERE tournament_id=? AND status='active' ORDER BY seed IS NULL, seed, name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tournamentId]);
        $participants = $stmt->fetchAll();
        if (count($participants) < 2) throw new RuntimeException('At least two active participants are required.');
        if ($mode === 'random') shuffle($participants);

        $size = next_power_of_two(count($participants));
        $rounds = (int)log($size, 2);
        $matchIds = [];
        for ($r = 1; $r <= $rounds; $r++) {
            $matchesInRound = $size / (2 ** $r);
            for ($m = 1; $m <= $matchesInRound; $m++) {
                $insert = $pdo->prepare('INSERT INTO matches (tournament_id,round,round_number,match_number,status) VALUES (?,?,?,?,?)');
                $insert->execute([$tournamentId, round_label($r, $rounds), $r, $m, 'scheduled']);
                $matchIds[$r][$m] = (int)$pdo->lastInsertId();
            }
        }

        for ($r = 1; $r < $rounds; $r++) {
            foreach ($matchIds[$r] as $m => $id) {
                $nextNumber = (int)ceil($m / 2);
                $slot = $m % 2 === 1 ? 1 : 2;
                $pdo->prepare('UPDATE matches SET next_match_id=?, next_match_slot=? WHERE id=?')
                    ->execute([$matchIds[$r + 1][$nextNumber], $slot, $id]);
            }
        }

        // Standard seed placement keeps seeds 1 and 2 in opposite halves.
        // For eight slots this produces: 1v8, 4v5, 2v7, 3v6.
        $positions = [1, 2];
        $currentSize = 2;
        while ($currentSize < $size) {
            $sum = ($currentSize * 2) + 1;
            $expanded = [];
            foreach ($positions as $position) { $expanded[] = $position; $expanded[] = $sum - $position; }
            $positions = $expanded;
            $currentSize *= 2;
        }
        $slots = array_map(fn(int $position) => $participants[$position - 1]['id'] ?? null, $positions);
        $pairs = array_chunk($slots, 2);
        foreach ($pairs as $index => [$p1, $p2]) {
            $id = $matchIds[1][$index + 1];
            $pdo->prepare('UPDATE matches SET participant1_id=?, participant2_id=? WHERE id=?')->execute([$p1, $p2, $id]);
            if (($p1 && !$p2) || (!$p1 && $p2)) progress_bye($pdo, $id, (int)($p1 ?: $p2));
        }

        $pdo->prepare("UPDATE tournaments SET status='live', updated_at=NOW() WHERE id=?")->execute([$tournamentId]);
        audit_log($pdo, 'generate_bracket', 'tournament', $tournamentId, "Generated {$size}-slot single elimination bracket");
        $pdo->commit();
        return array_sum(array_map('count', $matchIds));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function progress_bye(PDO $pdo, int $matchId, int $winnerId): void
{
    $stmt = $pdo->prepare('SELECT next_match_id,next_match_slot FROM matches WHERE id=?');
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();
    $pdo->prepare("UPDATE matches SET winner_id=?, status='finished', finished_at=NOW() WHERE id=?")->execute([$winnerId, $matchId]);
    if ($match && $match['next_match_id']) {
        $column = (int)$match['next_match_slot'] === 1 ? 'participant1_id' : 'participant2_id';
        $pdo->prepare("UPDATE matches SET {$column}=? WHERE id=?")->execute([$winnerId, $match['next_match_id']]);
    }
}

/** Completes a match atomically and advances its winner to the configured next slot. */
function finish_match(PDO $pdo, int $matchId): array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM matches WHERE id=? FOR UPDATE');
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();
        if (!$match) throw new RuntimeException('Match not found.');
        if ($match['status'] === 'finished') throw new RuntimeException('Match is already finished.');
        if (!$match['participant1_id'] || !$match['participant2_id']) throw new RuntimeException('Both participants must be assigned.');
        if ((int)$match['score1'] === (int)$match['score2']) throw new RuntimeException('A single-elimination match cannot finish in a tie.');
        $winnerId = (int)$match['score1'] > (int)$match['score2'] ? (int)$match['participant1_id'] : (int)$match['participant2_id'];

        $pdo->prepare("UPDATE matches SET winner_id=?, status='finished', finished_at=NOW(), updated_at=NOW() WHERE id=?")
            ->execute([$winnerId, $matchId]);
        if ($match['next_match_id']) {
            $column = (int)$match['next_match_slot'] === 1 ? 'participant1_id' : 'participant2_id';
            $pdo->prepare("UPDATE matches SET {$column}=?, updated_at=NOW() WHERE id=?")
                ->execute([$winnerId, $match['next_match_id']]);
        } else {
            $pdo->prepare("UPDATE tournaments SET champion_id=?, status='finished', updated_at=NOW() WHERE id=?")
                ->execute([$winnerId, $match['tournament_id']]);
        }
        $standing = $pdo->prepare('INSERT INTO standings (tournament_id,participant_id,played,wins,losses,score_for,score_against) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE played=played+1,wins=wins+VALUES(wins),losses=losses+VALUES(losses),score_for=score_for+VALUES(score_for),score_against=score_against+VALUES(score_against)');
        $p1Won = $winnerId === (int)$match['participant1_id'];
        $standing->execute([$match['tournament_id'],$match['participant1_id'],1,$p1Won?1:0,$p1Won?0:1,$match['score1'],$match['score2']]);
        $standing->execute([$match['tournament_id'],$match['participant2_id'],1,$p1Won?0:1,$p1Won?1:0,$match['score2'],$match['score1']]);
        $pdo->prepare('INSERT INTO match_events (match_id,participant_id,event_type) VALUES (?,?,"finish")')->execute([$matchId,$winnerId]);
        audit_log($pdo, 'finish_match', 'match', $matchId, "Finished match #{$matchId}; winner participant #{$winnerId}");
        $pdo->commit();
        return ['winner_id' => $winnerId, 'next_match_id' => $match['next_match_id']];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function handle_logo_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_UPLOAD_SIZE) throw new RuntimeException('Invalid upload or file exceeds 2 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('Logo must be JPG, PNG, or WebP.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Unable to save logo.');
    return $name;
}
