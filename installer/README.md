# Windows client installer

`PerfectLPG-Setup-<version>.exe` installs Perfect LPG on a client Windows 10/11 (x64) PC that has internet access.

## Build (developer PC)

```powershell
winget install JRSoftware.InnoSetup   # once
.\installer\build.ps1                 # -> installer\output\PerfectLPG-Setup-<version>.exe
```

Composer needs a PHP 8.2+ (with openssl and zip) on PATH. The installer contains only the application (tracked and new non-ignored files, with production Composer dependencies). Build from a clean, committed branch.

## What setup does on the client PC

1. It asks for the install folder (default `C:\PerfectLPG`, no spaces), the web port (default 80) and the company name. It can optionally open the port in the firewall for other POS PCs on the LAN.
2. It downloads the latest patch of each tested line, found by `scripts/resolve.ps1`:
   - PHP 8.5 TS x64;
   - Apache 2.4 (Apache Lounge);
   - MySQL 8.4 LTS;
   - the VC++ runtime;
   - the curl CA bundle.

   PHP, Apache and the CA bundle are checked against the vendors' SHA-256. MySQL is trusted on HTTPS only, because Oracle publishes no machine-readable checksum. The VC++ runtime must carry a valid Microsoft signature.
3. `scripts/configure.ps1` then sets everything up:
   - installs the components;
   - creates the services `PerfectLPG-MySQL` (port 3307 or the next free port, localhost only) and `PerfectLPG-Apache`, both set to start automatically and restart on failure;
   - generates random MySQL root and app passwords in `config\` (readable by Administrators only);
   - writes `app\.env` (production, `app.baseURL = http://<computer-name>:<port>/`);
   - schedules a daily 23:00 database backup to `backups\`, kept for 30 days.
4. It opens `http://<computer-name>:<port>/installation`, where the app creates the database. The default login is `admin` / `admin123`. Change that password right after installing.

## Upgrades and uninstall

- **Upgrade:** run a newer `setup.exe` over the existing installation. It downloads the current components again and replaces the app code. It keeps `data\`, `backups\`, `config\`, `logs\`, `app\.env` and `app\writable\`, and switches `FRESH_DATABASE_SETUP` off once the database exists. Pending migrations are offered on the login page.
- **Uninstall:** removes the services, the backup task, the firewall rule, the shortcuts and the program files. The database and backups stay in `data\` and `backups\`, so reinstalling reconnects to the same data.
- **Logs:** `logs\install-*.log`, `logs\apache-error.log`, `logs\mysql-error.log`, `logs\backup.log`.

## Moving to a new PHP or MySQL line

Change `$PhpLine` / `$MySqlLine` in `scripts/resolve.ps1` only after the app has been tested on the new line.
