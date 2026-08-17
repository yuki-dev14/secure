<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplianceVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $category,
        public string $periodLabel,
        public string $recipientName,
        public int    $beneficiaryCount,
        public string $filePath,
    ) {}

    public function envelope(): Envelope
    {
        $categoryDisplay = $this->category === 'education' ? 'Education' : 'Health & Nutrition';

        return new Envelope(
            subject: "SECURE 4Ps — {$categoryDisplay} Compliance Verification List — {$this->periodLabel}",
        );
    }

    public function content(): Content
    {
        $categoryDisplay = $this->category === 'education' ? 'Education' : 'Health & Nutrition';
        $verifierRole    = $this->category === 'education' ? 'School Representative' : 'Midwife';

        return new Content(
            view: 'emails.compliance-verification',
            with: [
                'subject'        => "{$categoryDisplay} Compliance Verification — {$this->periodLabel}",
                'greeting'       => "Hello, {$this->recipientName}!",
                'introLine'      => "Attached is the {$categoryDisplay} compliance verification list for the period {$this->periodLabel}. "
                    . "Please review the beneficiary list and mark those who did NOT comply with the required conditions.",
                'alertType'      => $this->category === 'education' ? 'info' : 'success',
                'detailsTitle'   => '📋 Verification Details',
                'details'        => [
                    'Category'            => $categoryDisplay,
                    'Period'              => $this->periodLabel,
                    'Beneficiaries Listed'=> $this->beneficiaryCount,
                    'Your Role'           => $verifierRole,
                    'Default Status'      => 'COMPLIANT (change to NON_COMPLIANT if not met)',
                ],
                'categoryDisplay' => $categoryDisplay,
                'verifierRole'    => $verifierRole,
                'beneficiaryCount'=> $this->beneficiaryCount,
                'periodLabel'     => $this->periodLabel,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $categoryDisplay = $this->category === 'education' ? 'Education' : 'Health';

        return [
            Attachment::fromPath($this->filePath)
                ->as("SECURE-4Ps-{$categoryDisplay}-Verification-{$this->periodLabel}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
