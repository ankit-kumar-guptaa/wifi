# Architecture

Electron Agent -> PHP API (/api) -> MySQL
                         ^
                         |
                    Admin Panel

The agent sends a heartbeat and basic non-secret device metadata. Production deployment should use HTTPS, strong device authentication, password_hash/password_verify, authorization, rate limiting and audit logs.

The agent is intentionally not a credential-stealing or hidden Wi-Fi-password collection tool.
