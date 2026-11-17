# Tafakari Digital Hub: guide for the CRTP team

This guide covers the day-to-day work in the admin portal and what to do when something goes wrong. Sign in at `/login`.

## How the work is shared

The Tafakari Agent does the work research assistants used to do with the Bi-weekly Country Conflict and Peace Monitoring template. It researches every monitored country each cycle, fills in the template, fact-checks the key claims and proposes scores. It also drafts the analysis reports, writes articles and finds videos.

**It never publishes anything.** Everything it produces waits for a person:

- **Any admin** can review, correct and save the agent's work, approve articles and videos, and reject anything.
- **A super admin** approves and publishes country monitoring updates and analysis reports.

Open **Tafakari Agent** in the sidebar. The number beside it shows how many items are waiting.

## Your routine

| Tab | What is waiting | What to do |
|---|---|---|
| **Country updates** | A monitoring update for each country, every cycle | Read the update and its fact check, open the sources, correct anything, then a super admin approves it. See below. |
| **Findings** | Single developments found between updates, with proposed scores | Open the sources. If the finding is sound, choose **Create draft report**, check the scores and submit it. Otherwise reject it with a reason. |
| **Articles** | Sourced articles on a region and an indicator | Read the article and the "Check these first" points, open the sources, edit if needed, tick **I have verified this article**, then **Publish**. |
| **Videos** | YouTube videos on your regions | Click **Watch**. If it is suitable, choose **Approve and post**. Rejected videos are never suggested again. |

Aim to clear the queues every few days. The agent will not write a new article for a region and indicator while the last one is still waiting, so an untouched queue slows it down.

## Country monitoring updates

Each cycle, the agent compiles one update per monitored country, about two a day. It follows CRTP's template:

1. Overall peace and security status: a score from 0 to 10 and a three-to-five-sentence justification.
2. Key peace and conflict developments.
3. The heat-map categories:
   - A) Conflict and Security Watch.
   - B) Peace and Political Track.
   - C) Humanitarian and Emergency Situation.
   - D) Actors and Institutional Engagement: international actors, regional organisations, civil society, **states**, NGOs and religious institutions.
4. Early warning indicators and **early response indicators**, each marked increasing, stable or decreasing.
5. Analysis of the period: conflict dynamics, peace dynamics and risks for the coming period.
6. Executive summary.

Every row cites numbered sources. A source counts only if the agent actually opened it during its research, so invented links are removed automatically.

### The fact check

Each update lists its key claims with a status:

| Status | Meaning | What to do |
|---|---|---|
| **Verified** | Two or more independent publishers agree | Spot-check one source |
| **Single source** | Only one publisher reports it | Open the source; soften the wording if needed |
| **Disputed** | Reputable sources contradict each other | Read the note; make sure the text says it is disputed |
| **Unverified** | It could not be confirmed | Check it yourself, or remove it from the summary |

The agent cannot mark a claim "verified" on one source, so check the other three statuses first.

### Reviewing and approving

1. Open **Tafakari Agent > Country updates** and choose a country.
2. Read the full update, the fact check and the agent's notes on the right.
3. Correct the overall score, justification, executive summary, analysis or element scores if needed, then click **Save changes**. Any admin can do this.
4. A super admin ticks **I have reviewed this update** and clicks **Approve and publish**.

Once approved, a bi-weekly update becomes a published report in the document library, with Word and Excel downloads. Its scores count on the heat map and in the analysis reports, labelled as AI-proposed and reviewed by CRTP.

If an update is not good enough, **Reject** it with a reason. A rejected update can be returned for review later.

**This cycle** on the right shows which countries are done. A super admin can click **Write now** to research a country immediately rather than waiting for its turn.

### Quarterly updates

After a quarter closes, the agent writes a quarterly update for each country from the bi-weekly updates CRTP approved during that quarter. It does no new research for these. Review and approve them the same way. Quarterly updates do not add to the heat map, so nothing is counted twice.

## Critical alerts

Every super admin receives an email in two cases:

- The agent proposes a finding scored 9 or above.
- A country update has an overall score of 9 or above.

The finding or update is AI-proposed and unverified: review it like any other.

## Analysis reports

**Analysis Reports** holds the bi-weekly reports, quarterly reports and Outlooks (one-cycle projections). Drafts are created automatically, with a first commentary written by AI.

Each report includes **comparative ratings**: the countries ranked by overall score, with each one's change in score and rank since the previous period, and its highest-scoring category.

1. Open a draft and read the commentary against the figures. Edit it as needed.
2. If new scored reports have been published since, choose **Refresh data**.
3. Submit it for approval. A super admin publishes it.

Published reports are frozen: they do not change when new data arrives.

## Downloads: Word and Excel

| What | Where | Word | Excel |
|---|---|---|---|
| Heat map | The heat map page, under the title. Downloads the cycle you are viewing, or all time. | Yes | Yes |
| Analysis report | The report page (admin and public) | Yes | Yes, plus CSV |
| Country update | The update's page (admin), and the published document page (public) | Yes | Yes |

Word documents are kept **under ten pages**. When there is more data than fits, the longest tables are shortened and the document says so at the end. Excel workbooks always contain every row.

A draft's Word file is marked "DRAFT" at the top. Only published reports and updates can be downloaded from the public site.

