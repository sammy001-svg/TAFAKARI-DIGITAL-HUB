<?php
declare(strict_types=1);
/**
 * Tafakari Agent - is everything set up? And if not, exactly why not.
 *
 * The agent and the website chat depend on a chain: PHP and its extensions,
 * the Claude software (vendor/), a readable API key, a connection Anthropic
 * accepts, credits on the account, web search enabled for the organisation,
 * and the hourly job. When any link is missing they used to fall silent
 * ("offline", a disabled button). These functions name the broken link and
 * the fix, for the Settings page and for the "Check everything" button.
 *
 * Requires agent.php, assistant.php and agent-auto.php.
 */

require_once __DIR__ . '/agent-auto.php';
require_once __DIR__ . '/assistant.php';

/** Saved key state, so a key that can no longer be read is reported rather than looking like no key. */
function agent_key_state(): array {
    $stored = '';
    try { $stored = (string) agent_setting_fresh(AGENT_KEY_SETTING); } catch (Throwable $e) {}
    $server = (string) (getenv('ANTHROPIC_API_KEY') ?: (defined('ANTHROPIC_API_KEY') ? constant('ANTHROPIC_API_KEY') : ''));
    if ($stored !== '') {
        $plain = agent_decrypt_key($stored);
        if ($plain !== '') return ['state' => 'saved', 'hint' => agent_key_hint($plain)];
        return ['state' => 'unreadable', 'server' => trim($server) !== ''];
    }
    return trim($server) !== '' ? ['state' => 'server'] : ['state' => 'none'];
}

/**
 * What stops the agent and the chat from working at all, most fundamental
 * first. Empty when both can run. Each: [text, fix].
 *
 * @return list<array{0:string,1:string}>
 */
function agent_blockers(): array {
    $out = [];
    if (PHP_VERSION_ID < 80100) {
        $out[] = ['The server runs PHP ' . PHP_VERSION . ', and the Claude software needs PHP 8.1 or newer.', 'In cPanel, open "Select PHP Version" (or MultiPHP Manager) and choose PHP 8.1 or newer for this site.'];
    }
    if (!agent_sdk_available()) {
        $out[] = ['The Claude software (the vendor folder) is missing from the server.', 'Deploy the latest code: cPanel > Git Version Control > Update from Remote, then Deploy HEAD Commit.'];
    }
    foreach (['curl' => 'curl', 'openssl' => 'openssl'] as $ext => $name) {
        if (!extension_loaded($ext)) $out[] = ["The PHP extension \"{$name}\" is not enabled.", "In cPanel > Select PHP Version > Extensions, tick \"{$name}\"."];
    }
    $k = agent_key_state();
    if ($k['state'] === 'none') {
        $out[] = ['No Claude API key has been saved.', 'Paste your key under Claude API key in Tafakari Agent > Settings and click Save and test.'];
    } elseif ($k['state'] === 'unreadable' && empty($k['server'])) {
        $out[] = ['A Claude API key is saved, but the server can no longer read it. This happens when APP_SECRET or the database password in config.php has changed since the key was saved.',
                  'Paste your key again under Claude API key in Tafakari Agent > Settings and click Save and test.'];
    }
    return $out;
}

/** Why the public chat shows "offline", or null when it is ready. */
function assistant_offline_reason(): ?string {
    if (!assistant_settings()['enabled']) return 'The chat is switched off in Settings, so the button is hidden from visitors.';
    $b = agent_blockers();
    return $b ? 'Visitors see the chat as offline because: ' . $b[0][0] . ' ' . $b[0][1] : null;
}

// ── "Check everything" ───────────────────────────────────────────

/**
 * Test every link in the chain, on this server, against the real API.
 * The live checks make a few small requests to Claude (one of them a single
 * web search). Each check: name, ok (true, false, or null for information),
 * detail, fix.
 *
 * @param mixed $transporter optional PSR-18 client (tests)
 * @return array{at:string,ok:bool,checks:list<array{name:string,ok:?bool,detail:string,fix:string}>}
 */
