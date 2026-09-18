<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds every Las Palmas Medical Center table with 3-5 records.
 *
 * Tables come from create_db.sql, not from migrations, so this uses the query
 * builder with explicit IDs. Each table gets its own ID range (Physician 101+,
 * Department 201+, ...) so rows are easy to trace across foreign keys.
 */
class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $this->physicians();
        $this->departments();
        $this->affiliations();
        $this->procedures();
        $this->patients();
        $this->nurses();
        $this->medications();
        $this->prescriptions();
        $this->rooms();
        $stays = $this->stays();
        $this->procedureRecords($stays);
        $this->onCallShifts();
        $this->appointments();
    }

    private function physicians(): void
    {
        $positions = [
            101 => 'Chief of Medicine',
            102 => 'Surgeon',
            103 => 'Psychiatrist',
            104 => 'Resident',
            105 => 'Intern',
        ];

        foreach ($positions as $id => $position) {
            DB::table('Physician')->insert([
                'physicianID' => $id,
                'name' => $this->personName(),
                'position' => $position,
                // 105 is an international intern who has no SSN yet
                'ssn' => $id === 105 ? null : $this->ssn(),
            ]);
        }
    }

    private function departments(): void
    {
        DB::table('Department')->insert([
            ['deptID' => 201, 'name' => 'General Medicine', 'headID' => 101],
            ['deptID' => 202, 'name' => 'Surgery', 'headID' => 102],
            ['deptID' => 203, 'name' => 'Psychiatry', 'headID' => 103],
        ]);
    }

    private function affiliations(): void
    {
        DB::table('AffiliatedWith')->insert([
            ['physicianID' => 101, 'departmentID' => 201],
            ['physicianID' => 102, 'departmentID' => 202],
            ['physicianID' => 103, 'departmentID' => 203],
            ['physicianID' => 104, 'departmentID' => 201],
            ['physicianID' => 105, 'departmentID' => 202],
        ]);
    }

    private function procedures(): void
    {
        DB::table('Procedure')->insert([
            ['procID' => 301, 'name' => 'Appendectomy', 'cost' => 2450.00],
            ['procID' => 302, 'name' => 'MRI Scan', 'cost' => 1200.00],
            ['procID' => 303, 'name' => 'Blood Transfusion', 'cost' => 850.50],
            // price not finalized yet
            ['procID' => 304, 'name' => 'Psychiatric Evaluation', 'cost' => null],
        ]);
    }

    private function patients(): void
    {
        $familyName = fake()->lastName();
        $familyInsurance = $this->insuranceNumber();

        $patients = [
            // 601 and 602 are family members sharing one insurance policy
            601 => ['name' => fake()->firstName().' '.$familyName, 'insurance' => $familyInsurance, 'primary' => 101],
            602 => ['name' => fake()->firstName().' '.$familyName, 'insurance' => $familyInsurance, 'primary' => 101],
            603 => ['name' => $this->personName(), 'insurance' => $this->insuranceNumber(), 'primary' => 104],
            604 => ['name' => $this->personName(), 'insurance' => $this->insuranceNumber(), 'primary' => 103],
            // 605 is an uninsured foreign patient with no SSN and no primary physician
            605 => ['name' => $this->personName(), 'insurance' => null, 'primary' => null],
        ];

        foreach ($patients as $id => $patient) {
            DB::table('Patient')->insert([
                'patientID' => $id,
                'ssn' => $id === 605 ? null : $this->ssn(),
                'name' => $patient['name'],
                'address' => fake()->streetAddress().', Las Cruces, NM '.fake()->numerify('880##'),
                'dob' => fake()->dateTimeBetween('-85 years', '-1 year')->format('Y-m-d'),
                'phone' => fake()->numerify('575-###-####'),
                'insuranceNumber' => $patient['insurance'],
                'primaryPhysID' => $patient['primary'],
            ]);
        }
    }

    private function nurses(): void
    {
        $positions = [401 => 'Head Nurse', 402 => 'Nurse', 403 => 'Nurse', 404 => 'Nurse'];

        foreach ($positions as $id => $position) {
            DB::table('Nurse')->insert([
                'nurseID' => $id,
                'name' => $this->personName(),
                'position' => $position,
                // 404 is an international nurse who has no SSN yet
                'ssn' => $id === 404 ? null : $this->ssn(),
            ]);
        }
    }

    private function medications(): void
    {
        DB::table('Medication')->insert([
            ['medID' => 501, 'name' => 'Amoxicillin'],
            ['medID' => 502, 'name' => 'Ibuprofen'],
            ['medID' => 503, 'name' => 'Sertraline'],
            ['medID' => 504, 'name' => 'Morphine'],
            ['medID' => 505, 'name' => 'Omeprazole'],
        ]);
    }

    private function prescriptions(): void
    {
        $prescriptions = [
            [101, 601, 502, '400 mg every 8 hours'],
            [102, 601, 501, '500 mg 3/day'],
            [102, 603, 504, '2 mg IV as needed'],
            [103, 604, 503, '50 mg/day'],
            [104, 602, 505, '20 mg/day'],
        ];

        foreach ($prescriptions as [$physician, $patient, $medication, $dose]) {
            DB::table('Prescribes')->insert([
                'physicianID' => $physician,
                'patientID' => $patient,
                'medicationID' => $medication,
                'prescribedDate' => fake()->dateTimeBetween('-1 year', '-1 week')->format('Y-m-d'),
                'dose' => $dose,
            ]);
        }
    }

    private function rooms(): void
    {
        DB::table('Room')->insert([
            ['roomID' => 701, 'roomType' => 'Single'],
            ['roomID' => 702, 'roomType' => 'Double'],
            ['roomID' => 703, 'roomType' => 'Double'],
            ['roomID' => 704, 'roomType' => 'Single'],
        ]);
    }

    /**
     * @return array<int, array{patient: int, start: Carbon, end: ?Carbon}>
     */
    private function stays(): array
    {
        // stayID => [patientID, roomID, length in days (null = still admitted)]
        $plan = [
            801 => [601, 701, 7],
            802 => [603, 702, 3],
            803 => [604, 703, 7],
            804 => [602, 704, null],
        ];

        $stays = [];

        foreach ($plan as $id => [$patient, $room, $days]) {
            $start = $days === null
                ? Carbon::instance(fake()->dateTimeBetween('-6 days', '-2 days'))->startOfDay()
                : Carbon::instance(fake()->dateTimeBetween('-6 months', '-1 month'))->startOfDay();
            $end = $days === null ? null : $start->copy()->addDays($days);

            DB::table('Stay')->insert([
                'stayID' => $id,
                'patientID' => $patient,
                'roomID' => $room,
                'startDate' => $start->format('Y-m-d'),
                'endDate' => $end?->format('Y-m-d'),
            ]);

            $stays[$id] = ['patient' => $patient, 'start' => $start, 'end' => $end];
        }

        return $stays;
    }

    /**
     * Undergoes rows use the stay's own patient, and a date inside that stay.
     *
     * @param  array<int, array{patient: int, start: Carbon, end: ?Carbon}>  $stays
     */
    private function procedureRecords(array $stays): void
    {
        // stayID => [procedureID, physicianID, nurseID]
        $plan = [
            801 => [301, 102, 401],
            802 => [302, 101, 402],
            803 => [304, 103, 403],
            804 => [303, 104, 402],
        ];

        foreach ($plan as $stayId => [$procedure, $physician, $nurse]) {
            $stay = $stays[$stayId];
            $lastDay = $stay['end'] ?? Carbon::today();

            DB::table('Undergoes')->insert([
                'patientID' => $stay['patient'],
                'procedureID' => $procedure,
                'stayID' => $stayId,
                'procDate' => Carbon::instance(fake()->dateTimeBetween($stay['start'], $lastDay))->format('Y-m-d'),
                'physicianID' => $physician,
                'nurseID' => $nurse,
            ]);
        }
    }

    private function onCallShifts(): void
    {
        foreach ([401, 402, 403, 404] as $nurse) {
            $start = Carbon::instance(fake()->dateTimeBetween('-3 months', '+1 month'))->startOfDay();

            DB::table('OnCall')->insert([
                'nurseID' => $nurse,
                'startDate' => $start->format('Y-m-d'),
                // endDate must be after startDate
                'endDate' => $start->copy()->addDays(fake()->numberBetween(1, 14))->format('Y-m-d'),
            ]);
        }
    }

    private function appointments(): void
    {
        // appID => [patientID, nurseID, physicianID, days from today]
        $plan = [
            901 => [601, 402, 101, -40],
            902 => [603, 403, 104, -25],
            903 => [604, 401, 103, -10],
            904 => [605, 404, 105, -3],
            905 => [602, 402, 101, 14],
        ];

        foreach ($plan as $id => [$patient, $nurse, $physician, $offset]) {
            $start = Carbon::today()->addDays($offset)->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

            DB::table('Appointment')->insert([
                'appID' => $id,
                'patientID' => $patient,
                'nurseID' => $nurse,
                'physicianID' => $physician,
                'startDateTime' => $start->format('Y-m-d H:i:s'),
                // a future appointment has no confirmed end time yet
                'endDateTime' => $offset > 0 ? null : $start->copy()->addMinutes(fake()->randomElement([30, 60]))->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function personName(): string
    {
        return fake()->firstName().' '.fake()->lastName();
    }

    private function ssn(): int
    {
        return fake()->unique()->numberBetween(100000000, 899999999);
    }

    private function insuranceNumber(): int
    {
        return fake()->unique()->numberBetween(10000000, 99999999);
    }
}
