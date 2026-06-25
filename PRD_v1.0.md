# Smart Agri-Advisor System — Product Requirements Document

**Version:** 1.0
**Date:** May 2026
**Author:** Young William Sadiki — BIT/30599/2301/DT
**Institution:** Kampala International University in Tanzania
**Stack:** Pure PHP 8.x · MySQL 8.x · Africa's Talking · OpenAI GPT-4 · OpenWeatherMap


## 1. Introduction

### 1.1 Purpose

This PRD defines the complete functional and non-functional specifications for the **Smart Agri-Advisor System** — a multi-channel agricultural advisory platform serving smallholder farmers in Kakonko District, Kigoma Region, Tanzania. It is intended for the development team, project supervisor, and future technical contributors.

### 1.2 Product Overview

The system integrates three access channels:

- **USSD (feature-phone):** Farmer self-registration and on-demand crop advisory menus. No internet required.
- **Two-way SMS + AI:** Farmers send free-text questions; GPT-4 automatically responds using a curated Knowledge Base (KB), escalating to agricultural officers when confidence is low.
- **Web Portal (farmers):** Profile management, SMS chat history, weather information, and scheduled officer visit calendar.
- **Web Portal (officers & admin):** Full farmer management, advisory KB editing, visit planning, bulk messaging, analytics, and user account administration.

### 1.3 Scope

**In scope (v1.0):**
USSD flow via Africa's Talking · SMS gateway integration · AI-powered Q&A via OpenAI GPT-4 · Farmer web portal · Officer web portal · Super Admin panel · Knowledge Base management · Visit planning module · Bulk SMS targeting · Weather API integration · Analytics dashboard.

**Out of scope (v1.0):**
Mobile native apps · Voice/IVR · Marketplace or e-commerce features · Multi-district deployment · Offline PWA · Payment integrations.

### 1.4 Technology Stack

| Layer | Technology |
|---|---|
| Backend Language | Pure PHP 8.x (Laravel 11 recommended or vanilla PHP with MVC structure) |
| Web Server | Nginx or Apache |
| Database | MySQL 8.x |
| USSD / SMS Gateway | Africa's Talking API |
| AI Engine | OpenAI GPT-4 API (chat completions) |
| Weather API | OpenWeatherMap API or equivalent |
| Frontend | Blade templates (Laravel) or plain PHP templates + Bootstrap 5 + Vanilla JS |
| Authentication | Session-based PHP auth + OTP via Africa's Talking SMS |
| Hosting | VPS / Cloud VM (Ubuntu 22.04 LTS) |
| Version Control | Git |

---

## 2. User Roles & Permissions

| Role | Access | Created By | Scope |
|---|---|---|---|
| **Super Admin** | Full system access: all wards, all farmers, all officers, system settings, crop list management, audit logs | System seeded / first account | District-wide |
| **District Agricultural Officer (DAO)** | All officers' data, all wards read, create Ward Officer accounts, view all analytics, broadcast messages district-wide. Cannot edit system settings. | Super Admin | District-wide (read + messaging) |
| **Ward Agricultural Officer** | Own assigned ward(s) only: farmer profiles, crop data, visit planning, messaging, KB content creation/editing | Super Admin or DAO | One or more assigned wards |
| **Farmer (Web)** | Own profile, own SMS chat history, own weather, own scheduled visits | Auto-created on first USSD registration | Own account only |

> **Ward enforcement:** A Ward Officer can be assigned to one or multiple wards. All DB queries for farmers, visits, queries, and messages are filtered with `WHERE ward_id IN (officer's assigned wards)` on every officer-facing request.

---

## 3. USSD Module

### 3.1 Entry Point

Farmers dial a short USSD code (e.g. `*384*XXXX#`) provisioned via Africa's Talking. The system detects the farmer's phone number from the USSD session and checks registration status.

### 3.2 Registration Flow (New Farmer)

Triggered when phone number is **not found** in the `farmers` table.

