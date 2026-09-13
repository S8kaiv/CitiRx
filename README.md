# CitiRx

## Adaptive Learning and Predictive Board Readiness Platform for PhLE Reviewees

**CitiRx** is a web-based adaptive review platform designed to support Pharmacy Licensure Examination (PhLE) reviewees through personalized learning, knowledge tracing, predictive readiness analytics, and gamified review mechanics.

The platform uses **Bayesian Knowledge Tracing (BKT)** to estimate mastery per competency and uses assessment performance, response telemetry, and learning progress to support adaptive review and board-readiness monitoring.

> **Project Status:** Under active development as a capstone project. The current seeded questions, competency content, survey items, and PRC-related weights are development data and should be academically validated before final research deployment.

---

## Core Features

- Adaptive competency-based review using Bayesian Knowledge Tracing
- Diagnostic, practice, mock-board, and PvP assessment sessions
- Predicted board-readiness monitoring
- PhLE Table of Specifications domain and competency structure
- Four-choice multiple-choice question bank
- Response-time and speed-guessing telemetry
- Hypercorrection rationales and distractor feedback
- Rx Vault for questions requiring additional review
- Question bookmarking and personal notes
- Faculty interventions for targeted student support
- Bot-based PvP review mode
- XP, levels, tiers, streaks, and badges
- ISO/IEC 25010 evaluation support
- Research analytics including MAE, RMSE, and Wilcoxon statistics
- Cohort-level readiness and performance analytics

---

## Technology Stack

| Technology | Purpose |
|---|---|
| Laravel | Backend web framework |
| PHP | Server-side programming |
| MySQL | Relational database |
| Eloquent ORM | Database models and relationships |
| Laravel Breeze | Authentication |
| Blade | Server-rendered user interface |
| HTML / CSS / JavaScript | Front-end interface |
| Composer | PHP dependency management |
| Artisan | Laravel command-line tooling |

---

## System Requirements

For the current development environment, the project uses PHP 8.x and MySQL 8.x.

You should have PHP, Composer, MySQL, Node.js/NPM, and Git installed before setting up the project.

---

## Installation

Clone the repository:

```bash
git clone <repository-url>
cd CitiRx
```

Install PHP dependencies:

```bash
composer install
```

Install front-end dependencies:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

On Windows PowerShell, you may use:

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

---

## Database Configuration

Create a MySQL database named:

```text
citirx
```

Update the database section of `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=citirx
DB_USERNAME=root
DB_PASSWORD=
```

Change the username and password if your local MySQL configuration is different.

---

## Database Setup

Run all migrations:

```bash
php artisan migrate
```

The project uses Laravel migrations as the **source of truth for the database schema**.

The CitiRx database contains the application domain tables together with Laravel's framework infrastructure tables for features such as sessions, cache, jobs, migrations, and password resets.

---

## Database Seeders

CitiRx includes development seeders for the initial application data.

The main `DatabaseSeeder` runs the seeders in dependency order:

```php
$this->call([
    CohortSeeder::class,
    TierAndLevelSeeder::class,
    BadgeSeeder::class,
    TosSeeder::class,
    QuestionSeeder::class,
    SurveyItemSeeder::class,
    UserSeeder::class,
]);
```

Seed the complete development database with:

```bash
php artisan db:seed
```

Individual seeders can also be executed:

```bash
php artisan db:seed --class=CohortSeeder
php artisan db:seed --class=TierAndLevelSeeder
php artisan db:seed --class=BadgeSeeder
php artisan db:seed --class=TosSeeder
php artisan db:seed --class=QuestionSeeder
php artisan db:seed --class=SurveyItemSeeder
php artisan db:seed --class=UserSeeder
```

Most seeders use `updateOrCreate()` so they can safely process existing development records without intentionally producing duplicates.

---

## Seeded Development Data

After running all current seeders, the expected base development data is:

| Data | Expected Count |
|---|---:|
| Cohorts | 1 |
| Tiers | 4 |
| Levels | 10 |
| Badges | 6 |
| TOS Domains | 6 |
| TOS Competencies | 12 |
| Questions | 24 |
| Question Choices | 96 |
| ISO Survey Items | 12 |
| Development Users | 7 |

The 24 seeded questions currently contain four answer choices each.

---

## Development Accounts

`UserSeeder` creates local development accounts for testing authentication and role-based features.

### Administrator

```text
Email: admin@citirx.test
Password: password
Role: admin
```

### Faculty

```text
Email: faculty@citirx.test
Password: password
Role: faculty
```

### Student Example

