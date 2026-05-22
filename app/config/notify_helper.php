<?php
// config/notify_helper.php

/**
 * Core notification inserter.
 * 
 * @param string      $role     Target role (e.g. 'doctor', 'lab_personnel')
 * @param int|null    $user_id  Specific user ID, or null for all users of that role
 * @param string      $type     'alert' | 'event' | 'log' | 'info'
 * @param string      $title    Short notification title
 * @param string      $message  Longer description
 * @param string      $icon_bg  Tailwind bg class for icon container
 * @param string      $icon     FontAwesome icon + color classes
 * @param string|null $link     Relative URL to navigate to on click
 */
function notify(
    $conn,
    string $role,
    ?int $user_id,
    string $type,
    string $title,
    string $message,
    string $icon_bg = 'bg-info/10 dark:bg-info/15',
    string $icon    = 'fa-solid fa-bell text-info',
    ?string $link   = null
): void {
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_role, user_id, type, title, message, icon_bg, icon, link, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) {
        error_log("notify() prepare failed: " . $conn->error);
        return;
    }
    $stmt->bind_param("sissssss", $role, $user_id, $type, $title, $message, $icon_bg, $icon, $link);
    if (!$stmt->execute()) {
        error_log("notify() execute failed: " . $stmt->error);
    }
    $stmt->close();
}


// ═══════════════════════════════════════════════
//  REGIMEN
// ═══════════════════════════════════════════════

function notify_regimen_review($conn, int $clinician_id, string $doctor_role_target, string $patient_name, int $regimen_id): void {
    notify($conn, $doctor_role_target, null, 'alert',
        'Pending Regimen Review',
        "$patient_name's regimen needs your approval.",
        'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning',
        "regimen_reviews.php?id=$regimen_id"
    );
}

function notify_regimen_approved($conn, int $clinician_id, string $patient_name): void {
    notify($conn, 'clinician', $clinician_id, 'event',
        'Regimen Approved',
        "Your regimen for $patient_name has been approved by the doctor.",
        'bg-success/10 dark:bg-success/15', 'fa-solid fa-check-circle text-success',
        'patients.php'
    );
}

function notify_regimen_rejected($conn, int $clinician_id, string $patient_name, string $reason): void {
    notify($conn, 'clinician', $clinician_id, 'alert',
        'Regimen Rejected',
        "Regimen for $patient_name was rejected: " . mb_substr($reason, 0, 100),
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-times-circle text-error',
        'patients.php'
    );
}


// ═══════════════════════════════════════════════
//  LAB RESULTS
// ═══════════════════════════════════════════════

function notify_lab_result($conn, int $patient_id, string $patient_name, string $test_type, bool $is_final): void {
    if ($is_final) {
        // Final — notify patient and doctor
        notify($conn, 'patient', null, 'event',
            'Lab Result Available',
            "Your $test_type results are ready.",
            'bg-success/10 dark:bg-success/15', 'fa-solid fa-flask text-success',
            'results.php'
        );
        notify($conn, 'doctor', null, 'event',
            'Final Lab Result Uploaded',
            "$patient_name — $test_type result is now final.",
            'bg-success/10 dark:bg-success/15', 'fa-solid fa-flask text-success',
            "lab/results.php?patient_id=$patient_id"
        );
        notify($conn, 'clinician', null, 'event',
            'Final Lab Result Uploaded',
            "$patient_name — $test_type result is now final.",
            'bg-success/10 dark:bg-success/15', 'fa-solid fa-flask text-success',
            "lab/results.php?patient_id=$patient_id"
        );
    } else {
        // Preliminary — notify doctor and patient
        notify($conn, 'patient', null, 'info',
            'Preliminary Lab Result',
            "Your $test_type results are ready (preliminary).",
            'bg-info/10 dark:bg-info/15', 'fa-solid fa-flask text-info',
            'results.php'
        );
        notify($conn, 'doctor', null, 'alert',
            'Preliminary Lab Result',
            "$patient_name — $test_type needs review.",
            'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-flask text-warning',
            'lab_review.php'
        );
    }
}

/**
 * Notify the specific lab user that one of their own results is still preliminary.
 * Call this after inserting a preliminary result in upload_result.php.
 */
function notify_preliminary_pending($conn, int $lab_user_id, string $patient_name, string $test_type): void {
    notify($conn, 'lab_personnel', $lab_user_id, 'alert',
        'Preliminary Result Needs Finalization',
        "$patient_name — $test_type is still marked preliminary.",
        'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-flask text-warning',
        'preliminary_results.php'
    );
}