| Step | System Prompt | Farmer Input | Notes |
|---|---|---|---|
| 1 | Welcome to Shamba Smart! Enter your full name: | Free text (typed) | Stored as `farmer.name` |
| 2 | Select your Ward: 1. Kakonko  2. Bulyaheke  3. Kasuga  4. [others…] | Numeric selection | Ward list loaded from DB |
| 3 | Select your Village: 1. [Village A]  2. [Village B]  … | Numeric selection | Filtered by selected ward |
| 4 | Select Primary Crop: 1. Maize  2. Cassava  3. Beans  4. Groundnuts  5. Oil Palm | Numeric selection | Fixed crop list v1.0 |
| 5 | Select Secondary Crop 1 (or 0 to skip): 0. Skip  1–5. [Crops] | Numeric or 0 | Optional |
| 6 | Select Secondary Crop 2 (or 0 to skip): 0. Skip  1–5. [Crops] | Numeric or 0 | Optional; skipped if step 5 was skipped |
| 7 | Registration complete! Welcome [Name]. Dial same code for advisory. SMS questions to [shortcode]. | — | Farmer account created; web account auto-generated |

### 3.3 Main Menu (Registered Farmer)

After dialling and phone number found in DB:

```
1. Crop Advisory
2. Weather Info
3. Market Prices
4. My Profile
5. Contact Officer
```

### 3.4 Crop Advisory Flow

Depth: **Crop → Growth Stage → Topic → Display Advice**

| Level | Options | Notes |
|---|---|---|
| Select Crop | 1. Maize  2. Cassava  3. Beans  4. Groundnuts  5. Oil Palm | Shows farmer's registered crops first (★ marked) |
| Select Growth Stage | 1. Land Prep  2. Planting  3. Early Growth  4. Flowering  5. Pest/Disease  6. Harvest | Per-crop stages managed in KB |
| Select Topic | 1. Planting Guide  2. Fertilizer  3. Pest Control  4. Irrigation  5. Harvest Tips | Topics available per stage |
| Display Advice | Short advisory text (≤160 chars, Kiswahili primary) | Followed by: *"SMS us for more details: [shortcode]"* |

### 3.5 Other USSD Menus

#### 3.5.1 Weather Info
Fetches weather for farmer's registered ward/village using Weather API. Displays: Today's weather, temperature, rain forecast for next 2 days (condensed to fit USSD character limit).

#### 3.5.2 Market Prices
Displays latest market prices for farmer's primary crop and up to 2 secondary crops, sourced from officer-uploaded price data in the dashboard.

#### 3.5.3 My Profile
Shows: Name, Ward, Village, Primary Crop, Secondary Crops. Option to edit name (free-text re-entry). Ward/village changes require officer confirmation.

#### 3.5.4 Contact Officer
Displays the ward officer's name and prompt: *"Tuma swali lako kwa SMS kwa [shortcode] na afisa au AI itajibu hivi karibuni."*

### 3.6 USSD Technical Specifications

- **Gateway:** Africa's Talking USSD API (webhook-based, POST to PHP endpoint)
- **Session handling:** Africa's Talking provides `sessionId` per session; PHP tracks session state in `ussd_sessions` table
- **Response type:** `CON` (continue session) vs `END` (close session)
- **Character limit:** ≤ 182 characters per response screen
- **Language:** Kiswahili primary; English option in profile settings (v1.1)
- **Timeout:** Sessions auto-end after telco timeout (~180 seconds); state stored in DB for analytics
- **Invalid input:** Show error message and re-display current menu (max 2 retries before ending session)

---

## 4. Two-Way SMS + AI Module

### 4.1 Overview

Farmers send free-text SMS questions to the Africa's Talking shortcode. The system processes each inbound message through an AI triage pipeline powered by OpenAI GPT-4, with the Knowledge Base as context, and escalates to a Ward Officer when the AI confidence is insufficient.

### 4.2 Inbound SMS Processing Flow

