# CitiRx

## Adaptive Learning and Predictive Board-Readiness Platform for PhLE Reviewees

CitiRx is a role-based web application for Pharmacy Licensure Examination (PhLE) preparation and academic research. It combines competency-based assessment, adaptive practice, Bayesian Knowledge Tracing (BKT), readiness analytics, response telemetry, faculty interventions, controlled research-form versioning, and gamified progression.

> **Research status:** Software readiness does not make question content or study instruments valid. Before official data collection, qualified pharmacy faculty must approve the questions, blueprint assignments, rationales, cognitive levels, source permissions, scoring rules, and research protocol.

## Current Capabilities

### Student

- Account registration, acceptable-email validation, email verification, login, password reset, and profile management
- Sixty-item diagnostic/pre-test with draft-answer saving and resume support
- Subject-weighted results based on the configured PhLE Table of Specifications
- Bayesian Knowledge Tracing at the competency level
- Estimated board-readiness index and domain-level mastery summaries
- Deterministic personalized study recommendations based on mastery, readiness, practice history, and published Micro Pearls
- Adaptive practice with immediate feedback and research-form question exclusion
- Timed Mock Board/post-test with autosave, server-enforced expiration, submission reasons, and resume support
- Rx Vault for mistakes, bookmarks, notes, and focused drills
- Published and reviewed Micro Pearls library
- Cohort-scoped informational leaderboard with privacy-conscious names
- XP, levels, badges, and progression tracking

### Faculty

- Cohort-level performance dashboard
- Domain and competency weakness summaries
- Student intervention creation, completion, and history
- Controlled Micro Pearl authoring and review workflow
- Access restricted to the assigned cohort

### Administrator

- User search, filtering, role-safe updates, soft deletion, restoration, and cohort assignment
- Cohort creation, editing, archiving, post-test windows, and participation summaries
- CSV cohort-assignment preview and commit workflow
- Question-bank filtering, editing, activation controls, locks, and immutable edit history
- Research-form readiness audits and structural validation
- Immutable Form A and Form B version management
- Review, validation, publication, activation, and served-content protection
- Official pilot-phase controls with optimistic locking and emergency-transition auditing
- Research-participant inclusion, exclusion, withdrawal, and development-data scope management
- Research analytics with eligibility enforcement, descriptive statistics, MAE/RMSE, and Wilcoxon signed-rank analysis
- Micro Pearl review, publication scheduling, retirement, source checks, and AI-assistance metadata
- Privacy-conscious administrative audit history and CSV export
- Read-only system-health monitoring for the database, cache, queue configuration, failed jobs, scheduler, mail, storage, release version, and backup status

## Important Current Limitation

Research content and faculty-validation CSV files are currently imported through guarded Artisan commands. The administrator website can manage form versions and publication states, but it does not yet provide a secure browser upload workflow for research-question CSV files.

Do not add a web endpoint that accepts shell commands or arbitrary file paths. A future browser importer must use private temporary storage, dry-run validation, file hashing, current-password confirmation, one-time commit tokens, immutable audit records, and the same validation services used by the terminal commands.

## PhLE Table of Specifications

The configured TOS contains six domains and 20 competencies. Domain weights total 100%.

| No. | Domain | Weight | Competencies |
|---:|---|---:|---:|
| 1 | Pharmaceutical Chemistry | 20% | 3 |
| 2 | Pharmacognosy/Biochemistry | 15% | 2 |
| 3 | Practice of Pharmacy | 17.5% | 4 |
| 4 | Pharmacology-Pharmacokinetics | 15% | 3 |
| 5 | Pharmaceutics | 17.5% | 4 |
| 6 | Quality Control/Quality Assurance | 15% | 4 |
|  | **Total** | **100%** | **20** |

Component-level percentages are stored separately within each domain. The seeders and research-form audit enforce the configured structure.

## Research Architecture

CitiRx separates ordinary learning content from official research instruments.

- Standard questions are eligible for adaptive practice.
- Form A (`pre_test_a`) is the diagnostic/pre-test research instrument.
- Form B (`post_test_b`) is the Mock Board/post-test research instrument.
- Research questions are bound to immutable `research_form_versions` records.
- Published or previously served versions cannot be edited in place.
- A new version must be copied to draft before its content changes.
- Assessment sessions store the exact form version and served question identifiers.
- Official analytics include only attempts that satisfy participant-scope, version, item-count, response-integrity, domain-allocation, chronology, and completion rules.
- Unanswered post-test records count as incorrect using the complete denominator.
- Missing response records are treated as data-integrity failures.

