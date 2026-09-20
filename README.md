# WiFi Device Management

Consent-based Windows device management for company-owned or authorized laptops.

### Components
- **Electron Agent** — user enrollment, system tray/background operation, heartbeat, authorized Wi-Fi metadata scan.
- **PHP API** — authentication, device registration, device tokens, scan queue, heartbeat and audit events.
- **Admin Panel** — device dashboard, enrollment codes, Wi-Fi status and Scan Now.
- **MySQL** — admins, devices, scan requests and audit events.

### Quick start
See [docs/setup.md](docs/setup.md).

Base API: `http://localhost:8000/api`

### Important
This project intentionally does not collect or exfiltrate saved Wi-Fi passwords, browser credentials, or other stored secrets. Remote actions are limited to enrolled/authorized devices.