| Step | Action | Detail |
|---|---|---|
| 1 | Receive webhook | Africa's Talking POSTs to PHP endpoint: `from`, `to`, `text`, `date` |
| 2 | Identify farmer | Look up phone number in `farmers` table. If not found → reply *"Please register first by dialling \*384\*XXXX#"* |
| 3 | Log message | Store in `sms_messages`: `farmer_id`, `direction=IN`, `content`, `timestamp` |
| 4 | Search KB | Full-text search on `knowledge_base` table for matching crop/topic keywords |
| 5a | KB match — HIGH confidence | Build GPT-4 prompt with KB context + farmer profile + question. Send SMS reply. Log response. Flag for officer review (low priority). |
| 5b | KB match — MEDIUM confidence | Same as 5a but response appends: *"Kwa uhakika zaidi, wasiliana na afisa wako."* Officer gets dashboard notification. |
| 5c | No KB match — LOW confidence | Send acknowledgement SMS: *"Tumeipokea swali lako. Afisa atajibu hivi karibuni."* Escalate to officer dashboard queue as **URGENT**. |
| 6 | Officer escalation | Ward officer sees pending query in dashboard, responds manually. Response triggers SMS to farmer + KB update option. |

### 4.3 GPT-4 System Prompt Strategy

- **System role:** *"You are an agricultural advisory assistant for smallholder farmers in Kakonko District, Tanzania. Answer only farming-related questions. Respond in Kiswahili. Keep responses under 300 characters. Use knowledge from the provided context only."*
- **Context injection:** Top 3 relevant KB entries injected as context.
- **Farmer profile context:** Crop(s), ward, current season month injected.
- **Confidence flag:** If GPT-4 response contains uncertainty phrases (`sijui`, `sijui kwa uhakika`, `I don't know`), system auto-downgrades to MEDIUM/LOW confidence.
- **Disclaimer appended:** *"Jibu hili limetolewa na AI. Wasiliana na afisa kwa uthibitisho."*

### 4.4 SMS Message Rules

- Max SMS length: 160 characters for single SMS; system splits to multi-part if needed (max 3 parts)
- Language: Kiswahili primary
- Rate limiting: Max 5 inbound queries per farmer per day to prevent spam
- Delivery receipts: Africa's Talking delivery callbacks stored in `sms_logs` table

---

## 5. Farmer Web Portal

### 5.1 Authentication (OTP Flow)

| Step | Action |
|---|---|
| 1 – Enter Phone | Farmer visits web portal, enters registered phone number |
| 2 – OTP Sent | System sends 6-digit OTP via Africa's Talking SMS (valid 10 minutes, one-time use) |
| 3 – OTP Entry | Farmer enters OTP on web form |
| 4 – Session Created | PHP session created; farmer is logged in |
| 5 – Re-auth | Session expires after 24 hours; re-login required |

### 5.2 Portal Sections

#### 5.2.1 Dashboard / Home
Summary card showing: name, ward, village, primary crop icon, weather widget for their location, and next scheduled officer visit (if any).

#### 5.2.2 My Profile
- **Editable:** Full name, primary crop, secondary crops (up to 2), preferred language (Kiswahili / English)
- **Read-only:** Phone number, ward, village (changes require officer action)

#### 5.2.3 SMS Chat History
- Chronological conversation thread of all inbound and outbound SMS exchanges
- Each message shows: timestamp, direction (Sent / Received), message text, responder type (AI or Officer name)
- Farmer can send new questions directly from this web view — routed through same AI pipeline
- Max display: last 90 days, paginated

#### 5.2.4 Weather
Full weather widget: current conditions (temperature, humidity, wind), 5-day forecast, rainfall probability, and a crop-specific advisory banner. Powered by Weather API using farmer's ward/village GPS coordinates from the `villages` table.

#### 5.2.5 Officer Visits
- Calendar and list view of all visits scheduled by the officer
- Each visit card: date, time, officer name, purpose/notes, status (Scheduled / Completed / Cancelled)
- Farmer cannot create or edit visits — **view only**
- SMS notification sent automatically when a visit is scheduled by an officer

---

## 6. Officer Web Portal

### 6.1 Authentication
Officers log in with **email + password** (bcrypt hashed). Password reset via email link. Session expires after 8 hours of inactivity.

### 6.2 Dashboard Overview
Summary panel: total farmers in assigned ward(s), pending SMS queries requiring response, upcoming visits this week, recent farmer activity, quick stats (messages sent today, KB articles, active farmers).

### 6.3 Farmer Management

#### 6.3.1 Farmer List
Paginated, searchable, filterable table — scoped to officer's assigned wards.

**Filters:** Ward (if multiple assigned) · Village · Primary crop · Secondary crop · Registration date range