/**
 * Notify doctors and clinicians of a drug resistance finding.
 * Call this after inserting a DST result with result = 'resistant'.
 */
function notify_dst_resistant($conn, string $patient_name, string $drug_name): void {
    notify($conn, 'doctor', null, 'alert',
        'Resistance Finding Detected',
        "$patient_name is resistant to $drug_name — urgent review required.",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-biohazard text-error',
        'drug_susceptibility.php'
    );
    notify($conn, 'clinician', null, 'alert',
        'Resistance Finding Detected',
        "$patient_name is resistant to $drug_name.",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-biohazard text-error',
        'drug_susceptibility.php'
    );
}


// ═══════════════════════════════════════════════
//  ADVERSE EVENTS
// ═══════════════════════════════════════════════

function notify_adverse_event($conn, string $patient_name, string $event_type, string $severity): void {
    $critical = in_array($severity, ['severe', 'life_threatening']);
    $icon     = $critical ? 'fa-solid fa-exclamation-triangle text-error' : 'fa-solid fa-exclamation-circle text-warning';
    $bg       = $critical ? 'bg-error/10 dark:bg-error/15' : 'bg-warning/10 dark:bg-warning/15';

    notify($conn, 'doctor', null, 'alert',
        "Adverse Event: $event_type",
        "$patient_name reported $event_type (Severity: $severity)",
        $bg, $icon, 'adverse_events.php'
    );
    notify($conn, 'nurse', null, 'alert',
        'Side Effect Reported',
        "$patient_name — $event_type",
        $bg, $icon, 'side_effects.php'
    );
}


// ═══════════════════════════════════════════════
//  ADHERENCE
// ═══════════════════════════════════════════════

function notify_missed_dose($conn, string $patient_name, string $drug_name, string $dose_time): void {
    notify($conn, 'clinician', null, 'alert',
        'Missed Dose',
        "$patient_name missed $drug_name scheduled for $dose_time",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-pills text-error',
        'patients.php'
    );
    notify($conn, 'nurse', null, 'alert',
        'Missed Dose Logged',
        "$patient_name — $drug_name at $dose_time marked as missed",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-pills text-error',
        'log_adherence.php'
    );
}

function notify_low_adherence($conn, string $patient_name, string $adherence_pct): void {
    notify($conn, 'clinician', null, 'alert',
        'Low Adherence Alert',
        "$patient_name's adherence dropped to $adherence_pct%",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-chart-line text-error',
        'patients.php'
    );
    notify($conn, 'doctor', null, 'alert',
        'Low Adherence Alert',
        "$patient_name — $adherence_pct% adherence (30-day)",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-chart-line text-error',
        'low_adherence.php'
    );
}


// ═══════════════════════════════════════════════
//  PATIENTS
// ═══════════════════════════════════════════════

function notify_patient_enrolled($conn, string $patient_name, string $patient_code): void {
    notify($conn, 'clinician', null, 'event',
        'New Patient Enrolled',
        "$patient_name ($patient_code) has been registered.",
        'bg-primary/10 dark:bg-accent-light/15', 'fa-solid fa-user-plus text-primary dark:text-accent-light',
        'patients.php'
    );
    notify($conn, 'data_officer', null, 'event',
        'New Enrollment',
        "$patient_name ($patient_code) enrolled in GxAlert program.",
        'bg-info/10 dark:bg-info/15', 'fa-solid fa-user-plus text-info',
        'cohort_report.php'
    );
}


// ═══════════════════════════════════════════════
//  ADMIN / SYSTEM
// ═══════════════════════════════════════════════

function notify_user_created($conn, string $user_name, string $role): void {
    notify($conn, 'admin', null, 'log',
        'New User Created',
        "$user_name ($role) account has been created.",
        'bg-primary/10 dark:bg-accent-light/15', 'fa-solid fa-user-shield text-primary dark:text-accent-light',
        'users.php'
    );
}

function notify_sms_failed($conn, int $count): void {
    notify($conn, 'admin', null, 'alert',
        'SMS Delivery Failures',
        "$count SMS message(s) failed to deliver today.",
        'bg-error/10 dark:bg-error/15', 'fa-solid fa-comment-slash text-error',
        'sms_logs.php'
    );
}