<?php

return [
    'meta_title' => 'Learning Center — Noobstron',
    'meta_description' => 'Learn how to organize customers, sales, service, automation and commercial management with practical Noobstron guides.',

    'index' => [
        'eyebrow' => 'Noobstron Learning Center',
        'title' => 'Learn how to transform your commercial operation.',
        'description' => 'Practical guides to organize customers, improve sales, manage your team, automate tasks and get more value from Noobstron.',

        'start_title' => 'Start here',
        'start_description' => 'A simple journey from initial setup to your first organized sale in Noobstron.',

        'cards' => [
            'getting_started' => [
                'number' => '01',
                'title' => 'Getting started with Noobstron',
                'description' => 'Configure your company, organize your team and prepare your workspace to get started.',
                'action' => 'Start guide →',
            ],
            'customers' => [
                'number' => '02',
                'title' => 'Organize your customers',
                'description' => 'Centralize contacts, history, owners and commercial information.',
                'action' => 'Coming soon',
            ],
            'sales' => [
                'number' => '03',
                'title' => 'Structure your sales process',
                'description' => 'Learn how to work with leads, pipelines, opportunities and activities.',
                'action' => 'Coming soon',
            ],
            'follow_up' => [
                'number' => '04',
                'title' => 'Improve your follow-up',
                'description' => 'Organize tasks and next steps so opportunities do not get lost.',
                'action' => 'Coming soon',
            ],
            'communication' => [
                'number' => '05',
                'title' => 'Centralize communication',
                'description' => 'Connect email, WhatsApp and conversations to your customer context.',
                'action' => 'Coming soon',
            ],
            'automation' => [
                'number' => '06',
                'title' => 'Automate and scale',
                'description' => 'Use automation and artificial intelligence to reduce repetitive work.',
                'action' => 'Coming soon',
            ],
        ],

        'path_title' => 'Grow step by step',
        'path_description' => 'Start by organizing the foundation and evolve as your operation matures.',

        'path' => [
            ['title' => '1. Organize', 'description' => 'Customers and data'],
            ['title' => '2. Sell', 'description' => 'Leads and pipeline'],
            ['title' => '3. Follow up', 'description' => 'Activities'],
            ['title' => '4. Communicate', 'description' => 'Email and WhatsApp'],
            ['title' => '5. Automate', 'description' => 'Flows and tasks'],
            ['title' => '6. Scale', 'description' => 'AI and indicators'],
        ],

        'cta' => [
            'title' => 'Ready to put it into practice?',
            'description' => 'Create your workspace and use our guides while organizing your commercial operation in Noobstron.',
            'button' => 'Start my trial',
        ],
    ],

    'getting_started' => [
        'meta_title' => 'Getting started with Noobstron',
        'meta_description' => 'Learn step by step how to configure Noobstron, organize your team, register customers and work with leads, opportunities, proposals and sales.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 01 • Getting started',
        'title' => 'From registration to your first organized sale in Noobstron.',
        'lead' => 'This guide shows how to prepare your company, organize your team, register customers, work with leads and opportunities, and build a commercial process that can grow with your operation.',

        'progress' => [
            'Company',
            'Team',
            'Customers',
            'Pipeline',
            'Leads',
            'Opportunities',
            'Activities',
            'Proposals',
            'Sale',
        ],

        'nav_title' => 'In this guide',

        'nav' => [
            'overview' => 'Before you start',
            'company' => '1. Configure your company',
            'team' => '2. Build your team',
            'customers' => '3. Organize customers',
            'pipeline' => '4. Create your pipeline',
            'leads' => '5. Organize your leads',
            'opportunities' => '6. Create opportunities',
            'activities' => '7. Plan activities',
            'proposals' => '8. Create proposals',
            'sale' => '9. Record the sale',
            'evolve' => 'How to evolve',
        ],

        'overview' => [
            'title' => 'Before you start',
            'paragraphs' => [
                'Noobstron was designed to connect information that is usually scattered across spreadsheets, calendars, email inboxes, WhatsApp and different systems.',
                'You do not need to configure every feature on the first day. The best approach is to build a simple and reliable foundation and evolve as your team starts using the system.',
            ],
            'flow' => [
                'Organize company and team',
                'Centralize customers and leads',
                'Structure the commercial process',
                'Track opportunities and activities',
                'Create proposals and record sales',
            ],
            'box_title' => 'A good rule for getting started',
            'box_text' => 'Do not try to reproduce all of your company complexity immediately. Start with the commercial process your team actually uses today.',
        ],

        'company' => [
            'title' => 'Configure your company',
            'paragraphs' => [
                'The first step is to make sure the workspace correctly represents your organization. This information helps Noobstron prepare the environment for your operation.',
            ],
            'subtitle' => 'Review the main information',
            'items' => [
                'Company name.',
                'Country of operation.',
                'Primary language.',
                'Business segment.',
            ],
            'after_list' => 'The segment helps provide context for the type of operation, while country and language influence the experience presented to users.',
            'example_title' => 'Example',
            'example_text' => 'A service company can start by selecting Services as its segment and later create a specific pipeline for quotation, negotiation and closing.',
        ],

        'team' => [
            'title' => 'Build your team',
            'paragraphs' => [
                'After configuring the company, add the people who will actually participate in the process.',
                'Separate user accounts make it possible to identify owners, control permissions and maintain a history of actions performed.',
            ],
            'subtitle' => 'Start with the people who need access',
            'items' => [
                'Administrators.',
                'Sales managers.',
                'Sales representatives.',
                'Customer service.',
                'Other required team members.',
            ],
            'box_title' => 'Avoid sharing user accounts',
            'box_text' => 'Each person should use their own account. This improves security, accountability and auditing.',
        ],

        'customers' => [
            'title' => 'Organize your customers',
            'paragraphs' => [
                'Customer records are the foundation of relationships inside Noobstron. This is where important information stops being scattered across different places.',
            ],
            'subtitle' => 'Centralize the context',
            'items' => [
                'Main customer information.',
                'Contacts.',
                'Phone numbers.',
                'Email addresses.',
                'Addresses.',
                'Relationship history.',
            ],
            'after_list' => 'The better organized the customer record is, the easier it becomes to understand who the customer is and what has already happened in the commercial relationship.',
            'example_title' => 'Example',
            'example_text' => 'If a sales representative goes on vacation, another team member can review the customer history and continue the relationship with much more context.',
            'import_title' => 'What if I already have many customers?',
            'import_text' => 'Instead of entering them one by one, use the import process to bring existing data into Noobstron in an organized way.',
        ],

        'pipeline' => [
            'title' => 'Create your sales pipeline',
            'paragraphs' => [
                'The pipeline represents the stages an opportunity goes through until closing.',
                'There is no universal pipeline. It should represent your company’s real sales process.',
            ],
            'flow' => [
                'First contact',
                'Qualification',
                'Proposal',
                'Negotiation',
                'Closing',
            ],
            'box_title' => 'Keep it simple',
            'box_text' => 'A pipeline with four or five clearly defined stages is usually more useful than fifteen stages nobody understands.',
        ],

        'leads' => [
            'title' => 'Organize your leads',
            'paragraphs' => [
                'Leads represent potential customers who are still being identified or qualified.',
                'Recording the source and status of these contacts helps your team understand where opportunities come from and which ones deserve attention.',
            ],
            'subtitle' => 'Some possible lead sources',
            'items' => [
                'Website.',
                'Referral.',
                'Campaign.',
                'WhatsApp.',
                'Outbound prospecting.',
                'Event.',
            ],
            'after_list' => 'When a lead becomes a real commercial opportunity, it can advance to the next stage of the process.',
        ],

        'opportunities' => [
            'title' => 'Turn interest into an opportunity',
            'paragraphs' => [
                'An opportunity represents a concrete negotiation currently in progress.',
                'At this point, you begin tracking information such as expected value, current stage, owner and negotiation progress.',
            ],
            'example_title' => 'Example',
            'example_text' => 'A lead requests a demonstration and confirms interest in purchasing. The lead is no longer just a contact and now represents a commercial opportunity.',
            'subtitle' => 'Keep your pipeline updated',
            'after_example' => 'When the negotiation changes, move the opportunity to the corresponding stage. This keeps your commercial view close to reality.',
        ],

        'activities' => [
            'title' => 'Never lose track of the next step',
            'paragraphs' => [
                'An opportunity without a defined next action tends to be forgotten.',
                'Use activities to record what needs to happen next.',
            ],
            'items' => [
                'Phone call.',
                'Meeting.',
                'Send proposal.',
                'Contact the customer again.',
                'Follow up.',
                'Confirm documentation.',
            ],
            'box_title' => 'A simple practice',
            'box_text' => 'Whenever you finish an important interaction, ask: “what is the next step and when should it happen?”',
        ],

        'proposals' => [
            'title' => 'Turn the negotiation into a proposal',
            'paragraphs' => [
                'When the opportunity is mature enough, formalize the offer through a proposal.',
                'The catalog helps keep products and services organized so they can be used in commercial proposals.',
            ],
            'subtitle' => 'A clear proposal should help the customer understand',
            'items' => [
                'What is being offered.',
                'Quantity.',
                'Prices.',
                'Commercial conditions.',
                'Negotiation context.',
            ],
            'after_list' => 'The goal is not only to generate a document, but to keep the proposal connected to the negotiation that produced the sale.',
        ],

        'sale' => [
            'title' => 'Record your first sale',
            'paragraphs' => [
                'When the negotiation is completed, record the sale so the commercial result remains connected to the rest of the process.',
                'From there, financial information and receivables can continue the cycle.',
            ],
            'flow' => [
                'Lead',
                'Opportunity',
                'Proposal',
                'Sale',
                'Payment and relationship',
            ],
        ],

        'evolve' => [
            'title' => 'Now you can evolve',
            'paragraphs' => [
                'Once your team, customers and commercial process are organized, the next step is to improve efficiency.',
            ],
            'checklist' => [
                'Centralize email and WhatsApp.',
                'Standardize communication templates.',
                'Automate repetitive tasks.',
                'Use notifications and reminders.',
                'Track financial indicators.',
                'Use AI to support your team’s activities.',
            ],
            'cta_title' => 'Start with an organized foundation.',
            'cta_text' => 'You do not need to master every feature today. The important thing is to get your main process working and evolve from there.',
            'trial_button' => 'Start my trial',
            'guides_button' => 'View other guides',
        ],
    ],
];