**Columns:** Name · Phone · Ward · Village · Primary Crop · Secondary Crops · Registered Date · Last Activity · Actions

#### 6.3.2 Farmer Profile View
Full farmer profile: personal details, crop registrations, full SMS chat history (AI + officer messages), visit history, officer-only internal notes field.

#### 6.3.3 Manual Farmer Registration
Form fields: Name · Phone · Ward (from assigned wards only) · Village · Primary Crop · Secondary Crop 1 · Secondary Crop 2

#### 6.3.4 Farmer Profile Edit
Officer can edit any farmer profile field in their ward. Ward/village transfer to another ward requires DAO or Super Admin approval.

### 6.4 SMS Query Management

#### 6.4.1 Query Inbox
Filters: Status (Pending / AI-Answered / Escalated / Officer-Replied) · Priority (URGENT / NORMAL) · Date range · Crop type · Ward · Village

Each row shows: farmer name, phone, crop, question text, AI response (if any), status, time elapsed.

#### 6.4.2 Responding to a Query
Officer clicks a query → full conversation thread → types manual response → submits → SMS delivered to farmer via Africa's Talking. Officer can also tick **"Add to KB"** to open a pre-filled KB entry form using the Q&A as source.

### 6.5 Knowledge Base Management

#### 6.5.1 KB Article List
Columns: Title · Crop · Stage · Topic · Language · Status (Published / Draft) · Created By · Last Updated

Ward Officers see: their ward's articles + system-wide (global) articles.

#### 6.5.2 Create / Edit KB Article

| Field | Detail |
|---|---|
| Crop | Select from fixed list |
| Growth Stage | Select |
| Topic | Select or custom text |
| Title | Short label |
| Short Text | ≤ 160 chars — used in USSD/SMS |
| Long Text | Full advisory — used in web and AI context |
| Language | Kiswahili / English |
| Status | Draft / Published |

Ward Officers can create and edit articles for their assigned wards. Super Admin and DAO can publish or unpublish any article.

### 6.6 Visit Planning

#### 6.6.1 Visit Calendar
Monthly and weekly calendar view of all visits scheduled by the officer. Colour-coded: Scheduled (blue) · Completed (green) · Cancelled (grey).

#### 6.6.2 Schedule Individual Visit

| Field | Detail |
|---|---|
| Farmer | Searchable dropdown from officer's ward |
| Date | Date picker |
| Time | Time picker |
| Purpose | Crop Inspection / Pest Response / Training / Follow-up / Other |
| Notes | Free text |
| Notify farmer via SMS | Checkbox (default: ON) |

**SMS notification sent to farmer:**
> *"Afisa [Name] atakutembelea [Date] saa [Time] kwa ajili ya [Purpose]. Maswali: [Officer phone]."*

#### 6.6.3 Schedule Group Visit
Officer selects multiple farmers via checkbox list (filterable by village/crop). Sets shared date, time, purpose, notes. Each selected farmer receives individual SMS. One visit record created per farmer, all linked to a `group_visit_id` for reporting.

#### 6.6.4 Visit Outcome Recording
After a visit date passes, officer can mark as **Completed** and add outcome notes (observations, recommendations, follow-up needed). Notes visible to farmer on their web portal visit card.

### 6.7 Messaging / Bulk SMS

#### 6.7.1 Compose Message
Message text area (max 160 chars, character counter shown). Targeting options (combinable with AND logic):

| Option | Description |
|---|---|
| By Ward | All farmers in one or more assigned wards |
| By Village | One or more specific villages within assigned wards |
| By Primary Crop | All farmers with that crop as primary |
| By Secondary Crop | All farmers with that crop as primary or secondary |
| By Registration Date Range | Farmers registered between two dates |
| Individual Farmer | Single farmer by name or phone search |
| Custom Selection | Officer ticks individual names from a filtered list |

**Recipient Preview** count shown before sending. Officer must confirm before dispatching.

