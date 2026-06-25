# Agri-Advisory System (BwanaShamba) PRD

## 1. Project Overview

The Agri-Advisory System is a smart agriculture platform designed to improve communication and advisory services between farmers and agricultural officers using USSD, SMS, AI assistance, and a web-based management portal. The system mainly targets smallholder farmers who use feature phones and have limited internet access. The platform combines low-bandwidth technologies with artificial intelligence to create a scalable and accessible agricultural support ecosystem.

The system introduces an AI agricultural assistant called **BwanaShamba**, which helps farmers through SMS-based conversations in Kiswahili. The platform also provides management tools for agricultural officers and administrators to monitor farmers, send alerts, manage knowledge bases, schedule visits, and analyze agricultural data.

The initial deployment focuses on Kakonko District, but the architecture is designed to support multiple regions and districts in future expansion.

---

# 2. Objectives

## Primary Objectives

* Improve farmer access to agricultural advisory services.
* Reduce response time between farmers and agricultural officers.
* Provide AI-powered agricultural support through SMS.
* Improve agricultural monitoring and reporting for district officers.
* Deliver localized weather and farming alerts.
* Support low-connectivity environments using USSD and SMS technologies.

## Secondary Objectives

* Create a scalable digital extension service platform.
* Improve farmer engagement and productivity.
* Digitize agricultural officer workflows.
* Collect agricultural insights and analytics for planning.

---

# 3. Target Users

## Farmers

Smallholder farmers using:

* Feature phones
* Smartphones
* Limited internet connectivity

Farmers interact with the system using:

* USSD
* SMS
* Web portal

## Agricultural Officers

### District Agricultural Officers

Responsibilities:

* Manage ward officers
* Monitor district activities
* Approve alerts
* Access analytics
* Manage knowledge base

### Ward Agricultural Officers

Responsibilities:

* Manage assigned wards
* Respond to escalated farmer issues
* Schedule visits
* Manage farmer communication
* Update advisories

## Super Administrator

Responsibilities:

* Manage entire platform
* Manage regions and districts
* Manage officers
* Configure system settings
* Monitor AI performance
* Manage permissions and audit logs

---

# 4. System Architecture

The platform contains three main layers:

## 4.1 Farmer Access Layer

Channels:

* USSD
* SMS
* Web Portal

Purpose:

* Registration
* AI chat
* Weather updates
* Crop tracking
* Visit requests
* Notifications

---

## 4.2 Officer Management Portal

Web-based dashboard for:

* Farmer management
* Officer management
* Advisory management
* Analytics
* Visit scheduling
* AI escalation handling
* Broadcast messaging

---

## 4.3 AI Advisory Engine (BwanaShamba)

Functions:

* Respond to agricultural questions
* Detect uncertainty
* Escalate complex issues
* Support Kiswahili conversations
* Generate farming recommendations

---

# 5. Technology Stack

## Backend

* Pure PHP
* Server-rendered architecture

## Database

* MySQL

## Frontend

* HTML
* CSS
* JavaScript

## Integrations

* Africa’s Talking SMS/USSD Gateway
* OpenAI API
* Weather API

## Hosting

* Shared hosting
* Government hosting environment

---

# 6. Authentication & Security

## Farmer Authentication

* Phone number + OTP
* OTP expires after 3 minutes
* Previous OTP removed after new request

## Officer Authentication

* Email and password

## Security Features

* HTTPS
* Role-based access control
* Audit logging
* Soft delete support
* Session management

---

# 7. Farmer Registration Flow

## USSD Registration Process

1. Farmer dials USSD code.
2. System checks if phone number exists.
3. If registered:

   * Display main menu.
4. If not registered:

   * Start registration process.

## Registration Fields

* First name
* Last name
* District
* Ward
* Village
* Main crop
* Gender
* Date of birth

## Additional Web Fields

* Secondary crops
* Farm size

---

# 8. USSD Features

USSD is mainly used for:

* Registration
* Quick menu access
* Redirecting to SMS AI chat

## Main Menu

1. Ask AI
2. Crop Advice
3. Weather
4. My Officer
5. Visit Status

## Ask AI Flow

When selected:

* System sends SMS:
  “Unaweza kuniuliza chochote kuhusu kilimo chako.”

Conversation then continues through SMS.

---

# 9. SMS AI Chat System

## Core Features

* AI-powered agricultural chat
* Kiswahili-first communication
* Daily conversation threads
* Officer escalation support

## AI Scope

AI only answers agriculture-related questions including:

