# AI Assistant

The AI Assistant lets an AI assistant you trust create and manage content on your site on your behalf.

## What it can do

- Write post and page drafts, then publish or schedule them
- Browse and search posts and pages (drafts included) so it can find what to work on
- Moderate comments
- Manage redirects
- Keep navigation menus tidy
- Fact check posts and pages, on your terms

## Fact checking

Your AI assistant (CLI, IDE, or desktop) can check the claims in your posts and pages and file a structured report: findings, per-claim verdicts, and cited sources. The checks run under a versioned integrity prompt that your assistant fetches from this site before every check. You accept the terms once per prompt version under **Tools → AI Assistant → Fact Checking**, and the service refuses to run until you do, and again whenever the prompt is updated.

Accepted reports appear in the report history, in a read-only panel in the post and page editors, and (once you place the "Fact Check Summary" block in a region) on the public page, complete with a "checked under Pubvana fact-check prompt vX" line. If an article tries to steer its own fact check, the report flags the attempt.

## Getting started

1. Under **Tools → AI Assistant**, create a key and give it a name.
2. Copy the one-time "AI startup text" shown after generating the key. The key and the API guide link are both in it.
3. Paste the whole message into your AI tool.
4. On the Manage page, tick only the permissions that assistant should have. Unticked permissions are denied.
5. Stop the assistant at any time by pausing or deleting its key.

For fact checking, also visit **Tools → AI Assistant → Fact Checking**: read the terms, accept them, and switch the service on (it needs at least one enabled key).

## Records

The plugin keeps its own records for keys, permissions, and activity. Keys are never stored in full, and every use is recorded in the audit log on the Manage page.

## Default author

AI-created posts and pages are attributed to a user you choose on the Manage page.

## Where to go next

- `AI-README.md` is the technical reference for the AI and developers.
- The live guide is at `GET /api/ai/help`, and the Quick Start & Help button on the Manage page summarizes the setup.