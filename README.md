# CitiRx

## Adaptive Learning and Predictive Board-Readiness Platform for PhLE Reviewees

CitiRx is a web-based review platform for Pharmacy Licensure Examination (PhLE) reviewees. It combines competency-based assessment, adaptive practice, Bayesian Knowledge Tracing (BKT), readiness analytics, response telemetry, and gamified progression to help students identify weak areas and focus their review.

> **Project status:** CitiRx is under active development as an academic capstone project. Seeded questions and research forms are development data. Pharmacy content, cognitive classifications, scoring rules, and research instruments must be reviewed by qualified faculty or subject-matter experts before formal deployment.

## Implemented Features

- Role-aware authentication for student, faculty, and administrator accounts
- A 60-item diagnostic assessment with 10 items from each PhLE subject
- Draft-answer autosave and diagnostic resume support
- Subject-weighted diagnostic results based on the configured PhLE Table of Specifications (TOS)
- Bayesian Knowledge Tracing at the competency level
- Predicted board-readiness percentages and readiness bands
- Adaptive Practice Mode with immediate answer feedback
- Response telemetry, including response time, item position, and mastery changes
- Rx Vault for mistakes, bookmarks, personal notes, and focused drills
- XP, levels, badges, and progress tracking
- Student profile management and account deletion
- Development-only parallel research forms for pre-test and post-test workflows
- Automated feature and unit tests using Pest

Mock Board Exam, PvP, and expanded faculty/administrator analytics are planned features and should not be treated as complete until their routes, interfaces, tests, and research rules are implemented.

## PhLE Table of Specifications

The current TOS configuration contains six subjects and 20 components. Subject weights total 100%.

| No. | Subject | Weight | Components |
|---:|---|---:|---:|
| 1 | Pharmaceutical Chemistry | 20% | 3 |
| 2 | Pharmacognosy/Biochemistry | 15% | 2 |
| 3 | Practice of Pharmacy | 17.5% | 4 |
| 4 | Pharmacology-Pharmacokinetics | 15% | 3 |
| 5 | Pharmaceutics | 17.5% | 4 |
| 6 | Quality Control/Quality Assurance | 15% | 4 |
|  | **Total** | **100%** | **20** |

Component-level percentages are stored separately within each subject and are validated by `TosSeeder` to total 100% per subject.

## Assessment Data

The seeders separate ordinary development questions from mock research forms:

| Dataset | Purpose | Items |
|---|---|---:|
| Standard question bank | Practice and general development testing | 24 |
| Form A (`pre_test_a`) | Development mock pre-test/diagnostic form | 60 |
| Form B (`post_test_b`) | Development mock post-test form | 60 |
| **Local/testing total** | All development datasets | **144** |

Each research form contains 10 questions per subject. The two forms use the same subject totals while varying component placement where the scaled blueprint permits it.

`ResearchQuestionSeeder` runs only in `local` and `testing` environments and refuses to run in `production`. Its generated items are explicitly labelled as unvalidated mock content and must be replaced with faculty-authored, Content Validity Index (CVI)-validated questions before research administration.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.3+ | Server-side language |
| Laravel 13 | Application framework |
| MySQL 8 | Relational database |
| Blade | Server-rendered views |
| Tailwind CSS | Interface styling |
| Alpine.js | Lightweight client-side interactions |
| Vite | Front-end development and builds |
| Laravel Breeze | Authentication foundation |
| Pest 5 | Automated testing |
| Laravel Pint | PHP code formatting |

## Requirements

Install the following before setting up CitiRx:

- PHP 8.3 or later
- Composer
- MySQL 8 or a compatible MySQL server
- Node.js and npm
- Git

PHP must have the extensions required by Laravel and the project dependencies. Enabling the PHP ZIP extension is recommended so Composer can install distribution packages efficiently.

## Local Installation

Clone the repository and enter the project directory:

```powershell
git clone https://github.com/S8kaiv/CitiRx.git
Set-Location CitiRx
```

Install dependencies:

```powershell
composer install
npm install
```

Create the local environment file and application key:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database named `citirx`, then configure the matching values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=citirx
DB_USERNAME=root
DB_PASSWORD=
```

Use the actual username and password for your local MySQL installation. Never commit real passwords, API keys, or production credentials.

Build the database and load development data:

```powershell
php artisan migrate:fresh --seed
```

> `migrate:fresh` deletes every table and record in the selected database. Verify that `.env` points to a disposable local database before running it.

## Run the Application

Start Laravel:

```powershell
php artisan serve
```

In a second terminal, start Vite:

```powershell
npm run dev
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

