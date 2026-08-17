@extends('emails.layout', [
    'subject'      => $subject,
    'greeting'     => $greeting,
    'introLine'    => $introLine,
    'alertType'    => $alertType,
    'detailsTitle' => $detailsTitle,
    'details'      => $details,
    'actionUrl'    => null,
    'noteLines'    => [
        '📎 The Excel file is attached to this email.',
        '✅ All beneficiaries are listed as COMPLIANT by default.',
        '❌ Change the "compliance_status" column to NON_COMPLIANT for beneficiaries who did NOT meet the required conditions.',
        '📝 Fill in the "reason" column for each non-compliant entry (e.g., "Attendance below 85%", "Missed immunization").',
        '⚠ Do NOT modify the beneficiary_unique_id, beneficiary_name, or barangay columns.',
        '📨 Once completed, return the file to your DSWD contact for import into the system.',
    ],
])

@section('extra_content')
@endsection
