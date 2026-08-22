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
                'action' => 'Open guide →',
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

    'customers' => [
        'meta_title' => 'How to organize your customers in a CRM — Noobstron',
        'meta_description' => 'Learn how to organize customers, contacts, history, owners, imports and data quality in a CRM.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 02 • CRM and customers',
        'title' => 'How to organize your customers in a CRM.',
        'lead' => 'A well-structured customer database turns scattered information into commercial context. In this guide, you will learn how to organize customers, contacts, history and responsibilities so your team can work more effectively.',

        'nav_title' => 'In this guide',

        'nav' => [
            'por-que-organizar' => '1. Why organize customers',
            'cadastro' => '2. Structure customer records',
            'contatos' => '3. Organize contacts',
            'enderecos' => '4. Addresses and context',
            'historico' => '5. Relationship history',
            'responsaveis' => '6. Owners and continuity',
            'importacao' => '7. Customer import',
            'qualidade' => '8. Data quality',
            'erros' => '9. Common mistakes',
            'noobstron' => '10. How to apply it in Noobstron',
        ],

        'sections' => [
            [
                'id' => 'por-que-organizar',
                'title' => 'Why organize customers',
                'paragraphs' => [
                    'When customer information is scattered across spreadsheets, calendars, emails and conversations, the team loses context and starts depending on each person’s memory.',
                    'An organized CRM creates a central source of information so everyone can understand who the customer is, who is responsible, what has already happened and what should happen next.',
                ],
                'box_title' => 'The goal is not just to store records',
                'box_text' => 'A good CRM should help your team make decisions, continue conversations and identify opportunities with better context.',
            ],
            [
                'id' => 'cadastro',
                'title' => 'Structure the main customer record',
                'paragraphs' => [
                    'The main customer record should contain essential and reliable information about each customer.',
                    'Avoid turning it into a huge form filled with fields nobody uses. Start with information that actually supports your operation.',
                ],
                'subtitle' => 'Useful information to start with',
                'items' => [
                    'Name or legal business name.',
                    'Document or identifier when necessary.',
                    'Customer segment or type.',
                    'Internal owner.',
                    'Relationship status.',
                    'Relevant notes.',
                ],
                'after_list' => 'The structure can evolve as your company discovers which information truly helps sales, service and management.',
            ],
            [
                'id' => 'contatos',
                'title' => 'Organize contacts, phone numbers and emails',
                'paragraphs' => [
                    'A company can have several contacts. That is why it is important to separate organization data from the people your team actually communicates with.',
                    'Keeping phone numbers and emails connected to the customer helps reduce duplicates and makes it easier to find the correct information.',
                ],
                'subtitle' => 'For each contact, record when relevant',
                'items' => [
                    'Name.',
                    'Job title or role.',
                    'Phone number.',
                    'Email.',
                    'Preferred contact channel.',
                ],
                'example_title' => 'Example',
                'example_text' => 'A customer may have one financial contact, one purchasing contact and another technical contact. Keeping these roles separate helps your team speak to the right person.',
            ],
            [
                'id' => 'enderecos',
                'title' => 'Use addresses as commercial context',
                'paragraphs' => [
                    'Addresses can be important for billing, delivery, field service or defining commercial regions.',
                    'Ideally, addresses should remain connected to the customer without mixing them with information that belongs to individual contacts.',
                ],
                'subtitle' => 'Common uses',
                'items' => [
                    'Business address.',
                    'Billing address.',
                    'Delivery address.',
                    'Branches or locations.',
                ],
            ],
            [
                'id' => 'historico',
                'title' => 'Build a relationship history',
                'paragraphs' => [
                    'Relationship history is one of the most valuable parts of a CRM. It helps your team understand what happened before a new conversation begins.',
                    'Interactions, activities, proposals and negotiations should remain connected to the customer whenever possible.',
                ],
                'example_title' => 'Example',
                'example_text' => 'Before calling a customer, a sales representative can review the latest proposal, the pending activity and previous conversations.',
                'box_title' => 'Context reduces repeated work',
                'box_text' => 'When history is centralized, the customer does not need to repeat the entire story every time they speak with a different member of your team.',
            ],
            [
                'id' => 'responsaveis',
                'title' => 'Define owners and preserve continuity',
                'paragraphs' => [
                    'Customers should have clear owners when the operation depends on individual follow-up.',
                    'This helps distribute customer portfolios and prevents situations where everyone assumes someone else is taking care of the relationship.',
                ],
                'subtitle' => 'Clear responsibility helps answer',
                'items' => [
                    'Who is responsible for this customer?',
                    'Who should make the next contact?',
                    'Who can take over if the main owner is unavailable?',
                ],
                'after_list' => 'Even with a main owner, the relationship history should remain available to authorized team members.',
            ],
            [
                'id' => 'importacao',
                'title' => 'Import existing customers carefully',
                'paragraphs' => [
                    'If your company already has a customer database in spreadsheets or another system, importing it can save a significant amount of time.',
                    'However, importing data without reviewing it can also bring duplicates, outdated information and inconsistent fields.',
                ],
                'subtitle' => 'Before importing',
                'items' => [
                    'Remove obvious duplicate records.',
                    'Standardize important fields.',
                    'Review emails and phone numbers.',
                    'Decide which columns actually need to be imported.',
                    'Test with a small sample first.',
                ],
                'box_title' => 'Import less, but better',
                'box_text' => 'A smaller, organized and reliable database often creates more value than thousands of low-quality records.',
            ],
            [
                'id' => 'qualidade',
                'title' => 'Take care of data quality',
                'paragraphs' => [
                    'A CRM quickly loses value when the team stops trusting its information.',
                    'Data quality depends less on filling out many fields and more on keeping the important fields accurate and updated.',
                ],
                'subtitle' => 'Good practices',
                'items' => [
                    'Avoid creating the same customer more than once.',
                    'Update contacts when information changes.',
                    'Remove clearly invalid information.',
                    'Use consistent standards for important fields.',
                    'Record useful information instead of simply collecting more data.',
                ],
            ],
            [
                'id' => 'erros',
                'title' => 'Avoid common CRM organization mistakes',
                'paragraphs' => [
                    'Many CRM problems are not caused by the tool itself, but by the way data is managed in everyday work.',
                ],
                'subtitle' => 'Mistakes worth avoiding',
                'items' => [
                    'Entering information without any standard.',
                    'Sharing one account among several users.',
                    'Creating too many unnecessary required fields.',
                    'Never updating old records.',
                    'Keeping important information only in private conversations.',
                    'Creating a new customer record for every new negotiation.',
                ],
                'after_list' => 'The best process is one your team can maintain consistently.',
            ],
            [
                'id' => 'noobstron',
                'title' => 'How to apply this in Noobstron',
                'paragraphs' => [
                    'In Noobstron, customer records can bring together the organization’s main information and keep related data within the same context.',
                    'Contacts, phone numbers, emails, addresses and history can be organized in a structured way to support both sales and customer relationships.',
                ],
                'subtitle' => 'A simple sequence to get started',
                'items' => [
                    'Create or import your customers.',
                    'Review contacts and communication channels.',
                    'Assign owners when necessary.',
                    'Connect leads and opportunities to your sales process.',
                    'Record activities and next steps.',
                    'Keep relationship history updated.',
                ],
                'example_title' => 'Expected result',
                'example_text' => 'When any authorized user opens a customer record, they should quickly understand who the customer is, how to contact them and what is happening in the relationship.',
            ],
        ],

        'checklist_title' => 'Checklist for an organized customer database',
        'checklist_description' => 'Before moving on to automation and more complex processes, check whether your customer database is ready.',

        'checklist' => [
            'No obvious duplicate customers.',
            'Main contacts registered.',
            'Phone numbers and emails updated.',
            'Owners defined when necessary.',
            'Commercial history accessible.',
            'Existing database reviewed before import.',
            'Team following a consistent registration standard.',
        ],

        'cta' => [
            'title' => 'Turn your data into commercial context.',
            'description' => 'Start by organizing your customer database and build the rest of your commercial process on reliable information.',
            'trial' => 'Start my trial',
            'previous' => 'View getting started guide',
        ],
    ],];