Development seeders may create placeholder research content in `local` and `testing` environments. Always run the formal audit before a pilot:

```powershell
php artisan research:audit-forms --require-validation
```

Never enable this setting during staging, production, or official collection:

```ini
CITIRX_ALLOW_UNVALIDATED_RESEARCH_FORMS=true
```

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.3+ | Server-side language |
| Laravel 13 | Application framework |
| MySQL 8 | Relational database |
| Blade | Server-rendered interfaces |
| Tailwind CSS | Styling |
| Alpine.js | Client-side interaction |
| Vite 8 | Frontend build tooling |
| Pest 5 / PHPUnit | Automated testing |
| Playwright | Browser/system testing |
| Laravel Pint | PHP formatting |

## Requirements

- PHP 8.3 or later
- Composer 2
- MySQL 8 or a compatible MySQL server
- Node.js compatible with Vite 8; Node.js 22 is recommended
- npm
- Git

PHP must include Laravel’s required extensions. The browser-testing plugin additionally requires the sockets extension.

## Local Installation

Clone the repository:

```powershell
git clone https://github.com/S8kaiv/CitiRx.git
Set-Location CitiRx
```

Install dependencies:

```powershell
composer install
npm ci
```

Create the environment file and generate a unique application key:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Create separate MySQL databases:

```text
citirx
citirx_testing
```

Configure the development database in `.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=citirx
DB_USERNAME=root
DB_PASSWORD=
```

Use the credentials for your MySQL installation. Never commit `.env`.

Create the schema and load local development data:

```powershell
php artisan migrate:fresh --seed
```

> `migrate:fresh` deletes all tables and records in the selected database. Confirm that `.env` points to a disposable local database before running it. Never use this command against pilot or production data.

Build frontend assets:

```powershell
npm run build
```

## Running Locally

Start Laravel:

```powershell
php artisan serve
```

For frontend development, run this in a second terminal:

```powershell
npm run dev
```

Run the scheduler in another terminal so expiration handling and the System Health heartbeat work:

```powershell
php artisan schedule:work
```

When using the database queue locally, run:

```powershell
php artisan queue:work --tries=3
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Development Accounts

The development seeder creates local-only accounts such as:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@citirx.test` | `password` |
| Faculty | `faculty@citirx.test` | `password` |
| Student | `juan@citirx.test` | `password` |

These credentials are for `local` and `testing` environments only. Never create production accounts with these passwords.

## Research-Form Commands

The terminal import workflow is restricted to registered, mutable form versions.

Audit a version:

```powershell
php artisan research:audit-forms `
    --form=pre_test_a `
    --form-version=pre_test_a_v2 `
    --require-validation
```

Dry-run a content import:

```powershell
php artisan research:import-form-content `
    "D:\secure\pre_test_a_v2.csv" `
    --form=pre_test_a `
    --form-version=pre_test_a_v2 `
    --dry-run
```

Export content for faculty review:

```powershell
php artisan research:export-forms `
    --form=pre_test_a `
    --form-version=pre_test_a_v2 `
    --out="D:\secure\pre_test_a_v2_review.csv"
```

Dry-run faculty validation:

```powershell
php artisan research:import-validation `
    "D:\secure\pre_test_a_v2_approved.csv" `
    --form=pre_test_a `
    --form-version=pre_test_a_v2 `
    --validator-email="faculty@example.edu" `
    --dry-run
```

Remove `--dry-run` only after the preview passes and the source file has been formally approved. Store copyrighted questions and review workbooks outside Git.

## Pilot Controls

Official research collection is controlled through administrator-managed phases. Phase transitions enforce form readiness, participant scope, cohort state, post-test windows, current-password confirmation, optimistic locks, and audit history.

Once official collection begins:

- Active research configuration is locked.
- Published or served forms remain immutable.
- Cohort post-test windows cannot be casually changed.
- Emergency transitions use a separate audited path.
- Closing an assessment blocks new sessions while allowing eligible existing sessions to resume or be finalized safely.

Do not bypass these controls through direct database changes.

## System Health and Backups

Administrators can view the read-only System Health page at:

```text
/admin/system-health
```

The scheduler records its heartbeat automatically:

```powershell
php artisan system:heartbeat
```

The following command does **not** create a backup:

```powershell
php artisan system:record-backup
```

It only records the verified outcome of a server-managed backup job. A successful record requires a non-zero file size and SHA-256 checksum:

```powershell
php artisan system:record-backup success `
    --size-bytes=123456 `
    --checksum=64_CHARACTER_SHA256_VALUE
