# Agri-Advisory — USSD & SMS

## SMS types

| Type | When | Method | From → To |
|------|------|--------|-----------|
| **Two-way** | Farmer texts short code 5852 | `sendTwoWayReply()` | `5852` → farmer |
| **One-way** | Welcome, OTP, alerts, broadcasts | `sendOneWay()` | `AFRICASTKNG` → farmer |

## USSD

All answers are shown **on USSD** — no SMS follow-up for menu options.

| Option | Behaviour |
|--------|-----------|
| 1 Ushauri | AI/rules answer shown in USSD `END` (max ~182 chars) |
| 2 Hali ya Hewa | Weather shown in USSD |
| 3 Afisa | Officer names & phones shown in USSD |
| Registration | Welcome sent as **one-way SMS** from AFRICASTKNG |

## `.env`

```env
AT_USERNAME=sandbox
AT_API_KEY=your_key
AT_SANDBOX=1
AT_SHORT_CODE=5852
AT_BULK_SENDER_ID=AFRICASTKNG
AT_SSL_VERIFY=0
```

## Africa's Talking dashboard

| Callback | URL |
|----------|-----|
| USSD | `https://yourdomain.com/ussd` |
| SMS Inbox (two-way) | `https://yourdomain.com/sms` |
| Delivery Reports | `https://yourdomain.com/sms/delivery` |
