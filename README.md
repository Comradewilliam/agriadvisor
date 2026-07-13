# Agri-Advisory (BwanaShamba)

**AI-powered agricultural extension for Kakonko District, Kigoma Region — built for farmers on feature phones and for government officers who serve them.**

[![GitHub](https://img.shields.io/badge/GitHub-Comradewilliam%2Fagriadvisor-blue)](https://github.com/Comradewilliam/agriadvisor)

---

## Why Kakonko?

Kakonko District is home to thousands of smallholder farmers across **13 wards** and dozens of villages — from Kakonko and Kasanda to Mwamala and Ilagala. Many rely on basic phones, seasonal rainfall, and occasional visits from Ward Agricultural Officers (WAOs).

Agri-Advisory closes the gap between **when a farmer has a question** and **when expert help arrives**. Farmers ask in **Kiswahili**; the system answers using **AI + a local knowledge base**, and escalates complex cases to real officers in the same district structure.

---

## How It Helps Kakonko Farmers

| Challenge | How Agri-Advisory helps |
|-----------|-------------------------|
| No smartphone or data | **USSD** menu works on any mobile network; **SMS** to short code **5852** for longer AI chats |
| Questions in Swahili, KB in English | **Bilingual RAG search** maps terms like *mahindi* → maize, *kupanda* → planting |
| Waiting days for an officer | **Instant AI advice** on crops, soil, seasons, pests; officer contacts shown on USSD |
| Missing weather warnings | **Localized weather** by village/ward with seasonal fallbacks |
| Forgetting officer phone numbers | **Option 3 (Afisa)** lists WAO contacts for the farmer’s ward inline on USSD |
| Registration far from office | **USSD self-registration** by ward & village + welcome SMS |

### Farmer channels

| Channel | Best for | Example |
|---------|----------|---------|
| **USSD** | Quick menu: advice, weather, officer, profile | `*384#` → 1 Ushauri → *Je, ni lini bora kupanda mahindi?* |
| **SMS 5852** | Longer AI conversation in Swahili | Text a farming question; BwanaShamba replies via SMS |
| **Web portal** | Farmers with occasional internet | Dashboard, chat, crops, visit requests |

**BwanaShamba** (the AI assistant) uses OpenRouter with **knowledge-base fallback** when the API is busy — so farmers still get practical Swahili guidance grounded in Tanzanian crop practices.

---

## How It Helps Government (District & National)

Agri-Advisory is not only a farmer app — it is a **digital extension layer** for the Ministry of Agriculture structure at ward and district level.

### For Ward Agricultural Officers (WAOs)

- **Escalation queue** — AI flags questions that need field inspection; officers reply from the portal  
- **Farmer registry** — view and update farmers by ward/village  
- **Visit scheduling & follow-up** — plan field visits and record outcomes  
- **Broadcasts** — send one-way SMS alerts to farmers in assigned wards  
- **Knowledge base (AI mentorship)** — add/update local advisories that improve AI answers  

### For District Agricultural Officers (DAOs)

- **Multi-ward oversight** — manage WAOs across Kakonko’s 13 wards  
- **District analytics** — farmer counts, message activity, escalation trends  
- **Weather alert approval** — review and publish district-wide advisories  
- **Automated alerts** — welcome SMS on registration, triggered notifications  

### For Super Administrators & Policy

- **System-wide analytics** — channel usage (USSD / SMS / web), AI confidence, errors  
- **Channel analytics** — structured events from USSD, SMS, AI, and delivery reports  
- **Audit logs** — officer actions, district edits, security-sensitive changes  
- **CMS & landing page** — public-facing information for the programme  
- **Scalable design** — Kakonko first; architecture supports more districts and regions  

### Government value in one line

> **Fewer unanswered farmer questions, faster officer response, measurable extension coverage, and data for agricultural planning — without requiring smartphones.**

---

## AI in the System

```
Farmer (Swahili) → USSD / SMS / Web
        ↓
   Knowledge Base (RAG) — crops, seasons, Kakonko context
        ↓
   BwanaShamba AI (OpenRouter) — conversational Swahili reply
        ↓
   Fallback: KB-only answer if AI rate-limited
        ↓
   Escalate → Ward Officer if question needs field visit
```

- **RAG** retrieves relevant articles before the AI responds  
- **Rate limits** protect costs and fair use per farmer per day  
- **Confidence levels** (high / medium / low) support officer review  
- **Offline USSD tips** when AI is unavailable — answers stay on the USSD screen (no slow SMS wait)  

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.1+, custom MVC |
| Database | MySQL (MariaDB) |
| AI | OpenRouter API + local Knowledge Base |
| Messaging | Africa's Talking (USSD, SMS, delivery reports) |
| Weather | WeatherAPI.com |
| Frontend | Tailwind CSS, vanilla JS |
| Tests | PHPUnit, USSD session flow test suite |

---

## Quick Start (Local)

### Requirements

- PHP 8.1+ with `pdo_mysql`, `curl`, `mbstring`
- MySQL 5.7+ / MariaDB
- [Africa's Talking](https://africastalking.com) sandbox account (USSD/SMS)
- [OpenRouter](https://openrouter.ai) API key (AI)
- Optional: [WeatherAPI](https://www.weatherapi.com) key

### Setup

```bash
git clone https://github.com/Comradewilliam/agriadvisor.git
cd agriadvisor
cp .env.example .env
# Edit .env with DB credentials, AT keys, OpenRouter key

# Create database and import schema
mysql -u root -p agridb < database/setup.sql
mysql -u root -p agridb < database/seed.sql

# Apply patches (USSD channel, chat threads, system events, etc.)
php scripts/patch-system-events.php
# Or run SQL files in database/ manually via MySQL

# Start dev server (Windows / MAMP)
serve.bat
# Or: php -S 127.0.0.1:1234 -t public public/router.php
```

Open **http://127.0.0.1:1234**

### Webhooks (production / ngrok)

| Service | Callback URL |
|---------|----------------|
| USSD | `https://yourdomain.com/ussd` |
| SMS (two-way) | `https://yourdomain.com/sms` |
| Delivery reports | `https://yourdomain.com/sms/delivery` |

See [DOCS.md](DOCS.md) for SMS one-way vs two-way configuration.

---

## USSD Menu (Registered Farmer)

```
Karibu [Jina]!
1. Ushauri wa Kilimo    → AI/KB farming advice (on screen)
2. Hali ya Hewa         → Today's weather by village
3. Wasiliana na Afisa   → Ward officer name & phone
4. Taarifa zangu        → Profile summary
```

Unregistered numbers go through **ward → village registration** and receive a **welcome SMS**.

---

## Testing

```bash
# USSD session flow validation (17 test cases)
php scripts/run-ussd-test-suite.php

# PHPUnit
vendor/bin/phpunit
```

---

## Project Structure

```
app/
  Controllers/   # USSD, SMS, Farmer, Officer, Admin
  Services/      # AI chat, RAG, SMS, alerts, weather
  Models/        # Farmers, wards, KB, visits
database/        # Schema, seeds, patches
public/          # Web root
routes/          # web.php, api.php
views/           # Farmer, officer, admin portals
scripts/         # Migrations, diagnostics, test suite
```

---

## Kakonko Coverage

The system is seeded for **Kakonko District** administrative units — 13 wards including Kakonko, Kasanda, Kasuga, Katanga, Mwamala, and others — with villages aligned to national statistics sources. See [ward.md](ward.md) for the full ward–village reference.

---

## License & Contribution

This project supports agricultural digitalisation in Tanzania. For issues, improvements, or deployment support, open an issue on [GitHub](https://github.com/Comradewilliam/agriadvisor/issues).

---

**Agri-Advisory** — *Bringing AI extension services to every farmer in Kakonko, on the phone they already have.*