#### 6.7.2 Message History
Log of all messages sent: message text, targeting criteria, recipient count, sent time, delivery status summary (delivered/failed counts from Africa's Talking callbacks).

### 6.8 Market Prices
Officers upload current market prices: Crop · Market Location · Price per Kg (TZS) · Unit · Date.
Prices displayed in USSD market prices menu and on the farmer web portal.

---

## 7. Super Admin Panel

### 7.1 User Management
- Create, edit, deactivate Super Admin accounts
- Create DAO accounts (email, name, phone, password)
- Create Ward Officer accounts: email, name, phone, expertise, assign wards (one or multiple), password
- View all officer accounts with assigned wards
- Reset any officer password

### 7.2 System Settings
- **Wards:** Add / edit ward names
- **Villages:** Add / edit villages, assign to ward, set GPS coordinates (lat/lng for weather API)
- **Crop List:** Add new crops, deactivate crops
- **Growth Stages:** Add / edit stages per crop
- **Topics:** Add / edit advisory topics per stage
- **USSD short code** configuration
- **SMS shortcode / sender ID** configuration
- **API keys management:** Africa's Talking, OpenAI, Weather API (stored encrypted in `.env`)

### 7.3 Analytics & Reports
- Total farmers registered (by ward, village, crop, date range)
- USSD session statistics (sessions/day, menu paths, completion rates)
- SMS volume (inbound/outbound, AI-resolved vs officer-escalated)
- Officer activity (queries handled, visits completed, KB contributions, messages sent)
- KB usage (most-accessed articles, most-asked topics)
- Export all reports to **CSV**

### 7.4 Audit Log
Read-only log of all system events: user logins, farmer edits, KB changes, messages sent, officer account changes.

**Columns:** Timestamp · Actor (role + name) · Action · Affected Entity · IP Address

---

## 8. Database Schema

| Table | Key Fields | Notes |
|---|---|---|
| `users` | id, name, email, password_hash, role (`super_admin\|dao\|ward_officer\|farmer`), phone, is_active, created_at | All system users in one table |
| `officer_wards` | id, officer_id (FK), ward_id (FK) | M:M assignment of officers to wards |
| `wards` | id, name, district, created_at | Kakonko wards list |
| `villages` | id, name, ward_id (FK), lat, lng, network_quality | GPS coords for weather API calls |
| `farmers` | id, user_id (FK, nullable), name, phone (UNIQUE), ward_id (FK), village_id (FK), is_active, registered_via (`ussd\|web`), registered_at | Core farmer table |
| `farmer_crops` | id, farmer_id (FK), crop_id (FK), type (`primary\|secondary`), order (1\|2\|3) | One primary + up to 2 secondary |
| `crops` | id, name_sw, name_en, is_active, created_at | Fixed list; admin-expandable |
| `growth_stages` | id, crop_id (FK), name_sw, name_en, sort_order | Per-crop stages |
| `advisory_topics` | id, stage_id (FK), name_sw, name_en, sort_order | Topics per stage |
| `knowledge_base` | id, crop_id (FK), stage_id (FK), topic_id (FK), title, short_text (≤160), long_text, language, status (`draft\|published`), created_by (FK), ward_id (FK, NULL=global), updated_at | Ward-specific or global articles |
| `sms_messages` | id, farmer_id (FK), direction (`in\|out`), content, responder_type (`ai\|officer\|system`), responder_id (FK, nullable), ai_confidence (`high\|medium\|low\|null`), delivery_status, sent_at | Full SMS chat log |
| `sms_escalations` | id, sms_message_id (FK), assigned_officer_id (FK), status (`pending\|responded`), priority (`urgent\|normal`), escalated_at, responded_at | Officer query queue |
| `ussd_sessions` | id, farmer_id (FK), session_id (AT), menu_path (JSON), start_time, end_time, completed (bool) | Africa's Talking session tracking |
| `visits` | id, farmer_id (FK), officer_id (FK), group_visit_id (nullable), date, time, purpose, notes, status (`scheduled\|completed\|cancelled`), outcome_notes, notified_at, created_at | Individual and group visits |
| `group_visits` | id, officer_id (FK), date, time, purpose, notes, created_at | Group visit parent record |
| `market_prices` | id, crop_id (FK), ward_id (FK), market_location, price_per_kg, unit, uploaded_by (FK), recorded_date | Crop market prices |
| `bulk_messages` | id, officer_id (FK), message_text, targeting_criteria (JSON), recipient_count, sent_at, delivery_summary (JSON) | Bulk SMS campaigns |
| `otp_tokens` | id, phone, token_hash, expires_at, used_at | OTP for farmer web login |
| `system_logs` | id, actor_id (FK, nullable), action, entity_type, entity_id, meta (JSON), ip_address, created_at | Audit trail |

---

## 9. External API Integrations

### 9.1 Africa's Talking (USSD + SMS)

| Function | Direction | PHP Endpoint | Notes |
|---|---|---|---|
| USSD session | Inbound POST from AT | `/api/ussd` | Returns `CON`/`END` text response |
| Send SMS | Outbound POST to AT | AT SDK / cURL | OTP · AI responses · Officer replies · Visit notifications · Bulk SMS |
| Delivery receipts | Inbound POST from AT | `/api/sms/delivery` | Updates `sms_messages.delivery_status` |
| Inbound SMS | Inbound POST from AT | `/api/sms/inbound` | Triggers AI pipeline |

### 9.2 OpenAI GPT-4

| Parameter | Value |
|---|---|
| Endpoint | `https://api.openai.com/v1/chat/completions` |
| Model | `gpt-4` (or `gpt-4-turbo` for cost efficiency) |
| Max tokens | 200 (SMS-length responses) |
| Temperature | 0.3 (factual, consistent) |
| System prompt | Agricultural advisor · Kiswahili · KB context injected · farmer profile injected |
| Timeout | 10 seconds; if exceeded → auto-escalate to officer |
| Cost control | KB full-text search first; GPT-4 called only if needed |

### 9.3 Weather API

| Parameter | Value |
|---|---|
| Provider | OpenWeatherMap (current + 5-day forecast endpoints) |
| Trigger | USSD weather menu + Farmer web portal weather page |
| Location input | `lat`/`lng` from `villages.lat` + `villages.lng` |
| Cache | Response cached per village for 1 hour in `weather_cache` table |
| Fallback | If API fails, show last cached data with *"Data as of [timestamp]"* notice |

---

## 10. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Performance** | USSD response < 3 seconds · SMS AI response < 15 seconds · Web page load < 2 seconds on 3G |
| **Availability** | 99% uptime target during pilot (06:00–22:00 EAT) · Maintenance window: 02:00–04:00 EAT |
| **Security** | HTTPS/TLS for all web traffic · Passwords hashed with bcrypt · API keys in `.env` only · OTP expires in 10 minutes · Role-based access enforced server-side · Prepared statements for all DB queries · Output escaping for XSS prevention |
| **Privacy** | Minimal PII stored · Farmer phone numbers not exposed in officer bulk export · USSD sessions deleted after 90 days · SMS logs retained 12 months |
| **Usability** | USSD menus ≤ 5 options per screen · Kiswahili primary · Web portal accessible on mobile browser · SUS score target ≥ 68 in pilot usability testing |
| **Scalability** | DB indexes on `farmer.phone`, `ward_id`, `village_id`, `crop_id` · Application stateless for horizontal scaling · Config-driven crop/stage/topic lists |
| **Localization** | Kiswahili primary in USSD and SMS · Web portal supports Kiswahili + English toggle · All KB articles stored with `language` field |
| **Reliability** | Africa's Talking webhook failures retried 3 times · GPT-4 API timeout falls back to officer escalation · Daily automated DB backups |
| **Testability** | Separate AT sandbox credentials for development · Seeded test data for all roles · Each USSD flow unit-testable via PHP CLI |

---

## 11. USSD Session State Machine

The USSD module uses a stateful session managed via the `ussd_sessions` table. Each USSD request from Africa's Talking carries a `sessionId`. The PHP handler reads the current `menu_path` from the DB and determines the next screen.

| State Key | Description | Next State on Input |
|---|---|---|
| `START` | Check if registered | NOT_REGISTERED → `REGISTER_NAME` \| REGISTERED → `MAIN_MENU` |
| `REGISTER_NAME` | Prompt: Enter your name | → `REGISTER_WARD` |
| `REGISTER_WARD` | Show ward list | → `REGISTER_VILLAGE` |
| `REGISTER_VILLAGE` | Show village list for chosen ward | → `REGISTER_PRIMARY_CROP` |
| `REGISTER_PRIMARY_CROP` | Show crop list | → `REGISTER_SECONDARY_1` |
| `REGISTER_SECONDARY_1` | Show crop list + skip | → `REGISTER_SECONDARY_2` or `REGISTER_DONE` |
| `REGISTER_SECONDARY_2` | Show crop list + skip | → `REGISTER_DONE` |
| `REGISTER_DONE` | Show confirmation + END | Session closed |
| `MAIN_MENU` | Options 1–5 | → respective sub-state |
| `CROP_SELECT` | Show crops | → `STAGE_SELECT` |
| `STAGE_SELECT` | Show growth stages | → `TOPIC_SELECT` |
| `TOPIC_SELECT` | Show topics | → `ADVICE_DISPLAY` |
| `ADVICE_DISPLAY` | Show advice text + END | Session closed |
| `WEATHER_DISPLAY` | Show weather + END | Session closed |
| `MARKET_DISPLAY` | Show prices + END | Session closed |
| `PROFILE_VIEW` | Show profile; 1-Edit name, 0-Back | → `PROFILE_EDIT` or `MAIN_MENU` |
| `OFFICER_CONTACT` | Show officer name + SMS tip + END | Session closed |

---

## 12. Security Specifications

### 12.1 Authentication Summary

| Actor | Method | Session Duration |
|---|---|---|
| Super Admin / DAO / Ward Officer | Email + password (bcrypt). No OTP. | 8 hours inactivity timeout |
| Farmer (web) | Phone number + OTP (6-digit, valid 10 min, one-time use) | 24 hours |

### 12.2 Authorization Rules

- Every PHP controller method checks role and ward assignment before querying DB.
- Ward Officers: all DB queries filtered with `WHERE ward_id IN (officer's assigned wards)`.
- DAO: can query all wards but cannot access Super Admin settings.
- Farmers: can only read their own records. No cross-farmer access possible.
- USSD/SMS webhooks: validated with Africa's Talking HMAC signature header before processing.
- OpenAI and Weather API keys stored in server-side `.env` file only. Never exposed to browser.

---

## 13. Recommended PHP File Structure

Applies to both Laravel and custom vanilla PHP MVC setup. **Laravel 11 strongly recommended.**

```
app/
├── Http/
│   └── Controllers/
│       ├── USSD/           # Session handler, state machine logic
│       ├── SMS/            # Inbound SMS handler, delivery receipt handler
│       ├── AI/             # GPT-4 service wrapper, KB search, confidence scoring
│       ├── Farmer/         # Farmer web portal: profile, chat, weather, visits
│       ├── Officer/        # Officer portal: farmers, queries, visits, messaging, KB
│       └── Admin/          # Super admin: users, wards, crops, settings, analytics
├── Models/                 # Eloquent models: Farmer, Officer, KnowledgeBase, Visit, SmsMessage…
└── Services/
    ├── AfricasTalkingService.php   # Send SMS, parse USSD webhooks, delivery receipts
    ├── OpenAIService.php           # GPT-4 API wrapper, prompt builder, confidence assessor
    ├── WeatherService.php          # Weather API fetch + cache logic
    └── OtpService.php              # Generate, store, verify OTP tokens

routes/
├── ussd.php       # USSD webhook route
├── sms.php        # SMS inbound + delivery routes
├── api.php        # Internal API routes (if needed)
└── web.php        # All web portal routes

resources/views/
├── farmer/        # Farmer portal Blade/PHP templates
├── officer/       # Officer portal templates
└── admin/         # Admin panel templates

database/
├── migrations/    # All DB migration files
└── seeders/       # Test data: wards, villages, crops, stages, topics, test farmers

.env               # AT API key/secret, OpenAI key, Weather API key, DB credentials, App URL
```

---

## 14. Development Phases & Milestones

| Phase | Sprint | Deliverables | Duration |
|---|---|---|---|
| **Phase 1: Foundation** | Sprint 1 | DB schema migrations, seeders (wards/villages/crops/stages/topics), Super Admin auth, Officer auth, Farmer USSD registration flow (AT sandbox) | 2 weeks |
| **Phase 1: Foundation** | Sprint 2 | USSD full menu tree (advisory, weather, market, profile, contact officer), Africa's Talking webhook handler, session state machine | 2 weeks |
| **Phase 2: Core Features** | Sprint 3 | Inbound SMS handler, GPT-4 integration, KB search, AI confidence scoring, Farmer web portal (OTP auth, profile, chat history) | 2 weeks |
| **Phase 2: Core Features** | Sprint 4 | Officer portal: farmer list, farmer profile view/edit, SMS query inbox, manual reply, escalation queue | 2 weeks |
| **Phase 3: Officer Tools** | Sprint 5 | KB management (CRUD), visit planning (individual + group), visit SMS notifications, farmer visit calendar on web portal | 2 weeks |
| **Phase 3: Officer Tools** | Sprint 6 | Bulk messaging (all targeting options + preview), market prices upload, weather widget (farmer + officer) | 2 weeks |
| **Phase 4: Admin & Polish** | Sprint 7 | Super Admin panel (user management, system settings, audit log), DAO account + permissions, analytics dashboard + CSV export | 2 weeks |
| **Phase 4: Admin & Polish** | Sprint 8 | Security hardening, performance optimisation, SUS usability testing with 8–10 farmers, bug fixes, pilot deployment | 2 weeks |

**Total estimated development time: 16 weeks (4 months).** Pilot: 12 weeks after deployment.

---

## 15. Acceptance Criteria (Go/No-Go for Pilot)

| Feature | Acceptance Criterion |
|---|---|
| USSD Registration | Farmer can register end-to-end in ≤ 5 menu steps. Session completes without error on AT sandbox and production. |
| USSD Advisory | Farmer navigates Crop → Stage → Topic → Advice in ≤ 4 steps. Advice text displays within USSD character limit. |
| Inbound SMS + AI | AI responds to a farming question within 15 seconds. Response is in Kiswahili, ≤ 300 chars, and references KB context. |
| SMS Escalation | If AI confidence is LOW, farmer receives acknowledgement SMS within 5 seconds and query appears in officer dashboard. |
| Farmer Web Login | OTP received within 60 seconds, valid for 10 minutes, one-time use. |
| Farmer Web Portal | Profile, chat history, weather, and visits all load correctly on mobile browser. |
| Officer Query Response | Officer can read, respond to, and close a query via dashboard. Farmer receives SMS reply. |
| Visit Scheduling | Officer can schedule individual and group visits. Farmer receives SMS notification. Visit appears in farmer's web portal. |
| Bulk Messaging | Officer can target by ward/village/crop/date range/custom. Preview count shown. Messages delivered via AT. |
| KB Management | Ward Officer can create, edit, and publish KB articles. Published articles appear in USSD advisory and AI context. |
| SUS Score | SUS score ≥ 68 in lab usability test with 8–10 farmers. |
| Security | No SQL injection vulnerabilities. Role boundaries enforced (ward officer cannot see out-of-ward data). |

---

## 16. Glossary

| Term | Definition |
|---|---|
| **USSD** | Unstructured Supplementary Service Data — interactive menu protocol via GSM network, no internet required |
| **AT** | Africa's Talking — SMS/USSD gateway provider |
| **KB** | Knowledge Base — curated database of crop advisory articles used for AI context and USSD menus |
| **DAO** | District Agricultural Officer — senior officer role with district-wide visibility |
| **Ward Officer** | Agricultural extension officer assigned to one or more specific wards |
| **AI Confidence** | Assessment of whether the GPT-4 response is based on sufficient KB context (HIGH / MEDIUM / LOW) |
| **GPT-4** | OpenAI's large language model used for automated SMS query responses |
| **OTP** | One-Time Password — 6-digit code sent via SMS for farmer web portal login |
| **SUS** | System Usability Scale — standardised 10-question usability questionnaire, score ≥ 68 = acceptable |
| **CON / END** | USSD response types: `CON` = continue session (more screens), `END` = close session |
| **TZS** | Tanzanian Shilling — currency used in market price displays |
| **EAT** | East Africa Time — UTC+3, used for all timestamps |

---

*End of Product Requirements Document — Smart Agri-Advisor System v1.0*
