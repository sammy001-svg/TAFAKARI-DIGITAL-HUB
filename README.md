# Tafakari Digital Hub

The conflict and peace monitoring platform of the Centre for Research, Training and Publications (CRTP), Hekima University College. It publishes a conflict heat map of the African countries CRTP monitors, bi-weekly and quarterly analysis reports, country monitoring updates, and CRTP's articles, documents, policy briefs, podcasts and videos.

An AI research assistant, the **Tafakari Agent** (built on Anthropic's Claude), does the routine research. It compiles country monitoring updates, proposes scores, grades the intensity of the documents staff post, drafts reports and articles, and finds videos. **It never publishes.** Every item waits for a CRTP admin, and monitoring updates and analysis reports need a super admin's approval.

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
  agent-grades.php        Document grades: accept, adjust or reject the AI's intensity grades
  agent-articles.php      AI articles: verify and publish
  agent-videos.php        Suggested videos: approve and post
  reports.php, report.php Analysis reports
  super/                  Super-admin pages: approvals, users, heat-map configuration, ...
api/                      JSON endpoints used by the admin pages and the chat assistant
cron/agent-auto.php       The one scheduled job (every five minutes)
includes/
  functions.php           Shared helpers, heat-map taxonomy, monitoring cycles, aggregation
  reports.php             Analysis reports: snapshots, comparative ratings, AI narrative
  forecast.php            Outlooks (statistical projections)
  exports.php             Word and Excel versions of the heat map and reports
  agent.php               Agent core: API client, key storage, research loop, findings
  agent-updates.php       Country monitoring updates (the CRTP template), quarterly updates
  agent-articles.php      AI articles
  agent-videos.php        Video finder
  agent-grades.php        Intensity grades for posted documents: reading, grading, review
  agent-grade-beacon.php  Lets admin pages start grading that is due (light; no AI code)
  agent-auto.php          The schedule: grading every five minutes, other work hourly
  assistant.php           Public chat assistant (English, French and Kiswahili)
  issue-categories.php    Several issue categories per item: storage, display, the shared form field
  agent-classify.php      "Suggest with AI": proposes a document's issue categories
migrations/               SQL for each schema change, numbered
docs/admin-guide.md       Guide for the CRTP team
```

Routes are clean URLs set in [.htaccess](.htaccess), which also blocks web access to `vendor/` and `cron/`.

## Setting up a server

1. Create the MySQL database in cPanel and import the base tables from [prisma/mysql-init.sql](prisma/mysql-init.sql) in phpMyAdmin. Everything else is created automatically (see below).
2. Put the real database credentials, `APP_URL` and a long random `APP_SECRET` in `config.php` **on the server only**. The copy in git holds placeholders; never commit real values.
3. Deploy the code with cPanel > Git Version Control: **Update from Remote**, then **Deploy HEAD Commit**.
4. Add the scheduled job in cPanel > Cron Jobs, to run every five minutes:
   ```
   */5 * * * * /usr/local/bin/php /home/USER/public_html/cron/agent-auto.php
   ```
   A server set up earlier with an hourly line (`0 * * * *`) keeps working, but grades new documents up to an hour late; Settings > Status says so and shows the line to use.
5. Sign in as a super admin. Open **Tafakari Agent > Settings**, paste a Claude API key, click **Save and test**, and switch the agent on.
6. Press **Check everything** in Settings. It tests PHP, the SDK, the database, the key, the connection, credits, web search (which must be enabled for the Anthropic organisation) and the chat, and names the fix for anything that fails ([includes/agent-diagnostics.php](includes/agent-diagnostics.php)).

**Run now** replies to the browser at once and finishes the work after the response is closed (`fastcgi_finish_request` or `litespeed_finish_request`, with `noabort` set for `/admin/agent` in `.htaccess`), so long research is not cut off by web-server time limits. It shares a lock with the scheduled job, so only one piece of work runs at a time.

New tables and columns are created automatically the first time the relevant page or job runs. Each is attempted once, and the result is recorded in `SiteSetting` (keys `schema_*`). If the database user cannot create tables, run the matching file in `migrations/` in phpMyAdmin.

`APP_SECRET` (together with the database password) encrypts the stored Claude API key. If you change either, save the key again in Settings.

### Deploying an update

Push to GitHub, then in cPanel choose **Update from Remote** and **Deploy HEAD Commit**. **Settings > Status** shows the software version (`AGENT_CODE_VERSION` in [includes/agent.php](includes/agent.php)), so you can confirm the server has the new code. Raise it whenever agent behaviour changes.

## The Tafakari Agent

The scheduled job runs every five minutes ([includes/agent-auto.php](includes/agent-auto.php), `agent_auto_cron()`). Each run first grades the documents that are due, then, once an hour, does one piece of the other work, in this order of priority:

| Work | How often | Output, waiting for review |
|---|---|---|
| Document grades | Every run, up to three documents | Proposed intensity scores for documents posted without them (see below) |
| Analysis report drafts | Once a day | Bi-weekly, quarterly and Outlook drafts with AI commentary |
| Quarterly country update | One a day, after a quarter closes | Drawn from the quarter's approved bi-weekly updates; no new research |
| Country monitoring update | Two a day, each country once per cycle | The full CRTP template, with a fact check and proposed scores |
| Video search | Once a day | Suggested YouTube videos |
| Article | Two a day | A sourced article on a region and indicator |
| Finding | Four a day | Single developments with proposed scores |

**Run now** in Settings does the next piece of the hourly work, ignoring the daily limits.

### Document grades

[includes/agent-grades.php](includes/agent-grades.php) grades the intensity of `DOCUMENT` posts that have no `intensityScores`:

- **When.** A document is due five minutes after it was posted or last edited (`updatedAt`), newest first. Each run of the scheduled job grades up to three. Documents posted in the last seven days count as new (up to 60 a day); older ones are the backlog (up to 40 a day), so the backlog never holds up a new document. Drafts and documents awaiting approval are graded too; rejected and archived ones are not.
- **Admin pages help.** Where the job still runs hourly, an admin page that finds a due document asks `/admin/agent/grades/due` in the background (`agent_grade_beacon()`, at most once a minute). That request answers at once with 204 and grades one document after the response is closed.
- **What the AI reads.** The summary, the description, and the attached file: a PDF up to 20 MB and 100 pages is sent whole as a document block; Word and PowerPoint files are read as text; a linked web page is read as text. Files on the site are read from `public/uploads`; other links are downloaded only from public addresses. With fewer than 150 characters to read and no PDF, nothing is sent and the document is marked "not graded" with what to add.
- **The grading.** One request: `claude-opus-5-5`, effort `medium`, structured output (`agent_grade_schema()`): a score from 1 to 10 for each element the document gives evidence on, each with a reason, plus confidence and a summary. The scale is the agent's own (`agent_scoring_scale()`), so grades and findings are calibrated alike. Recent reviewer corrections and rejection reasons are sent with each request (`agent_grade_feedback()`).
- **Review.** Proposals are stored in `AgentGrade` (one row per document, migrations/015) and do not touch the post. Accepting one writes `Post.intensityScores` with `scoreSource` `AI` (accepted as proposed) or `AI_REVIEWED` (adjusted). A document scored by hand in the meantime keeps its scores and the proposal is set aside.
- **Retrying.** A failure is retried an hour later, up to three times. A document the AI could not grade is tried again when it is edited, or when someone presses **Grade again**.

### Safeguards

- **Nothing is published by the AI.** Publishing a country update or analysis report needs a super admin, who must tick a review confirmation. Articles need an admin's confirmed verification. Document grades count on the heat map only once a person accepts them.
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

## Issue categories

`Post.issueCategory` holds a comma-separated list, main category first (`A,B`); public filters use `FIND_IN_SET`. Read and write it only through [includes/issue-categories.php](includes/issue-categories.php): `post_issue_categories()`, `issue_categories_from_post()`, `issue_category_pills()` and the shared form field `issue_category_field()`, whose **Suggest with AI** button calls `/api/classify` (signed-in staff, page token; it suggests and saves nothing). AI articles and videos keep their extra categories in a `categories` column (migrations/014, added automatically).

## Heat-map taxonomy

The five categories and their elements are set in **Heatmap Config** and stored in `SiteSetting.heatmap_categories`. The defaults are in `heatmap_default_elements()`. Elements added later, such as "States" and "Early response indicators", reach a saved configuration through `heatmap_element_additions()`, applied once.

## Brand

Colours: `#750B25` (burgundy), `#ED1C24` (red), `#E7952A` (amber), `#F8F8F0` (cream). No gradients. Fonts: Outfit for headings, Inter for body text.

## Licence

Private and proprietary to CRTP, Hekima University College.