## Heat-map categories and elements

Scores are given to the elements of five categories. Two elements were added from the template:

- **States**, under Actors and Institutional Engagement.
- **Early response indicators**, under Early Warning Signals.

They are added to the saved configuration automatically. Super admins can rename or add elements under **Heatmap Config > Categories**. Renaming an element means older reports scored under the old name no longer count for it, so add new elements rather than renaming old ones.

## Settings (super admins)

**Tafakari Agent > Settings** has only a few options:

- **Agent on:** switches the agent's automatic work on or off.
- **Chat button on the website:** shows or hides the public chat assistant.
- **Claude API key:** paste the key and choose **Save and test**. It should confirm that the agent is ready. Leave Workspace ID blank unless Anthropic asks for one.
- **Status:** shows whether the agent is working normally or paused, and whether the hourly job is running. If it is not, it shows the one line to add in cPanel under Cron Jobs. It also shows the **software version**, so you can confirm a deploy reached the server.
- **Run now:** starts one piece of work immediately (usually the next country update) and takes you to **Activity**, which refreshes itself until the work is done, usually one to five minutes. You can leave the page meanwhile. If Run now is greyed out, the red box at the top of Settings says why.
- **Check everything:** tests each part the agent and the website chat need: PHP, the Claude software, the database, the API key, the connection, the account's credits, web search and the chat. Every problem it finds comes with what to do. Run it after saving a key, adding credits or deploying an update. It makes a few small test requests to Claude.

**Activity** lists every piece of work the agent has done, with any error.

If something stops the agent or the chat from working at all, a red box at the top of Settings names the problem and the fix. While it is there, Run now is greyed out and visitors see the chat as offline, with CRTP's contact details instead.

### What the agent does each day

Each hour the agent does one piece of work, in this order of priority:

1. On the first run of the day, it creates the report drafts that are due.
2. One quarterly update, if a quarter has closed.
3. Up to two country monitoring updates.
4. One YouTube search.
5. Up to two articles and four findings.

After that it rests until the next day.

### Paying for the agent

The agent runs on Anthropic's API, which is paid from prepaid credits in the Anthropic Console under **Plans & Billing**. A Claude.ai subscription does not cover it. Turn on auto-reload, and set a monthly spend limit under **Settings > Limits** in the Console, so costs stay predictable and the agent never stops mid-month.

## When something goes wrong

- **A page shows "Something went wrong" with a reference code.** Note the code. The full technical detail is in the server's error log under the same code (cPanel > Metrics > Errors, or the `error_log` file in the site folder). Send it to your developer.
- **Settings says Claude did not accept the key.** Read the message: it gives Anthropic's reason. The usual fix is to create a key inside a workspace in the Anthropic Console (under API keys, not Admin keys), paste it, leave Workspace ID blank, and save it again.
- **Run now is greyed out, or the website chat says it is offline.** Open **Tafakari Agent > Settings**: the red box at the top says why. Then press **Check everything**. The most common causes:
  - *A key is saved but the server can no longer read it.* The key is stored encrypted using `APP_SECRET` and the database password in `config.php`. If either changes, paste the key again and click **Save and test**.
  - *The Claude software is missing.* The code has not been fully deployed: in cPanel > Git Version Control, click **Update from Remote**, then **Deploy HEAD Commit**.
  - *No key is saved.* Paste it and click **Save and test**.
- **Activity says the account has no API credits.** The key is fine. Add credits in the Anthropic Console under Plans & Billing (see above), then press **Check everything**. Once Claude answers, the agent carries on by itself.
- **Credits were bought, but it still says there are none.** Credits belong to one Anthropic organization, and the key to one organization; they must be the same. Check everything shows the ID of the organization the key belongs to. In the Console, switch to that organization (top left; Settings > Organization shows its ID) and check its balance. If the credits are in another organization, create a new API key inside the organization that has them and save it in Settings. Credits bought on claude.ai do not count, and a new payment can take a few minutes to arrive.
- **Check everything says web search is not working.** The agent's research needs web search, which an organisation's admin can switch off. In the Anthropic Console, open Settings > Privacy and make sure web search is enabled, then check again.
- **The agent has paused.** After an account problem (no credits, a rejected key) it pauses for six hours instead of failing every hour, and emails the super admins once. Fix the cause, then press **Check everything** or **Run now**, and it restarts straight away.
- **A country has no update this cycle.** The agent writes about two a day, so the last countries arrive near the end of the cycle. Activity shows whether it declined a country for lack of reliable reporting. A super admin can click **Write now** to try again.
- **A download fails with "The file could not be created".** The server needs PHP's zip extension. Ask your host to enable it.
- **A fix does not seem to take effect.** Check the **Software version** at the bottom of Settings > Status. If it is older than the version your developer gave you, the new code has not reached the server: deploy again in cPanel > Git Version Control.
- **No new items for several days.** Check **Activity** for errors, and **Settings > Status** to confirm the hourly job is running.

## If you are locked out

The setup and recovery pages are closed to the public. To reset a super admin password:

1. In cPanel File Manager, create an empty file named `ENABLE_SETUP` in the site folder.
2. Visit `/reset-admin` and set a new password.
3. **Delete the `ENABLE_SETUP` file straight away.**

Demo passwords that were once published online cannot be used for any account.