function agent_diagnose(mixed $transporter = null): array {
    $checks = [];
    $add = function (string $name, ?bool $ok, string $detail = '', string $fix = '') use (&$checks): void {
        $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail, 'fix' => $fix];
    };

    // The server
    $add('PHP version', PHP_VERSION_ID >= 80100, PHP_VERSION, PHP_VERSION_ID >= 80100 ? '' : 'Choose PHP 8.1 or newer in cPanel > Select PHP Version.');
    $missing = array_values(array_filter(['curl', 'openssl', 'mbstring', 'zip', 'pdo_mysql'], fn($e) => !extension_loaded($e)));
    $add('PHP extensions', !$missing, $missing ? 'Missing: ' . implode(', ', $missing) : 'curl, openssl, mbstring, zip and pdo_mysql are enabled',
         $missing ? 'Enable them in cPanel > Select PHP Version > Extensions. (zip is only needed for Word and Excel downloads.)' : '');
    $sdk = agent_sdk_available();
    $add('Claude software', $sdk, $sdk ? 'Found in the vendor folder' : 'The vendor folder is missing', $sdk ? '' : 'Deploy the latest code from git (cPanel > Git Version Control).');
    $add('Software version', null, AGENT_CODE_VERSION, '');

    // The database
    $tables = [];
    try { ensure_agent_tables(); } catch (Throwable $e) {}
    foreach (['ensure_agent_article_table', 'ensure_agent_video_table', 'ensure_agent_update_table', 'ensure_assistant_table', 'ensure_report_tables'] as $fn) {
        if (function_exists($fn)) { try { $fn(); } catch (Throwable $e) {} }
    }
    foreach (['AgentRun' => '006', 'AgentItem' => '006', 'AgentCountryUpdate' => '012', 'AgentArticle' => '010', 'AgentVideo' => '011', 'AssistantLog' => '009', 'AnalysisReport' => '007'] as $t => $mig) {
        try { db()->query("SELECT 1 FROM `{$t}` LIMIT 1"); } catch (Throwable $e) { $tables[] = "{$t} (migrations/{$mig})"; }
    }
    $add('Database tables', !$tables, $tables ? 'Missing: ' . implode(', ', $tables) : 'All agent and chat tables exist',
         $tables ? 'Run the listed files in phpMyAdmin (SQL tab), with the site\'s database selected.' : '');

    // The key
    $k = agent_key_state();
    $keyText = ['saved' => 'Saved: ' . ($k['hint'] ?? ''), 'server' => 'Set on the server (config.php or environment)', 'none' => 'No key saved',
                'unreadable' => 'A key is saved but cannot be read: APP_SECRET or the database password in config.php changed after it was saved'][$k['state']];
    $keyOk = in_array($k['state'], ['saved', 'server'], true) || ($k['state'] === 'unreadable' && !empty($k['server']));
    $add('Claude API key', $keyOk, $keyText, $keyOk ? '' : 'Paste your key under Claude API key below and click Save and test.');

    $live = $sdk && $keyOk && PHP_VERSION_ID >= 80100;
    if (!$live) {
        $add('Connection to Claude', null, 'Not tested until the problems above are fixed.', '');
    } else {
        // Free: the key is accepted and can see the model (also sorts out the workspace)
        $conn = agent_check_connection(null, null, $transporter);
        $add('Connection to Claude', $conn['ok'], $conn['message'], $conn['ok'] ? '' : 'Follow the instruction in the message, then run this check again.');
        if ($conn['ok']) {
            $client = agent_client($transporter);
            // Which organization the key belongs to: credits must be bought for this one (free request)
            $ids = ['organization' => '', 'workspace' => '', 'request' => ''];
            try { $ids = agent_response_ids($client->models->raw->retrieve(AGENT_MODEL, [])); } catch (Throwable $e) { $ids = agent_response_ids($e); }
            if ($ids['organization'] !== '') {
                $add('Anthropic organization', null, 'This key belongs to organization ID ' . $ids['organization'] . ($ids['workspace'] !== '' ? ', workspace ' . $ids['workspace'] : '')
                     . '. Credits must be bought for this organization. In the Anthropic Console, Settings > Organization shows the ID of the organization you are in.', '');
            }
            // A tiny request: confirms credits and model access
            try {
                $r = $client->beta->messages->create(
                    model: AGENT_MODEL, maxTokens: 400,
                    messages: [['role' => 'user', 'content' => 'This is a connection test. Reply with the single word OK.']],
                    thinking: ['type' => 'adaptive'], outputConfig: ['effort' => 'low'],
                    fallbacks: 'default', betas: [AGENT_FALLBACK_BETA],
                );
                $add('Credits and model', true, 'Claude answered (model ' . $r->model . ').', '');
                $paid = true;
            } catch (Throwable $e) {
                $org = agent_response_ids($e)['organization'] ?: $ids['organization'];
                $add('Credits and model', false, agent_api_error($e),
                     stripos(agent_api_detail($e), 'credit balance') !== false
                         ? 'In the Anthropic Console, switch to the organization' . ($org !== '' ? " with ID {$org}" : ' this key belongs to') . ' and check its balance under Plans & Billing. '
                           . 'If the credits are in a different organization, either buy credits in this one, or create a new API key inside the organization that has the credits and save it here. Then run this check again.'
                         : 'Follow the instruction in the message, then run this check again.');
                $paid = false;
            }
            if ($paid) {
                // One web search: the agent's research depends on it, and an organisation can have it switched off
                try {
                    $r = $client->beta->messages->create(
                        model: AGENT_MODEL, maxTokens: 1500,
                        tools: [['type' => 'web_search_20260209', 'name' => 'web_search', 'maxUses' => 1]],
                        messages: [['role' => 'user', 'content' => 'Connection test: run exactly one web search for "ReliefWeb South Sudan" and reply with the title of one result.']],
                        thinking: ['type' => 'adaptive'], outputConfig: ['effort' => 'low'],
                        fallbacks: 'default', betas: [AGENT_FALLBACK_BETA],
                    );
                    $found = 0; $searchErr = '';
                    foreach ($r->content as $b) {
                        if (($b->type ?? '') !== 'web_search_tool_result') continue;
                        if (is_array($b->content)) $found += count($b->content);
                        elseif (isset($b->content->errorCode)) $searchErr = (string) $b->content->errorCode;
                        elseif (isset($b->content->error_code)) $searchErr = (string) $b->content->error_code;
                    }
                    $add('Web search', $found > 0, $found > 0 ? "Working: {$found} results returned." : ($searchErr !== '' ? "Web search returned an error: {$searchErr}." : 'No search results came back.'),
                         $found > 0 ? '' : 'In the Anthropic Console, open Settings > Privacy (organisation settings) and make sure web search is enabled, then run this check again.');
                } catch (Throwable $e) {
                    $add('Web search', false, agent_api_error($e), 'In the Anthropic Console, open Settings > Privacy and enable web search for the organisation, then run this check again.');
                }
                // The chat's answer format
                if (function_exists('assistant_call_model')) {
                    try {
                        $r = assistant_call_model($client, 'Where is CRTP based?', [['title' => 'About CRTP', 'date' => '', 'text' => 'CRTP is based at Hekima University College in Nairobi.']]);
                        $text = '';
                        foreach ($r->content as $b) if (($b->type ?? '') === 'text') $text .= $b->text;
                        $d = json_decode($text, true);
                        $ok = is_array($d) && isset($d['answer'], $d['found']);
                        $add('Website chat', $ok, $ok ? 'The chat answered correctly: "' . mb_substr((string) $d['answer'], 0, 120) . '"' : 'The chat\'s answer was not in the expected format.', $ok ? '' : 'Send this result to your developer.');
                    } catch (Throwable $e) {
                        $add('Website chat', false, agent_api_error($e), 'Send this result to your developer.');
                    }
                }
            }
        }
    }

    // Running by itself
    $s = agent_settings();
    $add('Agent switched on', $s['enabled'] ? true : null, $s['enabled'] ? 'Yes' : 'No: it only works when you press Run now', $s['enabled'] ? '' : 'Tick Agent on in Settings and click Save.');
    $a = agent_auto_status();
    $add('Hourly job', $a['running'], $a['running'] ? 'Last ran ' . format_relative_time($a['lastTick']) . '.' : ($a['lastTick'] !== '' ? 'Last ran ' . format_relative_time($a['lastTick']) . ', so it has stopped.' : 'It has never run.'),
         $a['running'] ? '' : 'Add the cron line shown under Status in cPanel > Cron Jobs (once an hour).');
    $add('Website chat switched on', assistant_settings()['enabled'] ? true : null, assistant_settings()['enabled'] ? 'Yes' : 'No: the chat button is hidden', '');

    $failed = array_filter($checks, fn($c) => $c['ok'] === false);
    $result = ['at' => date('Y-m-d H:i:s'), 'ok' => !$failed, 'checks' => $checks];
    // Claude answered: a pause from an earlier account or connection problem is over
    $answered = (bool) array_filter($checks, fn($c) => $c['name'] === 'Credits and model' && $c['ok'] === true);
    if ($answered && function_exists('agent_health_reset')) agent_health_reset();
    try { set_setting('agent_diag_last', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); } catch (Throwable $e) {}
    return $result;
}

/** The last "Check everything" result, or null. */
function agent_diagnose_last(): ?array {
    $d = json_decode(agent_setting_fresh('agent_diag_last'), true);
    return is_array($d) && isset($d['checks']) ? $d : null;
}
