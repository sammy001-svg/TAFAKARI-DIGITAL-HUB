# Tafakari Digital Hub

The conflict and peace monitoring platform of the Centre for Research, Training and Publications (CRTP), Hekima University College. It publishes a conflict heat map of the African countries CRTP monitors, bi-weekly and quarterly analysis reports, country monitoring updates, and CRTP's articles, documents, policy briefs, podcasts and videos.

An AI research assistant, the **Tafakari Agent** (built on Anthropic's Claude), does the routine research. It compiles country monitoring updates, proposes scores, drafts reports and articles, and finds videos. **It never publishes.** Every item waits for a CRTP admin, and monitoring updates and analysis reports need a super admin's approval.

- For the CRTP team: [docs/admin-guide.md](docs/admin-guide.md)
- For the public: the methodology page at `/methodology`

## Stack

- **PHP 8.1+** with MySQL or MariaDB, on cPanel shared hosting. Plain PHP pages, no framework.
- **Tailwind CSS** from the CDN, plus [public/css/tafakari-ui.css](public/css/tafakari-ui.css) for the shared admin and site components.
- **Leaflet** for the map.
- **Anthropic PHP SDK** (`anthropic-ai/sdk` ^1.4) for the agent and the chat assistant. `vendor/` is committed, so the server needs no Composer step.
- **Word and Excel files** are written directly with PHP's `ZipArchive` ([includes/docx.php](includes/docx.php), [includes/xlsx.php](includes/xlsx.php)). The host must have the zip extension.

The repository also holds an earlier Next.js version of the site (`src/`, `package.json`, `prisma/`). The live PHP site does not use it.

## Layout

```
*.php                     Public pages: index, heatmap, reports, documents, news, methodology, ...
heatmap-export.php        /heatmap/export: the heat map as Word or Excel
update-export.php         /updates/{id}/word|excel: a published country update
admin/                    Admin portal (sign-in required)
  agent.php               Tafakari Agent: findings, Activity, Settings
  agent-updates.php       Country monitoring updates: review, fact check, approve
  agent-articles.php      AI articles: verify and publish
  agent-videos.php        Suggested videos: approve and post
  reports.php, report.php Analysis reports
  super/                  Super-admin pages: approvals, users, heat-map configuration, ...
api/                      JSON endpoints used by the admin pages and the chat assistant
cron/agent-auto.php       The one scheduled job (hourly)
includes/
  functions.php           Shared helpers, heat-map taxonomy, monitoring cycles, aggregation
  reports.php             Analysis reports: snapshots, comparative ratings, AI narrative
  forecast.php            Outlooks (statistical projections)
  exports.php             Word and Excel versions of the heat map and reports
  agent.php               Agent core: API client, key storage, research loop, findings
  agent-updates.php       Country monitoring updates (the CRTP template), quarterly updates
  agent-articles.php      AI articles
  agent-videos.php        Video finder
  agent-auto.php          The hourly plan: what the agent does next
  assistant.php           Public chat assistant (English and Kiswahili)
migrations/               SQL for each schema change, numbered
docs/admin-guide.md       Guide for the CRTP team
```

Routes are clean URLs set in [.htaccess](.htaccess), which also blocks web access to `vendor/` and `cron/`.

## Setting up a server

1. Create the MySQL database in cPanel and import the base tables from [prisma/mysql-init.sql](prisma/mysql-init.sql) in phpMyAdmin. Everything else is created automatically (see below).
2. Put the real database credentials, `APP_URL` and a long random `APP_SECRET` in `config.php` **on the server only**. The copy in git holds placeholders; never commit real values.
3. Deploy the code with cPanel > Git Version Control: **Update from Remote**, then **Deploy HEAD Commit**.
4. Add the hourly job in cPanel > Cron Jobs:
   ```
   0 * * * * /usr/local/bin/php /home/USER/public_html/cron/agent-auto.php
   ```
5. Sign in as a super admin. Open **Tafakari Agent > Settings**, paste a Claude API key, click **Save and test**, and switch the agent on.

New tables and columns are created automatically the first time the relevant page or job runs. Each is attempted once, and the result is recorded in `SiteSetting` (keys `schema_*`). If the database user cannot create tables, run the matching file in `migrations/` in phpMyAdmin.

`APP_SECRET` (together with the database password) encrypts the stored Claude API key. If you change either, save the key again in Settings.

### Deploying an update

Push to GitHub, then in cPanel choose **Update from Remote** and **Deploy HEAD Commit**. **Settings > Status** shows the software version (`AGENT_CODE_VERSION` in [includes/agent.php](includes/agent.php)), so you can confirm the server has the new code. Raise it whenever agent behaviour changes.

## The Tafakari Agent

Each hourly run does one piece of work ([includes/agent-auto.php](includes/agent-auto.php)), in this order of priority:

| Work | How often | Output, waiting for review |
|---|---|---|
| Analysis report drafts | Once a day | Bi-weekly, quarterly and Outlook drafts with AI commentary |
| Quarterly country update | One a day, after a quarter closes | Drawn from the quarter's approved bi-weekly updates; no new research |
| Country monitoring update | Two a day, each country once per cycle | The full CRTP template, with a fact check and proposed scores |
| Video search | Once a day | Suggested YouTube videos |
| Article | Two a day | A sourced article on a region and indicator |
| Finding | Four a day | Single developments with proposed scores |

**Run now** in Settings does the next piece of work, ignoring the daily limits.

### Safeguards

- **Nothing is published by the AI.** Publishing a country update or analysis report needs a super admin, who must tick a review confirmation. Articles need an admin's confirmed verification.
- **Every citation is checked.** The agent may cite only URLs the API returned during that run's web search or fetch. Rows and claims citing anything else are removed, and an update needs at least three independent publishers.
- **The fact check is enforced in code.** A claim the model marks "verified" is downgraded to "single source" unless two independent publishers it actually retrieved support it.
- **Provenance is kept.** Approved updates enter the heat map labelled `AI_REVIEWED`. Quarterly updates carry no scores, so nothing is counted twice.
- **Do-no-harm rules are in every prompt.** No private individuals are named, places are given no more precisely than district level, and hate speech is described, never reproduced.
- **Web content is treated as data, never as instructions.**
- **Critical alerts.** Any finding scored 9 or more, and any update with an overall score of 9 or more, is emailed to every super admin.

### Model and API

The agent uses `claude-opus-5-5` with adaptive thinking, the `web_search_20260209` and `web_fetch_20260209` server tools, strict tool schemas for structured output, prompt caching on the system prompts, and server-side refusal fallbacks (`fallbacks: 'default'`). The API is billed per use from prepaid credits in the Anthropic Console; a Claude.ai subscription does not cover it.

## Reports and exports

- **Analysis reports** freeze the heat-map figures for a period and scope (all countries, a region or a country) as a JSON snapshot. That snapshot includes the comparative ratings (a ranking by overall score, with change in score and rank). A published report never changes.
- **Downloads.** The heat map, every report and every country update come as Word and Excel. Word versions are trimmed to under ten pages by `docx_fit_pages()`: optional tables shorten first, and the document says what was shortened. Excel workbooks always contain everything.

## Heat-map taxonomy

The five categories and their elements are set in **Heatmap Config** and stored in `SiteSetting.heatmap_categories`. The defaults are in `heatmap_default_elements()`. Elements added later, such as "States" and "Early response indicators", reach a saved configuration through `heatmap_element_additions()`, applied once.

## Brand

Colours: `#750B25` (burgundy), `#ED1C24` (red), `#E7952A` (amber), `#F8F8F0` (cream). No gradients. Fonts: Outfit for headings, Inter for body text.

## Licence

Private and proprietary to CRTP, Hekima University College.