```

The correct production workflow is:

```text
Create database dump
→ verify that the file exists and is non-empty
→ calculate SHA-256
→ store the backup outside the web root
→ record the result in CitiRx
→ regularly test restoration
```

Never run the success command simply to make the health page green.

## Testing and Quality Checks

Create and configure the `citirx_testing` database before running tests. The test configuration intentionally uses MySQL.

Run unit and feature tests:

```powershell
php artisan test
```

Run formatting checks:

```powershell
php vendor/bin/pint --test
```

Apply PHP formatting:

```powershell
php vendor/bin/pint
```

Build production assets:

```powershell
npm run build
```

Install Chromium and run browser/system tests when required:

```powershell
npx playwright install chromium
php vendor/bin/pest tests/Browser
```

Run dependency checks:

```powershell
composer audit
npm audit
```

Before every pull request:

```powershell
php vendor/bin/pint --test
php artisan test
npm run build
composer audit
git diff --check
git status
```

Use the output from the exact release commit as the authoritative test and assertion count. Do not maintain a hardcoded count in this README.

## Production Requirements

CitiRx must not be deployed using `php artisan serve`.

A production deployment requires:

- A supported PHP runtime and production web server
- HTTPS
- A unique production `APP_KEY`
- `APP_ENV=production`
- `APP_DEBUG=false`
- A real database account with least-privilege permissions
- A persistent queue worker
- A continuously running Laravel scheduler
- A real SMTP provider for verification and password-reset messages
- Private, writable Laravel storage
- A server-managed database backup job and tested restore procedure
- Production credentials stored outside Git
- Published and faculty-validated research-form versions
- Approved pilot controls and participant scope
- `CITIRX_ALLOW_UNVALIDATED_RESEARCH_FORMS=false`

Set deployment metadata so System Health can identify the release:

```ini
APP_VERSION=1.0.0
APP_COMMIT=DEPLOYED_GIT_COMMIT

SYSTEM_HEALTH_SCHEDULER_STALE_MINUTES=5
SYSTEM_HEALTH_BACKUP_STALE_HOURS=25
```

Typical deployment commands include:

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Do not run development seeders in production.

## Security and Research-Data Rules

- Never commit `.env`, SQL dumps, production logs, backup files, review workbooks, or research exports.
- Never display database passwords, mail credentials, API keys, environment values, or queue payloads in administrator pages.
- Keep official question banks and copyrighted content outside public repositories.
- Do not modify served research questions or historical assessment responses.
- Do not shrink assessment denominators because response rows are missing.
- Do not include excluded, withdrawn, development, or unregistered participants in official analytics.
- Use current-password confirmation for high-impact administrator actions.
- Preserve immutable audit history for role changes, cohort assignments, form transitions, pilot changes, content publication, and account actions.
- Store exported research data only in institution-approved secure locations.
- Follow institutional ethics, consent, privacy, retention, and deletion requirements.

## Contribution Workflow

Do not develop directly on `main`.

```powershell
git switch main
git pull --ff-only origin main
git switch -c feature/short-description
```

Before committing:

```powershell
php vendor/bin/pint
php artisan test
npm run build
git diff --check
git status
```

Stage only intended files:

```powershell
git add path/to/intended-file
git diff --cached --check
git diff --cached --stat
```

Commit and push:

```powershell
git commit -m "feat: describe the completed change"
git push -u origin feature/short-description
```

Open a pull request into `main`, document the behavior and tests performed, and merge only after the automated quality workflow passes.

## Project Structure

```text
app/Console/Commands       Research and operational commands
app/Http/Controllers       Role-specific request handling
app/Http/Requests          Authorization and input validation
app/Models                 Eloquent models and relationships
app/Services               Domain, workflow, analytics, and audit logic
config                     Application and research configuration
database/migrations        Authoritative schema history
database/seeders           Local and testing development data
resources/views            Blade interfaces
routes                     Web routes and scheduled commands
tests/Unit                 Isolated unit tests
tests/Feature              Application and integration tests
tests/Browser              Browser/system tests
```

## Academic Use

CitiRx is an academic capstone system. Readiness values are estimated learning indicators, not guarantees of passing the Pharmacy Licensure Examination. Research conclusions must be based on approved instruments, eligible participant data, documented statistical methods, and actual pilot results.