* Crop diseases
* Fertilizers
* Irrigation
* Pest control
* Harvesting
* Planting
* Weather effects

If unrelated:

* AI politely rejects the question.

---

## Escalation Workflow

If AI is unsure:

1. Conversation escalates to officer.
2. Farmer receives:

   * Officer name
   * Officer contact
3. Officer accesses:

   * Full conversation history
   * Farmer profile
   * Crop details
   * Village information
   * Previous issues

AI continues assisting during officer involvement.

---

# 10. Farmer Web Portal

## Features

* OTP login
* Dashboard overview
* Weather updates
* Crop progress tracking
* AI chat access
* Notifications
* Visit requests
* Officer details
* Motivational messages

---

## Crop Progress Tracking

Farmers manually select:

* Current crop stage
* Duration in current stage

The system:

* Predicts next stages
* Estimates harvesting timeline
* Displays progress percentage
* Shows encouragement messages

---

# 11. Weather Intelligence Module

## Supported Weather Features

* Current weather
* Daily forecast
* Seasonal forecast
* Rainfall prediction
* Agricultural risk alerts

## Alert Types

* Heavy rain
* Drought
* Strong wind
* Pest outbreak
* Planting windows

## Approval Workflow

Before sending alerts:

* Super Admin
* District Officer
* Ward Officer

must approve alerts.

Unapproved alerts automatically expire.

---

# 12. Officer Management System

## Ward Assignment Rules

* Officer can manage max 3 wards
* One ward can have max 2 officers

## Officer Capabilities

* Manage farmers
* Respond to escalations
* Schedule visits
* Send broadcasts
* Manage advisories
* Add internal notes

Ward officers cannot delete farmers.

Only:

* Super Admin
* District Officer

can delete records.

---

# 13. Visit Management System

## Farmer Requests

Farmers can:

* Request urgent visits
* Track request status

## Officer Actions

Officers can:

* Approve requests
* Reject requests
* Reschedule visits
* Mark issue status

## Issue Statuses

* Open
* In Progress
* Resolved
* Closed

---

# 14. Advisory Knowledge Base

## Structure

Crop → Growth Stage → Problem → Solution

## Features

* Draft advisories
* Publish/unpublish
* Archive advisories
* Version tracking

---

# 15. Broadcast Messaging System

Officers can send messages to:

* Villages
* Wards
* Crop groups
* Individual farmers
* Selected farmers

## Message Types

* Weather alerts
* Farming tips
* Emergency alerts
* General announcements

---

# 16. Analytics & Reporting

## Dashboard Analytics

* Farmer registrations
* Active farmers
* AI usage
* Escalation rates
* Officer response times
* Crop trends
* Weather alert performance
* Unresolved issues
* Farmer engagement

## Reporting

Export support:

* PDF
* Excel

---

# 17. Audit Logging

System logs track:

* Logins
* Farmer edits
* Officer responses
* Weather approvals
* Deletions
* AI escalations

Soft deletion is used for recoverability.

---

# 18. Database Design

## Main Tables

* farmers
* officers
* wards
* villages
* crops
* farmer_crops
* advisories
* advisory_stages
* conversation_threads
* messages
* visit_requests
* weather_alerts
* audit_logs
* otp_codes
* broadcasts

---

# 19. Non-Functional Requirements

## Performance

* Fast USSD response time
* SMS delivery optimization

## Scalability

* Multi-region support
* Multi-district support
* Multi-tenant ready architecture

## Availability

* High uptime
* Backup systems

## Usability

* Kiswahili-first UI
* Minimal navigation steps

## Security

* HTTPS
* Access control
* Audit logs

---

# 20. Future Features

Planned future improvements:

* Voice/IVR support
* Image upload for disease detection
* PDF advisory resources
* Market price integration
* Geo-based farmer tracking
* Mobile application

---

# 21. MVP Scope

## Included in Phase 1

* Farmer registration
* USSD integration
* SMS AI chat
* Officer dashboard
* Weather alerts
* Visit management
* Knowledge base
* Broadcast messaging
* Analytics dashboard

## Deferred Features

* Voice support
* Image analysis
* Market prices
* Mobile app

---

# 22. Success Metrics

The project will be considered successful if it achieves:

* Increased farmer engagement
* Faster advisory response times
* Increased officer productivity
* High SMS interaction rate
* Reduced unresolved farming issues
* Improved advisory accessibility
* Positive farmer feedback

---

# 23. Project Name

## Official Platform Name

Agri-Advisory System

## AI Assistant Name

BwanaShamba
