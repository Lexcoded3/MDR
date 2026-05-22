# MDR — TB Patient Treatment Management System

<p align="center">
  <img src="YOUR_BANNER_IMAGE_URL" alt="MDR Banner" width="600" height="300"/>
</p>

<p align="center">
  <a href="#">
    <img title="MDR System" src="https://img.shields.io/badge/MDR-TB Management System-green?colorA=%23ff0000&colorB=%23017e40&style=for-the-badge"/>
  </a>
  <a href="https://github.com/Lexcoded3">
    <img title="GitHub" src="https://img.shields.io/badge/GitHub-Lexcoded3-black?style=for-the-badge&logo=github"/>
  </a>
</p>

---

## 📌 Overview

**MDR** (Multi-Drug Resistant) is a healthcare management system designed to streamline the treatment of TB patients. The system tracks patient medication assignments and automatically triggers **scheduled SMS reminders** via the **Africa's Talking API** — ensuring patients never miss a dose and healthcare workers stay informed in real time.

---

## ⚡ Features

- ✅ TB patient registration & profile management
- ✅ Medication assignment & treatment plan tracking
- ✅ Automated SMS dose reminders via Africa's Talking
- ✅ Scheduled notification pipelines
- ✅ Treatment progress monitoring
- ✅ Staff & healthcare worker management
- ✅ Clean, responsive UI
- ✅ Real-time patient status updates
- ✅ Automated results-ready alerts to patients & staff via SMS

---

## 🛠️ Built With

<p align="center">
  <img src="https://img.shields.io/badge/PHP-Backend-blue?style=for-the-badge&logo=php"/>
  <img src="https://img.shields.io/badge/Tailwind CSS-Styling-38B2AC?style=for-the-badge&logo=tailwind-css"/>
  <img src="https://img.shields.io/badge/Alpine.js-Interactivity-8BC0D0?style=for-the-badge&logo=alpine.js"/>
  <img src="https://img.shields.io/badge/Bootstrap-UI-7952B3?style=for-the-badge&logo=bootstrap"/>
  <img src="https://img.shields.io/badge/HTML5-Markup-E34F26?style=for-the-badge&logo=html5"/>
  <img src="https://img.shields.io/badge/Africa's Talking-SMS Gateway-brightgreen?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/MySQL-Database-orange?style=for-the-badge&logo=mysql"/>
</p>

---

## 🔄 How It Works

```
Patient Registered
       ↓
Assigned to Facility
       ↓
Patient Data Captured
       ↓
Status → ENROLLED
       ↓
Drugs Assigned to Patient
       ↓
Status → ON MEDICATION
       ↓
┌──────────────────────────────────────┐
│  Patient logs in & reports           │
│  adverse effects                     │
└──────────────────────────────────────┘
       ↓
Lab Feeds Test Results
       ↓
Results-Ready Alert Sent via SMS
       ↓
Doctor Reviews & Approves Regimen
       ↓
Lab Reviews & Confirms
       ↓
SMS Dose Reminders Triggered
       ↓
Africa's Talking API → Patient SMS
```
---

## 🔐 Environment Variables

Add the following to your `.env` or config file:

| Variable | Description |
|---|---|
| `DB_HOST` | Database host |
| `DB_NAME` | Database name |
| `DB_USER` | Database username |
| `DB_PASS` | Database password |
| `AT_API_KEY` | Africa's Talking API key |
| `AT_USERNAME` | Africa's Talking username |
| `AT_SENDER_ID` | SMS sender ID |

---

## 🚀 Installation

```bash
# Clone the repository
git clone https://github.com/Lexcoded3/MDR.git

# Navigate to project directory
cd MDR

# Configure your database
# Import the SQL file from /database folder

# Set up your environment variables
# Add Africa's Talking credentials

# Run on XAMPP or any PHP server
# Visit http://localhost/MDR
```

---

## 💬 Contact & Support

- **GitHub:** [@Lexcoded3](https://github.com/Lexcoded3)
- **Email:** your@email.com
- **WhatsApp:** [Chat](https://wa.me/256777777861)

---

## ⚠️ License & Usage

This project is the intellectual property of **DARK ALPHA / Lexcoded3**. Unauthorized redistribution or modification is strictly prohibited. Commercial inquiries welcome.

---

## ⚖️ BYTESTORM © 2026 — All Rights Reserved
