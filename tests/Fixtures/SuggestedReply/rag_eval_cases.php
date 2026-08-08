<?php

declare(strict_types=1);

/**
 * RAG eval fixtures for the Fake AI stack (OPENAI_FAKE in phpunit.xml).
 *
 * catalog: reusable knowledge articles keyed by slug.
 * cases: ticket question → expected grounded answer/sources or refuse.
 */
return [
    'catalog' => [
        'password-reset' => [
            'title' => 'Password reset',
            'body' => 'Open Settings → Security → Reset password. Link expires in 24 hours.',
            'is_published' => true,
        ],
        'billing-invoice' => [
            'title' => 'Download invoice',
            'body' => 'Go to Billing → Invoices → Download PDF. Invoices appear within 24 hours after payment.',
            'is_published' => true,
        ],
        'two-factor-auth' => [
            'title' => 'Two-factor authentication',
            'body' => 'Enable 2FA under Security → Authenticator app. Keep backup codes in a safe place.',
            'is_published' => true,
        ],
        'shipping-delays' => [
            'title' => 'Shipping delays',
            'body' => 'Orders ship in 2–3 business days. Track packages from Orders → Tracking.',
            'is_published' => true,
        ],
        'refund-policy' => [
            'title' => 'Refund policy',
            'body' => 'Request a refund within 14 days from Billing → Refunds. Refunds post in 5–7 business days.',
            'is_published' => true,
        ],
        'unpublished-draft' => [
            'title' => 'Internal password notes',
            'body' => 'Staff-only reset steps. Never share with customers.',
            'is_published' => false,
        ],
    ],
    'cases' => [
        [
            'name' => 'password_reset_cites_article',
            'articles' => ['password-reset', 'billing-invoice', 'two-factor-auth'],
            'ticket' => [
                'subject' => 'Password help',
                'body' => 'How do I reset my password?',
            ],
            'expect' => [
                'refused' => false,
                'source_slugs' => ['password-reset'],
                'answer_contains' => ['Password reset', '24 hours'],
            ],
        ],
        [
            'name' => 'billing_invoice_cites_article',
            'articles' => ['password-reset', 'billing-invoice', 'refund-policy'],
            'ticket' => [
                'subject' => 'Need my invoice',
                'body' => 'Where can I download my invoice PDF?',
            ],
            'expect' => [
                'refused' => false,
                'source_slugs' => ['billing-invoice'],
                'answer_contains' => ['Download invoice', 'Billing'],
            ],
        ],
        [
            'name' => 'two_factor_cites_article',
            'articles' => ['two-factor-auth', 'password-reset', 'shipping-delays'],
            'ticket' => [
                'subject' => 'Authenticator setup',
                'body' => 'How do I enable two-factor authentication with an authenticator app?',
            ],
            'expect' => [
                'refused' => false,
                'source_slugs' => ['two-factor-auth'],
                'answer_contains' => ['Two-factor authentication', 'backup codes'],
            ],
        ],
        [
            'name' => 'shipping_delays_cites_article',
            'articles' => ['shipping-delays', 'billing-invoice', 'password-reset'],
            'ticket' => [
                'subject' => 'Where is my package',
                'body' => 'How long do shipping delays take and how do I track orders?',
            ],
            'expect' => [
                'refused' => false,
                'source_slugs' => ['shipping-delays'],
                'answer_contains' => ['Shipping delays', 'Tracking'],
            ],
        ],
        [
            'name' => 'refund_policy_cites_article',
            'articles' => ['refund-policy', 'billing-invoice', 'two-factor-auth'],
            'ticket' => [
                'subject' => 'Want a refund',
                'body' => 'What is your refund policy and how many days to request a refund?',
            ],
            'expect' => [
                'refused' => false,
                'source_slugs' => ['refund-policy'],
                'answer_contains' => ['Refund policy', '14 days'],
            ],
        ],
        [
            'name' => 'empty_knowledge_base_refuses',
            'articles' => [],
            'ticket' => [
                'subject' => 'Password help',
                'body' => 'How do I reset my password?',
            ],
            'expect' => [
                'refused' => true,
                'refuse_reason' => 'empty_passages',
                'source_slugs' => [],
            ],
        ],
        [
            'name' => 'unpublished_only_refuses',
            'articles' => ['unpublished-draft'],
            'ticket' => [
                'subject' => 'Password help',
                'body' => 'How do I reset my password?',
            ],
            'expect' => [
                'refused' => true,
                'refuse_reason' => 'empty_passages',
                'source_slugs' => [],
            ],
        ],
        [
            'name' => 'irrelevant_topic_refuses',
            'articles' => ['password-reset', 'billing-invoice'],
            'ticket' => [
                'subject' => 'Spaceship warranty',
                'body' => 'Does my hyperdrive warranty cover nebula corrosion?',
            ],
            'expect' => [
                'refused' => true,
                'refuse_reason' => 'insufficient_context',
                'source_slugs' => [],
            ],
        ],
    ],
];
