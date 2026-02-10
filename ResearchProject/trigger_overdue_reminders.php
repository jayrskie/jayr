<?php
// Trigger script: runs send_overdue_reminders.php once per day when included.
// It uses a small text file to record last run date (YYYY-MM-DD).

$lockFile = __DIR__ . DIRECTORY_SEPARATOR . 'last_overdue_reminder.txt';
$today = date('Y-m-d');

// If lock file exists and equals today, skip
if (file_exists($lockFile)) {
    $last = trim(file_get_contents($lockFile));
    if ($last === $today) {
        // Already sent today
        return;
    }
}

// Run the reminders script (it will send reminders for overdue items)
// We include it so it runs within the same PHP process.
// Ensure send_overdue_reminders.php does not exit the process on success.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'send_overdue_reminders.php';

// If no fatal error happened, write today's date to lock file
file_put_contents($lockFile, $today);

?>