For a production-style front-end build, run:

```powershell
npm run build
```

## Database Seeders

`DatabaseSeeder` loads records in dependency order:

1. Cohorts
2. Tiers and levels
3. Badges
4. TOS subjects and components
5. Standard questions
6. Survey items
7. Development users
8. Mock research questions, only in `local` or `testing`

Run all eligible seeders:

```powershell
php artisan db:seed
```

Run an individual seeder when needed:

```powershell
php artisan db:seed --class=TosSeeder
php artisan db:seed --class=QuestionSeeder
php artisan db:seed --class=ResearchQuestionSeeder
```

The seeders are designed to be repeatable. Do not manually insert competing domain/component order values because database uniqueness rules protect the official ordering.

## Expected Development Records

After `php artisan migrate:fresh --seed` in the local environment, the main expected counts are:

| Data | Expected Count |
|---|---:|
| Cohorts | 1 |
| Tiers | 4 |
| Levels | 10 |
| Badges | 6 |
| TOS subjects | 6 |
| TOS components | 20 |
| Standard questions | 24 |
| Form A questions | 60 |
| Form B questions | 60 |
| Total local/testing questions | 144 |
| Choices, four per question | 576 |
| ISO survey items | 12 |
| Development users | 7 |

Verify the TOS and question pools with Tinker:

```powershell
php artisan tinker
```

```php
App\Models\TosDomain::count();
App\Models\TosCompetency::count();
(float) App\Models\TosDomain::sum('prc_weight_percentage');
App\Models\Question::whereNull('research_form')->count();
App\Models\Question::where('research_form', App\Models\Question::FORM_PRE_TEST_A)->count();
App\Models\Question::where('research_form', App\Models\Question::FORM_POST_TEST_B)->count();
```

The expected results are `6`, `20`, `100.0`, `24`, `60`, and `60`.

## Local Development Accounts

`UserSeeder` creates local accounts for development and testing.

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@citirx.test` | `password` |
| Faculty | `faculty@citirx.test` | `password` |
| Student | `juan@citirx.test` | `password` |

Additional student accounts may also be seeded. These credentials are strictly for local development and must never be used in production.

## Bayesian Knowledge Tracing and Readiness

CitiRx stores a mastery probability for each student-competency pair. Answer processing updates the probability using BKT and records the prior and posterior values in response telemetry.

Readiness calculations aggregate competency mastery into subject scores and apply the configured subject weights. The user-facing readiness band is calculated in application services rather than duplicated in Blade templates.

Research-form questions are excluded from ordinary adaptive practice pools so pre-test/post-test items do not leak into routine practice.

## Quality Checks

Before committing or opening a pull request, run:

```powershell
php vendor/bin/pint --test
php artisan test
npm run build
git diff --check
```

To apply PHP formatting automatically:

```powershell
php vendor/bin/pint
```

The current verified backend suite contains 34 passing tests with 99 assertions. This number can increase as new features and regression tests are added.

## Contribution Workflow

Do not develop directly on `main`.

```powershell
git switch main
git pull origin main
git switch -c feature/short-description
```

After implementing and validating the change:

```powershell
git status
git diff --check
php artisan test
git add <intended-files>
git commit -m "feat: describe the change"
git push -u origin feature/short-description
```

Open a pull request into `main`, describe the behavior and testing performed, and resolve conflicts on the feature branch before merging. Prefer a squash merge when the branch contains many small correction commits that represent one logical feature.

## Research and Content Safeguards

- Seeded pharmacy questions are not automatically valid research instruments.
- Mock Form A and Form B questions must be replaced or reviewed before participant use.
- Faculty or qualified subject-matter experts should validate question correctness, TOS alignment, cognitive level, distractors, and rationales.
- Survey instruments should undergo the approval, content validation, reliability testing, consent, and ethics procedures required by the institution.
- A pre-survey, knowledge pre-test, system evaluation, and knowledge post-test measure different constructs and should not be treated as interchangeable.
- Do not store participant-identifying research exports in Git.
- Back up the database before destructive migrations or large reseeding operations.

## Project Structure

```text
app/Http/Controllers   Request handling and authorization flow
app/Models             Eloquent models and relationships
app/Services           Assessment, practice, readiness, badges, and XP logic
database/migrations    Authoritative database schema history
database/seeders       Repeatable development and research mock data
resources/views        Blade user interfaces
routes                  Web and authentication routes
tests                   Pest unit and feature tests
```

## License

No standalone license file is currently included for the CitiRx application. The project is intended for academic capstone development. Third-party packages remain subject to their respective licenses; Laravel is distributed under the MIT License.

