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
                'action' => 'Open guide →',
            ],
            'follow_up' => [
                'number' => '04',
                'title' => 'Improve your follow-up',
                'description' => 'Organize tasks and next steps so opportunities do not get lost.',
                'action' => 'Open guide →',
            ],
            'communication' => [
                'number' => '05',
                'title' => 'Centralize communication',
                'description' => 'Connect email, WhatsApp and conversations to your customer context.',
                'action' => 'Open guide →',
            ],
            'results' => [
                'number' => '06',
                'title' => 'Track results and improve',
                'description' => 'Use data, conversion, pipeline, and metrics to continuously improve your sales process.',
                'action' => 'Open guide →',
            ],
            'automation' => [
                'number' => '07',
                'title' => 'Automate and scale',
                'description' => 'Reduce repetitive tasks, create safe rules, and increase your operation\'s capacity.',
                'action' => 'Open guide →',
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
    ],
    'sales' => [
        'meta_title' => 'How to structure your sales process — Noobstron',
        'meta_description' => 'Learn how to organize leads, pipeline, opportunities, activities and follow-up to create a more predictable sales process.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 03 • Sales process',
        'title' => 'How to structure your sales process.',
        'lead' => 'An organized sales process helps your team understand where each opportunity stands, what should happen next and where the main bottlenecks are.',

        'nav_title' => 'In this guide',

        'nav' => [
            'fundamentos' => '1. Understand the sales process',
            'leads' => '2. Organize your leads',
            'pipeline' => '3. Create your pipeline',
            'oportunidades' => '4. Create opportunities',
            'movimentacao' => '5. Move through the funnel',
            'atividades' => '6. Plan activities',
            'follow-up' => '7. Improve follow-up',
            'previsao' => '8. Track values',
            'ganhos-perdas' => '9. Record wins and losses',
            'noobstron' => '10. How to apply it in Noobstron',
        ],

        'sections' => [
            [
                'id' => 'fundamentos',
                'title' => 'Understand the sales process',
                'paragraphs' => [
                    'Before configuring tools, it is worth understanding the stages of the commercial relationship.',
                    'Contact, lead, opportunity and sale represent different moments and help your team separate initial interest from active negotiations.',
                ],
                'box_title' => 'A simple process is easier to maintain',
                'box_text' => 'The goal is not to create bureaucracy. It is to make clear what is happening and what action should come next.',
            ],
            [
                'id' => 'leads',
                'title' => 'Organize your leads',
                'paragraphs' => [
                    'Leads are contacts that still need to be identified or qualified before becoming a real negotiation.',
                    'Recording source, owner and context helps you understand which channels generate better opportunities.',
                ],
                'subtitle' => 'Useful information',
                'items' => [
                    'Lead source.',
                    'Owner.',
                    'Interest shown.',
                    'Qualification status.',
                    'Next action.',
                ],
            ],
            [
                'id' => 'pipeline',
                'title' => 'Create a pipeline that reflects your reality',
                'paragraphs' => [
                    'The pipeline represents the stages an opportunity goes through until closing.',
                    'The stages should reflect your company’s real process instead of a generic model copied from another business.',
                ],
                'subtitle' => 'Simple example',
                'items' => [
                    'First contact.',
                    'Qualification.',
                    'Proposal.',
                    'Negotiation.',
                    'Closing.',
                ],
                'after_list' => 'If a stage does not change the team’s decision or next action, it may not need to exist.',
            ],
            [
                'id' => 'oportunidades',
                'title' => 'Turn interest into an opportunity',
                'paragraphs' => [
                    'An opportunity should represent a concrete negotiation with real sales potential.',
                    'This keeps the pipeline from filling up with contacts that have not shown enough intent yet.',
                ],
                'example_title' => 'Example',
                'example_text' => 'A contact who only downloaded a resource may remain a lead. If they request a demo and discuss needs, they can become an opportunity.',
            ],
            [
                'id' => 'movimentacao',
                'title' => 'Move opportunities through the funnel',
                'paragraphs' => [
                    'The pipeline only provides a useful view when it stays updated.',
                    'Whenever the negotiation advances, moves back or closes, the opportunity should reflect that change.',
                ],
                'box_title' => 'A stagnant pipeline loses value',
                'box_text' => 'If opportunities remain in the same stage for months with no action, the funnel stops representing commercial reality.',
            ],
            [
                'id' => 'atividades',
                'title' => 'Plan activities and next steps',
                'paragraphs' => [
                    'Every active opportunity should have a clear next action.',
                    'Activities help turn intention into execution and make the team less dependent on memory and personal calendars.',
                ],
                'subtitle' => 'Examples',
                'items' => [
                    'Phone call.',
                    'Meeting.',
                    'Demo.',
                    'Send proposal.',
                    'Follow up with the customer.',
                    'Confirm documentation.',
                ],
            ],
            [
                'id' => 'follow-up',
                'title' => 'Improve follow-up discipline',
                'paragraphs' => [
                    'Many opportunities are lost not because of lack of interest, but because nobody made the next contact at the right time.',
                    'A consistent follow-up routine helps keep negotiations moving without relying on improvisation.',
                ],
                'box_title' => 'Always define the next step',
                'box_text' => 'At the end of an important interaction, record what should happen next, who is responsible and when the action should occur.',
            ],
            [
                'id' => 'previsao',
                'title' => 'Track values and sales forecast',
                'paragraphs' => [
                    'Recording the expected value of opportunities lets you see the volume of business currently under negotiation.',
                    'A forecast is not a promise of revenue, but it helps managers understand the size and maturity of the pipeline.',
                ],
                'subtitle' => 'Pay particular attention to',
                'items' => [
                    'Total value under negotiation.',
                    'Opportunities by stage.',
                    'Negotiations without recent activity.',
                    'Owners with higher volume.',
                    'Opportunities close to closing.',
                ],
            ],
            [
                'id' => 'ganhos-perdas',
                'title' => 'Record wins and losses',
                'paragraphs' => [
                    'Closing opportunities correctly is just as important as creating them.',
                    'Recording won sales and lost negotiations creates history and helps improve the sales process over time.',
                ],
                'subtitle' => 'A loss can also generate learning',
                'items' => [
                    'Price.',
                    'Timeline.',
                    'Competitor.',
                    'Lack of priority.',
                    'Unmet need.',
                ],
            ],
            [
                'id' => 'noobstron',
                'title' => 'How to apply this process in Noobstron',
                'paragraphs' => [
                    'In Noobstron, leads, pipelines, opportunities and activities can be used together to keep the sales process connected.',
                    'The goal is to let the team quickly understand what is under negotiation, who owns it and what needs to happen next.',
                ],
                'subtitle' => 'Recommended sequence',
                'items' => [
                    'Define a simple pipeline.',
                    'Create or organize your leads.',
                    'Convert real negotiations into opportunities.',
                    'Define the owner and expected value.',
                    'Record activities and next steps.',
                    'Update the stage as the negotiation evolves.',
                    'Record a win or loss when closing.',
                ],
                'example_title' => 'Expected result',
                'example_text' => 'When opening the pipeline, your team should be able to understand which deals are active, where they are and what needs to happen to move them forward.',
            ],
        ],

        'checklist_title' => 'Checklist for an organized sales process',
        'checklist_description' => 'Before adding more advanced automation, confirm that the basic process is working.',

        'checklist' => [
            'Pipeline with clear stages.',
            'Leads with source and owner.',
            'Real opportunities separated from initial contacts.',
            'Next activities defined.',
            'Negotiation values updated.',
            'Opportunities moved according to reality.',
            'Wins and losses recorded.',
        ],

        'cta' => [
            'title' => 'Turn your sales process into a clear flow.',
            'description' => 'Start with a simple pipeline, organize your next steps and evolve as your team becomes more consistent.',
            'trial' => 'Start my trial',
            'previous' => 'View customer organization guide',
        ],
    ],
    'follow_up' => [
        'meta_title' => 'How to improve follow-up and organize sales activities — Noobstron',
        'meta_description' => 'Learn how to organize follow-ups, tasks, meetings, customer callbacks and next steps so sales opportunities do not get lost.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 04 • Follow-up and activities',
        'title' => 'How to improve follow-up and organize sales activities.',
        'lead' => 'Good follow-up turns conversations into clear next steps. In this guide, you will learn how to organize activities, assign owners, manage deadlines and prevent important opportunities from being forgotten.',

        'nav_title' => 'In this guide',

        'nav' => [
            'fundamentos' => '1. Understand follow-up',
            'proximo-passo' => '2. Always define the next step',
            'tipos' => '3. Organize activity types',
            'prazos' => '4. Work with deadlines',
            'responsaveis' => '5. Define owners',
            'prioridades' => '6. Prioritize correctly',
            'contexto' => '7. Record context',
            'rotina' => '8. Create a follow-up routine',
            'atrasadas' => '9. Handle overdue activities',
            'noobstron' => '10. How to apply it in Noobstron',
        ],

        'sections' => [
            [
                'id' => 'fundamentos',
                'title' => 'Understand the role of follow-up',
                'paragraphs' => [
                    'Follow-up is the action taken after a commercial interaction to keep a negotiation moving forward.',
                    'It should not be just a message asking whether the customer has made a decision. Good follow-up has context, purpose and a clear next step.',
                ],
                'box_title' => 'Follow-up is not pressure',
                'box_text' => 'The goal is to continue the conversation at the right time, with a clear reason for making contact again.',
            ],
            [
                'id' => 'proximo-passo',
                'title' => 'Always define the next step',
                'paragraphs' => [
                    'A negotiation without a defined next action has a high chance of being forgotten.',
                    'At the end of a call, meeting or message exchange, record what needs to happen next.',
                ],
                'subtitle' => 'Useful questions',
                'items' => [
                    'What is the next action?',
                    'Who is responsible?',
                    'When should it happen?',
                    'What needs to be ready first?',
                    'What result do we expect from this action?',
                ],
                'example_title' => 'Example',
                'example_text' => 'After a demo, instead of simply waiting, create an activity to send the proposal by Friday and another one to follow up three days later.',
            ],
            [
                'id' => 'tipos',
                'title' => 'Organize activity types',
                'paragraphs' => [
                    'Not every sales activity is the same. Separating activity types helps the team quickly understand what needs to be done.',
                ],
                'subtitle' => 'Common activities',
                'items' => [
                    'Phone call.',
                    'Meeting.',
                    'Demo.',
                    'Send proposal.',
                    'Follow-up.',
                    'Customer-requested callback.',
                    'Confirm documentation.',
                    'Internal review.',
                ],
                'after_list' => 'Use simple and consistent names. Too many different activity types can create more confusion than value.',
            ],
            [
                'id' => 'prazos',
                'title' => 'Work with realistic deadlines',
                'paragraphs' => [
                    'Activities without deadlines end up competing with everything that appears more urgent during the day.',
                    'Setting dates helps turn intention into commitment and makes overdue work easier to identify.',
                ],
                'box_title' => 'Avoid artificial dates',
                'box_text' => 'Do not schedule everything for tomorrow just to clear the list. Use dates that represent when the action should realistically happen.',
            ],
            [
                'id' => 'responsaveis',
                'title' => 'Assign an owner to each activity',
                'paragraphs' => [
                    'Every important task should have someone clearly responsible for executing it.',
                    'This prevents situations where several people know something needs to be done, but nobody understands that they own the task.',
                ],
                'subtitle' => 'Clear ownership helps answer',
                'items' => [
                    'Who should execute it?',
                    'Who needs to follow the progress?',
                    'Who takes over if the owner is unavailable?',
                ],
            ],
            [
                'id' => 'prioridades',
                'title' => 'Prioritize activities by impact',
                'paragraphs' => [
                    'Not every task has the same commercial importance.',
                    'Activities tied to opportunities near closing or customers waiting for a response often deserve higher priority.',
                ],
                'subtitle' => 'Useful criteria',
                'items' => [
                    'Activity deadline.',
                    'Opportunity value.',
                    'Negotiation stage.',
                    'Time since the last contact.',
                    'Commitment made to the customer.',
                ],
                'after_list' => 'Priority should not be based only on the order in which tasks arrived.',
            ],
            [
                'id' => 'contexto',
                'title' => 'Record context with the activity',
                'paragraphs' => [
                    'A task named only “call customer” may not mean much a few days later.',
                    'Record enough information so the owner understands why the activity exists and what needs to be resolved.',
                ],
                'example_title' => 'Example',
                'example_text' => 'Instead of “call customer,” write “call to confirm approval of the proposal sent on 18/08 and validate the implementation timeline.”',
            ],
            [
                'id' => 'rotina',
                'title' => 'Create a follow-up routine',
                'paragraphs' => [
                    'Follow-up works better when it is part of the routine instead of depending on someone remembering it spontaneously.',
                    'Set aside time to review today’s activities, future tasks and negotiations that have gone without movement.',
                ],
                'subtitle' => 'A simple routine may include',
                'items' => [
                    'Review overdue activities at the start of the day.',
                    'Complete tasks planned for today.',
                    'Confirm next steps after meetings.',
                    'Review opportunities without future activities.',
                    'Prepare actions for the next day.',
                ],
            ],
            [
                'id' => 'atrasadas',
                'title' => 'Handle overdue activities without hiding the problem',
                'paragraphs' => [
                    'Overdue activities show where the process has lost momentum.',
                    'Instead of simply changing every date, understand why the task was not completed and decide what actually needs to happen.',
                ],
                'subtitle' => 'When you find an overdue activity',
                'items' => [
                    'Complete it immediately if it still makes sense.',
                    'Reschedule it with a realistic new date.',
                    'Reassign it if another owner is more appropriate.',
                    'Cancel it if the activity is no longer relevant.',
                    'Update the opportunity if the negotiation has changed.',
                ],
                'box_title' => 'Delays also provide information',
                'box_text' => 'If many activities of the same type are overdue, there may be a capacity, priority or process issue.',
            ],
            [
                'id' => 'noobstron',
                'title' => 'How to apply follow-up and activities in Noobstron',
                'paragraphs' => [
                    'In Noobstron, activities can connect tasks and next steps to the context of customers and opportunities.',
                    'This helps the team understand not only what needs to be done, but also why that action exists.',
                ],
                'subtitle' => 'Recommended sequence',
                'items' => [
                    'Open the related customer or opportunity.',
                    'Create the next activity.',
                    'Define owner and deadline.',
                    'Include enough context for execution.',
                    'Complete the activity when it is done.',
                    'Immediately record the next step when necessary.',
                    'Review overdue activities regularly.',
                ],
                'example_title' => 'Expected result',
                'example_text' => 'At the start of the day, each person should be able to quickly identify which actions they need to execute and which negotiations depend on those activities to move forward.',
            ],
        ],

        'checklist_title' => 'Checklist for organized follow-up',
        'checklist_description' => 'Check whether your team has a simple and consistent process for managing next steps.',

        'checklist' => [
            'Active opportunities have a defined next action.',
            'Activities have an owner.',
            'Deadlines represent realistic dates.',
            'Tasks include enough context.',
            'Completed activities are recorded.',
            'Overdue tasks are reviewed.',
            'New next steps are defined after important interactions.',
        ],

        'cta' => [
            'title' => 'Turn follow-up into commercial consistency.',
            'description' => 'Organize activities, keep next steps clear and reduce opportunities lost because of missing follow-up.',
            'trial' => 'Start my trial',
            'previous' => 'View sales process guide',
        ],
    ],
    'communication' => [
        'meta_title' => 'How to centralize customer communication — Noobstron',
        'meta_description' => 'Learn how to organize email, WhatsApp, history, context, and templates to improve customer communication.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 05 • Customer communication',
        'title' => 'How to centralize communication with your customers.',
        'lead' => 'When conversations are scattered across email, WhatsApp, and different team members, context gets lost. In this guide, you will learn how to organize communication so everyone knows what has already been discussed and what should happen next.',

        'nav_title' => 'In this guide',

        'nav' => [
            'problema' => '1. Understand the problem',
            'canais' => '2. Organize your channels',
            'contexto' => '3. Maintain context',
            'email' => '4. Organize email',
            'whatsapp' => '5. Organize WhatsApp',
            'templates' => '6. Standardize messages',
            'historico' => '7. Preserve history',
            'responsaveis' => '8. Define responsibilities',
            'boas-praticas' => '9. Avoid common mistakes',
            'noobstron' => '10. Apply it in Noobstron',
        ],

        'sections' => [
            [
                'id' => 'problema',
                'title' => 'Understand the problem with scattered communication',
                'paragraphs' => [
                    'When each salesperson uses their own email inbox, WhatsApp, and notes, much of the customer relationship becomes trapped in individual conversations.',
                    'This makes continuity, management, and collaboration more difficult, especially when someone else needs to take over the conversation.',
                ],
                'box_title' => 'Communication without context creates rework',
                'box_text' => 'Customers should not have to repeat their entire story every time they speak with someone different at your company.',
            ],
            [
                'id' => 'canais',
                'title' => 'Organize your communication channels',
                'paragraphs' => [
                    'Each channel can play a different role in the customer relationship.',
                    'The important thing is to prevent relevant information from becoming completely disconnected from the customer record and sales process.',
                ],
                'subtitle' => 'Common channels',
                'items' => [
                    'Email.',
                    'WhatsApp.',
                    'Phone.',
                    'Meetings.',
                    'Website forms.',
                    'Other channels used by your operation.',
                ],
                'after_list' => 'You do not need to eliminate channels. You need to make sure important context can reach the rest of the team.',
            ],
            [
                'id' => 'contexto',
                'title' => 'Keep communication connected to context',
                'paragraphs' => [
                    'An isolated message says very little. The same message connected to the customer, opportunity, and sales history becomes much more valuable.',
                    'Whenever possible, connect communication to the record that explains why that conversation is happening.',
                ],
                'example_title' => 'Example',
                'example_text' => 'A reply about pricing makes much more sense when the team can see the proposal that was sent, the related opportunity, and previous activities.',
            ],
            [
                'id' => 'email',
                'title' => 'Organize email communication',
                'paragraphs' => [
                    'Email remains an important channel for proposals, documents, confirmations, and formal communication.',
                    'Problems arise when important decisions remain only inside personal inboxes.',
                ],
                'subtitle' => 'Good practices',
                'items' => [
                    'Use clear subject lines.',
                    'Keep the customer identified.',
                    'Avoid keeping critical information only in personal inboxes.',
                    'Record important decisions in the sales context.',
                    'Use templates when messages are repeated.',
                ],
            ],
            [
                'id' => 'whatsapp',
                'title' => 'Organize WhatsApp conversations',
                'paragraphs' => [
                    'WhatsApp is fast and convenient, but it can also fragment the history when each conversation remains only on one person’s device.',
                    'Important messages should remain connected to the customer relationship whenever possible.',
                ],
                'box_title' => 'Speed without organization creates dependency',
                'box_text' => 'If only one person knows what was discussed on WhatsApp, the company becomes dependent on that person to continue serving the customer.',
            ],
            [
                'id' => 'templates',
                'title' => 'Standardize repetitive messages',
                'paragraphs' => [
                    'Templates help your team move faster and maintain quality when messages follow a similar structure.',
                    'They should not make communication feel robotic. The best approach is to start with a prepared structure and adapt it to the customer’s context.',
                ],
                'subtitle' => 'Templates can help with',
                'items' => [
                    'Initial contact.',
                    'Meeting confirmation.',
                    'Proposal delivery.',
                    'Follow-up.',
                    'Document requests.',
                    'Post-service follow-up.',
                ],
                'after_list' => 'Review templates periodically to make sure they remain clear and up to date.',
            ],
            [
                'id' => 'historico',
                'title' => 'Preserve the relationship history',
                'paragraphs' => [
                    'The history allows the team to understand how the relationship has evolved over time.',
                    'This is especially important when there are multiple contacts, different owners, or recurring negotiations.',
                ],
                'example_title' => 'Example',
                'example_text' => 'Before replying to a customer, someone can review previous messages, proposals, activities, and decisions to avoid contradictory responses.',
            ],
            [
                'id' => 'responsaveis',
                'title' => 'Define communication responsibilities',
                'paragraphs' => [
                    'When several people can respond to the same customer, it is important to know who is leading that communication.',
                    'Clear responsibility reduces duplicate replies and prevents important messages from going unanswered.',
                ],
                'subtitle' => 'Define especially',
                'items' => [
                    'Who responds first.',
                    'Who manages the relationship.',
                    'Who takes over when the owner is unavailable.',
                    'When a conversation should be escalated.',
                ],
            ],
            [
                'id' => 'boas-praticas',
                'title' => 'Avoid common communication mistakes',
                'paragraphs' => [
                    'Centralizing communication does not simply mean gathering messages in one place. You also need consistent practices that preserve clarity and context.',
                ],
                'subtitle' => 'Mistakes worth avoiding',
                'items' => [
                    'Replying without checking the history.',
                    'Keeping important decisions only in private conversations.',
                    'Using templates without adapting them to the customer.',
                    'Sending duplicate messages because ownership is unclear.',
                    'Leaving messages without a next step.',
                    'Mixing several unrelated topics in the same conversation without context.',
                ],
                'after_list' => 'The best communication remains understandable to both the customer and the team.',
            ],
            [
                'id' => 'noobstron',
                'title' => 'How to centralize communication in Noobstron',
                'paragraphs' => [
                    'In Noobstron, communication can be organized alongside the sales context of customers, activities, and opportunities.',
                    'Email, WhatsApp, templates, and history can work together to reduce context loss and improve continuity.',
                ],
                'subtitle' => 'Recommended sequence',
                'items' => [
                    'Organize the customer record.',
                    'Keep contacts and channels up to date.',
                    'Use email and WhatsApp connected to the relevant context.',
                    'Record important activities.',
                    'Use templates for recurring messages.',
                    'Review the history before making new contact.',
                    'Define the next step after relevant conversations.',
                ],
                'example_title' => 'Expected result',
                'example_text' => 'Any authorized team member should be able to quickly understand what has already been discussed, who is managing the relationship, and what action needs to happen next.',
            ],
        ],

        'checklist_title' => 'Checklist for organized communication',
        'checklist_description' => 'Check whether your team can maintain context even when customers use different channels.',

        'checklist' => [
            'Main communication channels are identified.',
            'Important information does not remain only in private conversations.',
            'Authorized team members can review the history.',
            'Communication responsibilities are clear.',
            'Templates are used with personalization.',
            'Important messages generate next steps.',
            'Customers do not need to repeat the context with every new interaction.',
        ],

        'cta' => [
            'title' => 'Turn conversations into organized relationships.',
            'description' => 'Centralize context, organize your channels, and make it easier to maintain continuity across customer service and sales.',
            'trial' => 'Start my trial',
            'previous' => 'View follow-up and activities guide',
        ],
    ],
    'results' => [
        'meta_title' => 'How to track results and improve your sales process — Noobstron',
        'meta_description' => 'Learn how to track sales metrics, conversion, pipeline, productivity, and losses to continuously improve your sales process.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 06 • Results and improvement',
        'title' => 'How to track results and improve your sales process.',
        'lead' => 'Organizing your process is only the beginning. The next step is understanding what is working, where the bottlenecks are, and which changes actually help your team sell better.',

        'nav_title' => 'In this guide',

        'nav' => [
            'fundamentos' => '1. Measure to learn',
            'pipeline' => '2. Analyze your pipeline',
            'conversao' => '3. Track conversion',
            'velocidade' => '4. Monitor sales cycle time',
            'atividades' => '5. Analyze activities',
            'ganhos' => '6. Understand your wins',
            'perdas' => '7. Learn from losses',
            'equipe' => '8. Evaluate productivity',
            'melhoria' => '9. Improve continuously',
            'noobstron' => '10. Apply it in Noobstron',
        ],

        'sections' => [
            [
                'id' => 'fundamentos',
                'title' => 'Measure to learn, not just to demand results',
                'paragraphs' => [
                    'Sales metrics turn perception into evidence.',
                    'When data is used only to pressure the team, people tend to see metrics as a source of stress. When it is used to understand the process, it helps uncover bottlenecks and opportunities for improvement.',
                ],
                'box_title' => 'Not every metric needs to become a target',
                'box_text' => 'Some numbers exist simply to help you understand how the process behaves and make better decisions.',
            ],
            [
                'id' => 'pipeline',
                'title' => 'Analyze the health of your pipeline',
                'paragraphs' => [
                    'The pipeline shows the volume and distribution of opportunities across each stage of the sales process.',
                    'More important than looking only at the total value is understanding whether opportunities are progressing in a healthy way.',
                ],
                'subtitle' => 'Pay particular attention to',
                'items' => [
                    'Number of opportunities at each stage.',
                    'Total value under negotiation.',
                    'Opportunities that have been stalled for too long.',
                    'Deals without a next activity.',
                    'Too many opportunities concentrated in a single stage.',
                ],
                'after_list' => 'A full pipeline does not necessarily mean a healthy pipeline.',
            ],
            [
                'id' => 'conversao',
                'title' => 'Track conversion rates',
                'paragraphs' => [
                    'Conversion helps you understand how many opportunities manage to move forward or reach a successful close.',
                    'You can measure it between individual funnel stages or across the entire process, from the first contact to the sale.',
                ],
                'example_title' => 'Example',
                'example_text' => 'If many opportunities reach the proposal stage but few move on to closing, there may be an issue with pricing, qualification, value proposition, or negotiation.',
            ],
            [
                'id' => 'velocidade',
                'title' => 'Monitor how long sales take',
                'paragraphs' => [
                    'Sales cycle time shows how long an opportunity takes to move through the sales process.',
                    'When certain stages become too slow, this may indicate insufficient follow-up, approval dependencies, or difficulty reaching a decision.',
                ],
                'subtitle' => 'Useful questions',
                'items' => [
                    'How long does an opportunity remain at each stage?',
                    'Where do the biggest delays occur?',
                    'Which types of deals take longer?',
                    'Are there activities that could accelerate progress?',
                ],
            ],
            [
                'id' => 'atividades',
                'title' => 'Analyze sales activities',
                'paragraphs' => [
                    'The number of activities alone does not measure quality, but it helps you understand the pace of the operation.',
                    'Ideally, activities should be evaluated in relation to actual opportunity progress and the results achieved.',
                ],
                'subtitle' => 'Important signals include',
                'items' => [
                    'Opportunities without a future activity.',
                    'A large number of overdue tasks.',
                    'Many activities without stage progression.',
                    'Too few contacts on important opportunities.',
                    'Too much administrative work.',
                ],
                'box_title' => 'Movement is not the same as progress',
                'box_text' => 'A team can perform many activities while advancing very few opportunities. The goal is to understand which actions actually create progress.',
            ],
            [
                'id' => 'ganhos',
                'title' => 'Understand why your team wins',
                'paragraphs' => [
                    'Won deals should also be analyzed.',
                    'Identifying patterns among customers who buy helps improve qualification, approach, positioning, and sales focus.',
                ],
                'subtitle' => 'Look for patterns in',
                'items' => [
                    'Segments with better conversion rates.',
                    'Lead sources that perform best.',
                    'Types of problems being solved.',
                    'Average time to close.',
                    'Stages that progress more easily.',
                ],
            ],
            [
                'id' => 'perdas',
                'title' => 'Learn from lost opportunities',
                'paragraphs' => [
                    'A lost opportunity does not have to be only a negative outcome. It can provide valuable information for improving the process.',
                    'Recording loss reasons allows you to identify patterns that would remain invisible if every unsuccessful deal were simply marked as “lost”.',
                ],
                'subtitle' => 'Common reasons',
                'items' => [
                    'Price.',
                    'Competitor.',
                    'Timing.',
                    'Lack of customer priority.',
                    'Unmet need.',
                    'No available budget.',
                    'Deal ended without a decision.',
                ],
                'after_list' => 'Loss reasons should be simple enough for the team to record them consistently.',
            ],
            [
                'id' => 'equipe',
                'title' => 'Evaluate productivity with context',
                'paragraphs' => [
                    'Comparing only the number of sales between people can lead to incorrect conclusions.',
                    'Account portfolio, region, customer type, opportunity value, and deal stage also influence results.',
                ],
                'box_title' => 'Use indicators to guide conversations',
                'box_text' => 'The goal is to understand where each person needs support, training, or better conditions to execute the process.',
            ],
            [
                'id' => 'melhoria',
                'title' => 'Create a continuous improvement cycle',
                'paragraphs' => [
                    'A sales process does not need to remain the same forever.',
                    'As new data becomes available, you can adjust stages, activities, qualification criteria, and priorities.',
                ],
                'subtitle' => 'A simple improvement routine',
                'items' => [
                    'Review the data.',
                    'Identify one specific bottleneck.',
                    'Choose a small change.',
                    'Apply it for a period of time.',
                    'Compare the results.',
                    'Keep, adjust, or discard the change.',
                ],
                'after_list' => 'Avoid changing too many things at once. When everything changes, it becomes difficult to understand what actually produced the result.',
            ],
            [
                'id' => 'noobstron',
                'title' => 'How to track results in Noobstron',
                'paragraphs' => [
                    'In Noobstron, the information recorded throughout the sales process provides the foundation for tracking how your operation evolves.',
                    'Customers, leads, opportunities, activities, values, stages, wins, and losses help build a more complete view of sales performance.',
                ],
                'subtitle' => 'Recommended sequence',
                'items' => [
                    'Keep opportunities and stages up to date.',
                    'Record deal values.',
                    'Complete activities correctly.',
                    'Record wins and losses.',
                    'Review stalled opportunities.',
                    'Look for patterns in conversion and cycle time.',
                    'Use the data to adjust the process.',
                ],
                'example_title' => 'Expected outcome',
                'example_text' => 'Your team stops working based only on intuition and becomes able to explain where the process is working, where it is getting stuck, and which improvement should be tested next.',
            ],
        ],

        'checklist_title' => 'Checklist for improving your process',
        'checklist_description' => 'Check whether your operation already has enough data to begin a continuous improvement cycle.',

        'checklist' => [
            'The pipeline is up to date.',
            'Opportunity values are recorded.',
            'Wins and losses are closed correctly.',
            'Loss reasons are recorded.',
            'Overdue activities are monitored.',
            'Conversion can be measured between stages.',
            'Process changes are based on evidence.',
        ],

        'cta' => [
            'title' => 'Turn sales data into better decisions.',
            'description' => 'Organize your process, track results, and improve through small changes based on what is actually happening in your operation.',
            'trial' => 'Start my trial',
            'previous' => 'View communication guide',
        ],
    ],
    'automation' => [
        'meta_title' => 'How to automate and scale your sales process — Noobstron',
        'meta_description' => 'Learn how to automate tasks, create triggers, standardize processes, and scale your sales operation safely.',

        'back' => '← Learning Center',
        'eyebrow' => 'Guide 07 • Automation and scale',
        'title' => 'How to automate and scale your sales process.',
        'lead' => 'Automation works best when the process is already organized. In this guide, you will learn what to automate, where to keep human control, and how to scale without losing context, quality, or accountability.',

        'nav_title' => 'In this guide',

        'nav' => [
            'fundamentos' => '1. Automate what already works',
            'gatilhos' => '2. Understand triggers',
            'tarefas' => '3. Automate repetitive tasks',
            'comunicacao' => '4. Automate with context',
            'responsabilidade' => '5. Preserve ownership',
            'seguranca' => '6. Protect data and access',
            'excecoes' => '7. Plan for exceptions',
            'monitoramento' => '8. Monitor automations',
            'escala' => '9. Scale with simplicity',
            'noobstron' => '10. Apply it in Noobstron',
        ],

        'sections' => [
            [
                'id' => 'fundamentos',
                'title' => 'Automate processes that already work',
                'paragraphs' => [
                    'Automation does not fix a confusing process. It simply executes faster what has already been defined.',
                    'Before automating, make sure stages, owners, data, and next steps are clear.',
                ],
                'box_title' => 'Automating a problem also scales the problem',
                'box_text' => 'If the process still changes every day or depends on undocumented decisions, stabilize it before creating automatic rules.',
            ],
            [
                'id' => 'gatilhos',
                'title' => 'Understand the role of triggers',
                'paragraphs' => [
                    'Every automation starts with an event that determines when an action should happen.',
                    'That event needs to be objective enough to avoid unexpected executions.',
                ],
                'subtitle' => 'Examples of triggers',
                'items' => [
                    'A new lead is created.',
                    'An opportunity changes stage.',
                    'An activity becomes overdue.',
                    'A sale is marked as won.',
                    'A customer replies to a message.',
                    'A record remains inactive for a defined period.',
                ],
                'after_list' => 'The clearer the trigger, the easier it is to understand why the automation ran.',
            ],
            [
                'id' => 'tarefas',
                'title' => 'Start with repetitive tasks',
                'paragraphs' => [
                    'The best first automations are usually predictable, low-risk tasks.',
                    'They save time without removing important decisions from the people responsible for the process.',
                ],
                'subtitle' => 'Good candidates',
                'items' => [
                    'Create an activity after a stage change.',
                    'Set a follow-up reminder.',
                    'Update predictable fields.',
                    'Distribute records using simple rules.',
                    'Generate internal notifications.',
                    'Prepare routine tasks.',
                ],
                'example_title' => 'Example',
                'example_text' => 'When an opportunity enters the “Proposal sent” stage, the system can automatically create a follow-up activity for three days later.',
            ],
            [
                'id' => 'comunicacao',
                'title' => 'Automate communication without losing context',
                'paragraphs' => [
                    'Automated messages can speed up confirmations, reminders, and simple responses.',
                    'But sales communication still needs to take the customer and negotiation context into account.',
                ],
                'subtitle' => 'Use automation mainly for',
                'items' => [
                    'Confirmations.',
                    'Reminders.',
                    'Transactional messages.',
                    'Internal alerts.',
                    'Standardized initial responses.',
                ],
                'box_title' => 'Automation should not feel like abandonment',
                'box_text' => 'When a situation requires negotiation, sensitivity, or interpretation, the conversation should return to a person.',
            ],
            [
                'id' => 'responsabilidade',
                'title' => 'Preserve clear ownership',
                'paragraphs' => [
                    'Even when an automation performs an action, someone should remain responsible for the outcome of that process.',
                    'This prevents important tasks from becoming ownerless because “the system was supposed to do it”.',
                ],
                'subtitle' => 'Always make clear',
                'items' => [
                    'Who owns the record.',
                    'Who receives failure alerts.',
                    'Who can change the automation.',
                    'Who reviews the results.',
                    'Who handles exceptions.',
                ],
            ],
            [
                'id' => 'seguranca',
                'title' => 'Consider security and access from the beginning',
                'paragraphs' => [
                    'Automation can move information, create records, send messages, and change process states.',
                    'Permissions and scope therefore need the same level of care as manual actions.',
                ],
                'subtitle' => 'Good practices',
                'items' => [
                    'Use only the access that is necessary.',
                    'Avoid sharing credentials.',
                    'Restrict who can edit rules.',
                    'Review the integrations being used.',
                    'Record important changes.',
                ],
                'after_list' => 'The greater the potential impact of an automation, the tighter the control over who can modify it should be.',
            ],
            [
                'id' => 'excecoes',
                'title' => 'Plan for what happens when something breaks the rule',
                'paragraphs' => [
                    'No real-world process consists only of perfect cases.',
                    'A reliable automation needs to consider what happens when data is missing, a condition is not met, or an integration fails.',
                ],
                'example_title' => 'Example',
                'example_text' => 'If an automatic message depends on a valid email address and the contact does not have one, the automation can create a task for the owner instead of failing silently.',
            ],
            [
                'id' => 'monitoramento',
                'title' => 'Monitor whether automations still make sense',
                'paragraphs' => [
                    'A rule created today may stop making sense after the process changes.',
                    'Automations should therefore be reviewed like any other part of the operation.',
                ],
                'subtitle' => 'Review periodically',
                'items' => [
                    'Number of executions.',
                    'Recurring failures.',
                    'Tasks created unnecessarily.',
                    'Messages sent at inappropriate times.',
                    'Rules that no longer reflect the current process.',
                ],
                'box_title' => 'Automation without review becomes operational debt',
                'box_text' => 'Old rules can continue working correctly from a technical perspective while still being wrong for the business.',
            ],
            [
                'id' => 'escala',
                'title' => 'Scale while keeping the process simple',
                'paragraphs' => [
                    'Scaling does not mean creating as many automations as possible.',
                    'It means allowing the same process to support more customers, opportunities, and people without increasing complexity at the same rate.',
                ],
                'subtitle' => 'Signs of healthy scale',
                'items' => [
                    'Fewer repetitive manual tasks.',
                    'Ownership remains clear.',
                    'Data remains organized.',
                    'Exceptions remain visible.',
                    'The team can understand the existing rules.',
                    'New people can learn the process.',
                ],
                'after_list' => 'If nobody understands why a certain automation exists anymore, it is probably time to simplify it.',
            ],
            [
                'id' => 'noobstron',
                'title' => 'How to apply automation in Noobstron',
                'paragraphs' => [
                    'In Noobstron, automations can support repetitive activities throughout the sales process without replacing team accountability.',
                    'The goal is to reduce predictable manual work and leave more time for decisions, relationships, and negotiation.',
                ],
                'subtitle' => 'Recommended sequence',
                'items' => [
                    'Choose a process that is already stable.',
                    'Identify one repetitive task.',
                    'Define a clear trigger.',
                    'Choose a simple action.',
                    'Define who handles exceptions.',
                    'Test with a small number of cases.',
                    'Review the result before expanding.',
                ],
                'example_title' => 'Expected result',
                'example_text' => 'The team performs less repetitive work, keeps visibility over the process, and can handle more volume without losing control.',
            ],
        ],

        'checklist_title' => 'Checklist before automating',
        'checklist_description' => 'Confirm that the automation reduces work without creating risks or hiding accountability.',

        'checklist' => [
            'The manual process is already clear.',
            'The trigger is objective.',
            'The automated action is predictable.',
            'Someone is responsible for exceptions.',
            'Permissions are appropriate.',
            'Failures can be identified.',
            'The automation will be reviewed periodically.',
        ],

        'cta' => [
            'title' => 'Scale without losing control.',
            'description' => 'Automate predictable tasks, keep important decisions with your team, and evolve your rules as the operation grows.',
            'trial' => 'Start my trial',
            'previous' => 'View results and improvement guide',
        ],
    ],];