```text
Email: juan@citirx.test
Password: password
Role: student
```

Additional student test accounts are also generated by `UserSeeder`.

> These credentials are intended only for local development and testing. They must not be used as production credentials.

---

## Run the Application

Start the Laravel development server:

```bash
php artisan serve
```

In another terminal, start the front-end development server:

```bash
npm run dev
```

Laravel will normally be available at:

```text
http://127.0.0.1:8000
```

---

## Rebuild the Development Database

To completely delete the current database tables, rerun all migrations, and execute all seeders:

```bash
php artisan migrate:fresh --seed
```

> **Warning:** `migrate:fresh` deletes all records in the current database. Use it only when you intentionally want to rebuild the development database from scratch.

---

## Verify the Seeded Database

Open Laravel Tinker:

```bash
php artisan tinker
```

Then check the main records:

```php
App\Models\User::count();
App\Models\Cohort::count();
App\Models\Tier::count();
App\Models\Level::count();
App\Models\Badge::count();
App\Models\TosDomain::count();
App\Models\TosCompetency::count();
App\Models\Question::count();
App\Models\QuestionChoice::count();
App\Models\SurveyItem::count();
```

The expected results using the current development seeders are:

```text
Users:              7
Cohorts:            1
Tiers:              4
Levels:             10
Badges:             6
TOS Domains:        6
TOS Competencies:   12
Questions:          24
Question Choices:   96
Survey Items:       12
```

---

## Bayesian Knowledge Tracing

CitiRx maintains a mastery estimate for each user and competency using Bayesian Knowledge Tracing.

The current mastery probability is stored as:

```text
current_mastery_p_l
```

The competency-level learning transition probability is stored as:

```text
bkt_transition_p_t
```

The initial development value for the transition probability is:

```text
0.1000
```

Other BKT parameters and the full mastery update process are handled by application logic rather than being duplicated across database records.

---

## Assessment System

CitiRx supports these assessment session types:

```text
diagnostic
practice
mock_board
pvp
```

Assessment sessions may also be identified by research phase:

```text
none
pre_test
post_test
```

Pre-test and post-test performance should be derived from assessment session records rather than permanently storing duplicated pre-test and post-test scores on the user record.

---

## Question Bank

Each question belongs to a PhLE competency and contains exactly four choices identified as:

```text
A
B
C
D
```

Questions can contain a hypercorrection rationale, diagnostic-pool status, difficulty information, activity status, and speed-flag information.

Response telemetry records can store the selected answer, correctness, response time, item position, speed flag, prior mastery probability, and posterior mastery probability.

The current seeded question bank is intended for development and system testing. Pharmacy-related content should be reviewed and approved by an appropriate subject-matter expert before being used as final research or review material.

---

## Gamification

CitiRx includes XP transactions, levels, tiers, badges, streaks, and bot-based PvP sessions.

XP changes are recorded through the `xp_transactions` ledger using a source type and optional source identifier. The user's `total_xp` value acts as a cached value for fast application access, while the transaction history preserves individual XP changes.

---

## ISO/IEC 25010 Evaluation

The project stores ISO-based evaluation items and individual Likert-scale responses for research analysis.

Survey results are associated with an evaluation record and can be used to calculate evaluation statistics while retaining the individual item-level responses required for research analysis.

The current survey items are development content and should be reviewed against the final evaluation instrument used by the researchers.

---

## Research Analytics

CitiRx supports cohort-level research snapshots containing values such as:

```text
cohort_mean_predicted_readiness_pct
projected_pass_rate_pct
speed_floor_compliance_pct
bkt_mae
bkt_rmse
wilcoxon_w_statistic
wilcoxon_p_value
```

These records are intended to support analysis of predictive performance, mastery estimation, readiness, and pre-test/post-test research outcomes.

---

## Important Development Notes

Laravel migrations are the authoritative definition of the CitiRx database structure. Seeders contain reproducible development data, while models define Eloquent relationships, casts, and application-level behavior.

Do not treat the current question bank, PRC domain weights, competency descriptions, or ISO survey wording as automatically validated research material. Final research content should be reviewed against the approved study instrument, official references, and subject-matter expert recommendations.

---

## Project

**CitiRx: An Adaptive Learning and Predictive Board Readiness Platform with Knowledge Tracing and Gamified Mechanics for PhLE Reviewees**

This project is being developed as an academic capstone system focused on adaptive learning, Pharmacy Licensure Examination review, knowledge tracing, gamification, and predictive board-readiness analytics.

---

## License

This repository is intended primarily for academic and capstone development.

Laravel itself is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).