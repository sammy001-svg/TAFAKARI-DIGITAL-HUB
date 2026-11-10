# Tafakari Digital Hub: guide for the CRTP team

This guide covers the day-to-day work in the admin portal and what to do when something goes wrong. Sign in at `/login`.

## Your daily routine

The Tafakari Agent works on its own every hour. It never publishes anything: everything it produces waits for a person to approve it. Open **Tafakari Agent** in the sidebar. The number beside it shows how many items are waiting.

| Tab | What is waiting | What to do |
|---|---|---|
| **Findings** | Developments the agent found, with proposed 1 to 10 scores and their sources | Open the sources. If the finding is sound, choose **Create draft report**, check the scores in the editor, and submit it. Otherwise reject it with a short reason. |
| **Articles** | Sourced articles on a region and an indicator | Read the article and the "Check these first" points, open the sources, edit if needed, tick **I have verified this article**, then **Publish**. **Download Word** gives you an editable copy. |
| **Videos** | YouTube videos on your regions | Click **Watch**. If it is suitable, choose **Approve and post**. Rejected videos are never suggested again. |

Aim to clear the queues every few days. The agent will not write a new article for a region and indicator while the last one is still waiting, so an untouched queue slows it down.

## Critical alerts

When the agent proposes a score of 9 or above, every super admin receives an email. The finding is AI-proposed and unverified: review it in the Findings tab like any other.

## Analysis reports

**Analysis Reports** holds the bi-weekly reports, the quarterly reports and the Outlooks (one-cycle projections). Drafts are created automatically, with a first commentary written by AI.

1. Open a draft and read the commentary against the figures. Edit it as needed.
2. If new scored reports have been published since, choose **Refresh data**.
3. Submit it for approval. A super admin publishes it.

Published reports are frozen and do not change when new data arrives.

## Settings (super admins)

**Tafakari Agent > Settings** has only a few options:

- **Agent on:** switches the agent's automatic work on or off.
- **Chat button on the website:** shows or hides the public chat assistant.
- **Claude API key:** paste the key and choose **Save and test**. It should confirm that the agent is ready. The Workspace ID is filled in automatically where possible.
- **Status:** shows whether the hourly job is running. If it is not, it shows the one line to add in cPanel under Cron Jobs.
- **Run now:** makes the agent do one piece of work immediately.

**Activity** lists every piece of work the agent has done, with any error.

## When something goes wrong

- **A page shows "Something went wrong" with a reference code.** Note the code. The full technical detail is in the server's error log under the same code (cPanel > Metrics > Errors, or the `error_log` file in the site folder). Send it to your developer.
- **Settings says Claude did not accept the key.** Read the message: it gives Anthropic's reason. The usual fix is to create a key inside a workspace in the Anthropic Console (under API keys, not Admin keys), paste it, leave Workspace ID blank, and save it again.
- **A fix does not seem to take effect.** Check the **Software version** at the bottom of Settings > Status. If it is older than the version your developer gave you, the new code has not reached the server: deploy again in cPanel > Git Version Control.
- **No new items for several days.** Check **Activity** for errors, and **Settings > Status** to confirm the hourly job is running.

## If you are locked out

The setup and recovery pages are closed to the public. To reset a super admin password:

1. In cPanel File Manager, create an empty file named `ENABLE_SETUP` in the site folder.
2. Visit `/reset-admin` and set a new password.
3. **Delete the `ENABLE_SETUP` file straight away.**

Demo passwords that were once published online cannot be used for any account.
