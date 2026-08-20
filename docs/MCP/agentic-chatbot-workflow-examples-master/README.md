# Agentic Chatbot — Workflow Examples

Ready-to-import JSON workflows for the [Filament Agentic Chatbot](https://github.com/heinergiehl/filament-agentic-chatbot) plugin.

> **Live demo →** [filament-agentic-chatbot.heinerdevelops.tech](https://filament-agentic-chatbot.heinerdevelops.tech/)
> **Plugin docs →** [agentic-chatbot-filament-docs](https://github.com/heinergiehl/agentic-chatbot-filament-docs)

---

## How to Import

1. Copy the raw JSON from any workflow file below (or download the `.json` file).
2. In the Filament workflow editor, click **📥 Import** in the sidebar.
3. Paste the JSON (or upload the file) and click **✅ Import**.
4. Review the visual graph, tweak system prompts and endpoints, then **💾 Save** and **🚀 Publish**.

---

## Workflows

| # | Workflow | Description | Key Features |
|---|---------|-------------|--------------|
| 01 | [SaaS Onboarding](workflows/01-saas-onboarding.json) | Progressive onboarding wizard that detects enterprise leads and tailors the experience | collectInput, knowledgeBase, condition (enterprise routing), join |
| 02 | [Support Ticket Router](workflows/02-support-ticket-router.json) | AI classifies user intent and routes to specialized support agents per department | aiAgent (intent classifier), switchRouter (4-way), knowledgeBase |
| 03 | [E-Commerce Order Status](workflows/03-ecommerce-order-status.json) | Looks up order data via external API and displays status-specific card messages | httpRequest, switchRouter, delay, card messages |
| 04 | [Lead Qualification](workflows/04-lead-qualification.json) | Collects lead info step by step, qualifies by company size, saves to CRM | collectInput (6 steps), setVariable, action (CRM), condition |
| 05 | [Webhook Inventory Alert](workflows/05-webhook-inventory-alert.json) | External system triggers stock check; auto-sends email + Slack alert if low | webhook trigger, action (email), httpRequest (Slack), no UI |
| 06 | [FAQ with Confidence Check](workflows/06-faq-with-confidence-check.json) | Two-stage AI evaluates answer confidence before responding or escalating | aiAgent ×2 (confidence + answer), nested conditions, buttons |
| 07 | [Content Research Assistant](workflows/07-content-research-assistant.json) | Multi-step content writer: researches KB, drafts outline, writes full piece | collectInput ×4, knowledgeBase, aiAgent (outline → draft) |

---

## Customisation Tips

- **AI models** — all `aiAgent` nodes leave `provider` and `model` empty, so they inherit the bot's default. Override per node if you need a faster or smarter model at specific steps.
- **Knowledge base** — connect your bot with ingested sources so `knowledgeBase` nodes return real results.
- **Actions** — workflow 04 uses `save_lead` and workflow 05 uses `send_email`. Register these in your `ActionRegistry` or swap with your own keys.
- **HTTP endpoints** — workflows 03 and 05 reference placeholder URLs (`api.example.com`, `hooks.slack.com`). Replace with your real endpoints.
- **Webhooks** — workflow 05 uses a webhook trigger. Send a POST to `/api/chatbot/webhook/inventory-alert` with the expected payload structure.

---

## Node Type Coverage

These examples collectively demonstrate every major node type:

| Node Type | Used In |
|-----------|---------|
| `trigger` (user_message) | 01, 02, 03, 04, 06, 07 |
| `trigger` (webhook) | 05 |
| `sendMessage` (text) | 01, 02, 04, 05, 06, 07 |
| `sendMessage` (card) | 03 |
| `sendMessage` (buttons) | 06 |
| `collectInput` | 01, 02, 03, 04, 06, 07 |
| `knowledgeBase` | 01, 02, 06, 07 |
| `aiAgent` | 01, 02, 04, 05, 06, 07 |
| `condition` | 01, 03, 04, 05, 06 |
| `switchRouter` | 02, 03 |
| `setVariable` | 04, 05 |
| `httpRequest` | 03, 05 |
| `action` | 04, 05 |
| `delay` | 03, 07 |
| `join` | 01, 02, 04 |
| `note` | all |

---

## Requirements

- [Filament Agentic Chatbot](https://github.com/heinergiehl/filament-agentic-chatbot) plugin installed
- PHP 8.4+, Laravel 12+, Filament 5.2+
- An AI provider key (OpenAI, Anthropic, or Gemini)
- PostgreSQL + pgvector (recommended) or ChromaDB

## License

These workflow examples are provided under the [MIT License](LICENSE). The Filament Agentic Chatbot plugin itself is licensed separately.
