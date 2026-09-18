# Las Palmas Medical Center — Seeder

A small Laravel app that builds the Las Palmas Medical Center MySQL database
from `create_db.sql`, seeds every table with 3–5 records, and exports those
records as `insert_db.sql`.

It was written for Phase 1 of a DBMS course project: `create_db.sql` is the
hand-written schema, and this app generates the matching `insert_db.sql`.
Because the rows go through MySQL, every primary key, foreign key and `CHECK`
constraint in the schema is exercised on the way in.

## Requirements

- PHP 8.3+ and Composer
- MySQL 8.0.16+ (older versions parse `CHECK` constraints but ignore them)

## Setup

```bash
git clone https://github.com/sureshhemal/las-palmas-medical-center-seeder.git
cd las-palmas-medical-center-seeder
composer install
cp .env.example .env
php artisan key:generate
```

Then set `DB_HOST`, `DB_PORT`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

The schema itself is not part of this repository. Put your own `create_db.sql`
in `database/schema/` (that folder is git-ignored), or point
`LPMC_SCHEMA_PATH` in `.env` at it.

## Usage

```bash
php artisan lpmc:fresh     # drop + recreate the database from create_db.sql, then seed it
php artisan lpmc:export    # write every table's rows as insert statements
```

`lpmc:fresh` asks before dropping the database; pass `--force` to skip the prompt.
`lpmc:export` takes `--path=` to choose the output file.

| Setting            | Default                           |
|--------------------|-----------------------------------|
| `LPMC_SCHEMA_PATH` | `database/schema/create_db.sql`   |
| `LPMC_EXPORT_PATH` | `storage/app/insert_db.sql`       |

The exported file starts with `use las_palmas_medical_center;` and inserts
parent tables before child tables, so it runs top to bottom right after
`create_db.sql`.

## Schema

| Table            | Primary key                                           |
|------------------|-------------------------------------------------------|
| `Physician`      | `physicianID`                                         |
| `Department`     | `deptID`                                              |
| `AffiliatedWith` | `physicianID, departmentID`                           |
| `Procedure`      | `procID`                                              |
| `Patient`        | `patientID`                                           |
| `Nurse`          | `nurseID`                                             |
| `Medication`     | `medID`                                               |
| `Prescribes`     | `physicianID, patientID, medicationID, prescribedDate`|
| `Room`           | `roomID`                                              |
| `Stay`           | `stayID`                                              |
| `Undergoes`      | `patientID, procedureID, stayID, procDate`            |
| `OnCall`         | `nurseID, startDate, endDate`                         |
| `Appointment`    | `appID`                                               |

The seeder expects the columns and constraints of the project's
`create_db.sql`: nullable SSNs and insurance numbers, `endDate > startDate`
checks, and the physician, department, nurse and room value constraints.

## Seed data

All records are created in
[`database/seeders/HospitalSeeder.php`](database/seeders/HospitalSeeder.php).

Each table has its own ID range, so a foreign key value tells you which table
it points to:

| Table       | IDs     | Table       | IDs     |
|-------------|---------|-------------|---------|
| Physician   | 101–105 | Medication  | 501–505 |
| Department  | 201–203 | Patient     | 601–605 |
| Procedure   | 301–304 | Room        | 701–704 |
| Nurse       | 401–404 | Stay        | 801–804 |
| Appointment | 901–905 |             |         |

Positions, departments, drugs, procedures and doses are hand-picked so the
records make sense together. Faker fills in names, SSNs, addresses, phone
numbers and dates.

The data deliberately covers the cases the schema allows:

- a physician and a nurse without an SSN (international staff)
- two family members sharing one insurance number
- an uninsured patient with no SSN and no primary physician
- a procedure whose cost isn't set yet
- a stay that is still open (no end date), and a future appointment with no end time
- every `Undergoes` row uses its stay's own patient and a date inside that stay

Faker uses a fixed seed, so names and numbers are the same on every run.
Dates are relative to the day you run it.
