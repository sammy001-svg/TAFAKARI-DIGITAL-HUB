<?php
/**
 * Admin pages help document grading along. When a document posted at least
 * five minutes ago is still waiting for its grades, the page asks the server,
 * in the background once it has loaded, to grade it. That keeps the
 * five-minute promise even where the scheduled job runs only hourly.
 *
 * Deliberately light: it reads a few settings and runs one small query, and
 * loads no AI code. The work itself is done by /admin/agent/grades/due.
 */

function agent_grade_beacon(): void {
    try {
        if (get_setting('agent_enabled', '0') !== '1') return;
        if (get_setting('agent_api_key_enc', '') === '' && trim((string) getenv('ANTHROPIC_API_KEY')) === '' && !defined('ANTHROPIC_API_KEY')) return;
        $pause = get_setting('agent_health_pause_until', '');
        if ($pause !== '' && strtotime($pause) > time()) return;
        $last = get_setting('agent_grade_beacon_at', '');
        if ($last !== '' && strtotime($last) > time() - 60) return;
        // Post times come from MySQL, whose time zone can differ from PHP's: measure on its clock
        $dbNow = strtotime((string) db()->query('SELECT NOW(3)')->fetchColumn()) ?: time();
        $join = get_setting('schema_agent_grades_v1', '') === 'ok';
        $st = db()->prepare("SELECT p.id FROM Post p " . ($join ? 'LEFT JOIN AgentGrade g ON g.postId = p.id ' : '')
            . "WHERE p.type = 'DOCUMENT' AND p.status IN ('PUBLISHED','PENDING','DRAFT')
                 AND (p.intensityScores IS NULL OR p.intensityScores IN ('', '[]', '{}', 'null')) AND p.updatedAt <= ?"
            . ($join ? " AND (g.id IS NULL OR g.status = 'QUEUED')" : '') . ' LIMIT 1');
        $st->execute([date('Y-m-d H:i:s', $dbNow - 5 * 60)]);
        if ($st->fetchColumn() === false) return;
    } catch (Throwable $e) {
        return;   // a missing column or table: nothing to grade yet
    }
    if (empty($_SESSION['agent_csrf'])) $_SESSION['agent_csrf'] = bin2hex(random_bytes(16));
    $body = json_encode('csrf=' . $_SESSION['agent_csrf']);
    echo '<script>(function(){try{fetch("/admin/agent/grades/due",{method:"POST",credentials:"same-origin",keepalive:true,'
       . 'headers:{"Content-Type":"application/x-www-form-urlencoded"},body:' . $body . '}).catch(function(){});}catch(e){}})();</script>' . "\n";
}
