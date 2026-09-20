# Setup Guide

## 1. Database
Import `admin-backend/database/schema.sql` into MySQL.

## 2. Create the first admin
Set your database environment variables, then run:
```bash
php admin-backend/tools/create_admin.php admin@example.com "StrongPasswordHere" "Administrator"
```

## 3. Start the PHP API
```bash
cd admin-backend
php -S localhost:8000 -t public
```

## 4. Start the admin panel
Serve `admin-panel/` with a local web server. For example:
```bash
cd admin-panel
npx serve .
```
Open the URL shown by the server and sign in.

## 5. Enroll a company laptop
1. Sign in to the admin panel.
2. Click **Generate Enrollment Code**.
3. Install the Windows agent on the company laptop.
4. The user enters the one-time enrollment code and explicitly registers the device.
5. The agent then runs in the background/system tray and sends periodic heartbeats.
6. Admin can click **Scan Now**. The backend queues a request and the authorized agent returns current Wi-Fi metadata.

## What is collected
- Device ID / hostname
- Windows version
- Agent version
- Local IP
- Connected Wi-Fi SSID
- BSSID
- Signal
- Radio type
- Channel
- Online/offline and last heartbeat

The agent does **not** read, reveal, or transmit saved Wi-Fi passwords.

## Production checklist
- Put the API behind HTTPS.
- Restrict CORS to the actual admin domain.
- Store secrets outside source control.
- Add reverse-proxy rate limiting.
- Back up MySQL.
- Add organization/device ownership and RBAC before multi-company deployment.
- Code-sign the Windows installer.
- Publish a privacy/acceptable-use notice and obtain required employee consent.
