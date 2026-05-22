<?php
return [
    'provider' => 'africas_talking',
    'at_username' => 'sandbox',
    'at_api_key' => 'atsk_e876918eb79d95d15579ae43ec018bec3493e687610772a4ad9159a689a8ceaaeeab3571',
    'at_sender_id' => 'GxAlert',
    'at_sandbox' => true,
    'advance_minutes' => 5,
    'retry_max' => 3,
    'retry_delay_min' => 5,
    'quiet_start' => '21:00',
    'quiet_end' => '06:00',
    'templates' => [
        'reminder' => 'GxAlert Reminder: It\'s time to take your {drug_name} ({dose_mg}mg). Please take your dose at {dose_time}. Reply HELP for support.',
        'missed' => 'GxAlert: Your {drug_name} dose for {dose_date} was not recorded as taken. Please contact your facility.',
    ],
];
