# WiFi Device Management

Authorized, consent-based Windows device management starter.

## Structure

- admin-backend/ — PHP API at http://localhost:8000/api
- admin-panel/ — web admin dashboard
- electron-agent/ — Electron desktop agent
- database/ — MySQL schema
- docs/ — architecture notes

This project does not collect or exfiltrate stored Wi-Fi passwords and is intended for authorized/company-managed devices.

## Quick start

### PHP API
```bash
cd admin-backend
php -S localhost:8000 -t public
```

### Electron agent
```bash
cd electron-agent
npm install
npm start
```
