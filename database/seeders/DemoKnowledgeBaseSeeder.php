<?php

namespace Database\Seeders;

use App\Models\KbDocument;
use App\Services\KbIndexer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoKnowledgeBaseSeeder extends Seeder
{
    /**
     * Seed demo knowledge base content.
     */
    public function run(): void
    {
        DB::table('kb_chunks')->delete();
        DB::table('kb_documents')->delete();

        $documents = [
            [
                'title' => 'Support Hours and SLA',
                'source_type' => 'faq',
                'source_ref' => 'internal/support-hours',
                'raw_text' => <<<TEXT
Support Hours
Q: What are the standard support hours?
A: Standard support is available Monday through Friday, 9:00 AM to 6:00 PM (local time).

Q: Do you offer weekend coverage?
A: Weekend support is available for Premium plans only, from 10:00 AM to 4:00 PM.

Q: What is the response time SLA?
A: We target first response within 4 business hours for Standard plans and within 1 hour for Premium plans.

Escalations
Q: How do I escalate a critical issue?
A: Email support with "SEV-1" in the subject and include the impact summary and timeline.
TEXT,
            ],
            [
                'title' => 'Billing and Invoices',
                'source_type' => 'faq',
                'source_ref' => 'internal/billing',
                'raw_text' => <<<TEXT
Billing Basics
Q: When are invoices issued?
A: Invoices are generated on the first business day of each month.

Q: What payment methods are supported?
A: We accept credit card and ACH transfer for annual plans.

Refunds
Q: Are refunds available?
A: Refunds are offered within 30 days of the initial purchase for annual plans.

Tax Information
Q: Can you add a tax ID to the invoice?
A: Yes. Provide your tax ID before the invoice is issued.
TEXT,
            ],
            [
                'title' => 'Data Retention and Security',
                'source_type' => 'faq',
                'source_ref' => 'internal/security',
                'raw_text' => <<<TEXT
Data Retention
Q: How long do you retain uploaded content?
A: Raw documents are retained for the life of the account unless deleted by an admin.

Q: How long are logs stored?
A: System logs are retained for 30 days for Standard plans and 90 days for Premium plans.

Security
Q: Is customer data encrypted?
A: Yes. Data is encrypted in transit and at rest using industry-standard protocols.

Q: Do you support SSO?
A: SSO is available for Enterprise plans with SAML 2.0.
TEXT,
            ],
            [
                'title' => 'Knowledge Base Formatting Guide',
                'source_type' => 'guide',
                'source_ref' => 'internal/kb-formatting',
                'raw_text' => <<<TEXT
Formatting Guidance
Q: What is the preferred format for Q/A entries?
A: Use a short question line starting with "Q:" and a direct answer line starting with "A:".

Q: Can I include headings?
A: Yes. Use short headings to group related questions.

Q: How long should answers be?
A: Keep answers under 4 sentences when possible. Link to deeper docs if needed.

Examples
Q: How should I note exceptions?
A: Add a short "Exceptions:" line after the main answer.
TEXT,
            ],
        ];

        $indexer = app(KbIndexer::class);

        foreach ($documents as $document) {
            $kbDocument = KbDocument::create([
                'title' => $document['title'],
                'source_type' => $document['source_type'],
                'source_ref' => $document['source_ref'],
                'meta' => [
                    'raw_text' => $document['raw_text'],
                ],
            ]);

            $indexer->index($kbDocument);
        }
    }
}
