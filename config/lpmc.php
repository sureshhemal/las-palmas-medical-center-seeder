<?php

return [

    // The assignment's DDL script. lpmc:fresh runs it as-is.
    'schema_path' => env('LPMC_SCHEMA_PATH', database_path('schema/create_db.sql')),

    // Where lpmc:export writes the INSERT statements.
    'export_path' => env('LPMC_EXPORT_PATH', storage_path('app/insert_db.sql')),

    // Fixed Faker seed, so every run produces the same names and numbers.
    'faker_seed' => 2026,

    // Tables in foreign-key order (parents first), with their primary key columns.
    'tables' => [
        'Physician' => ['physicianID'],
        'Department' => ['deptID'],
        'AffiliatedWith' => ['physicianID', 'departmentID'],
        'Procedure' => ['procID'],
        'Patient' => ['patientID'],
        'Nurse' => ['nurseID'],
        'Medication' => ['medID'],
        'Prescribes' => ['physicianID', 'patientID', 'medicationID', 'prescribedDate'],
        'Room' => ['roomID'],
        'Stay' => ['stayID'],
        'Undergoes' => ['patientID', 'procedureID', 'stayID', 'procDate'],
        'OnCall' => ['nurseID', 'startDate', 'endDate'],
        'Appointment' => ['appID'],
    ],

];
