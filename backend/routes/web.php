<?php

use App\Http\Controllers\SubscriptionBillingController;
use App\Http\Controllers\MercadoPagoSubscriptionWebhookController;
use App\Http\Controllers\StripeSubscriptionWebhookController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Middleware\ResolveTenant;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PipelineStageController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\SelfServiceRegistrationController;
use App\Http\Controllers\WorkspaceLoginController;
use App\Http\Controllers\PublicLocaleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CommercialContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\PlatformTenantController;
use App\Http\Controllers\PlatformCommercialContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\CompanyOnboardingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerRelationshipController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\FinancialIndicatorController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\WhatsAppTemplateController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\Illuminate\Http\Request $request) {
    $centralHost = parse_url(
        (string) config('app.url'),
        PHP_URL_HOST
    ) ?: 'localhost';

    if ($request->getHost() !== $centralHost) {
        $tenant = $request->attributes->get('tenant');

        if ($tenant === null) {
            abort(404, 'Tenant not found.');
        }

        return auth()->check()
            ? redirect('/dashboard')
            : redirect('/login');
    }

    return view('marketing.home');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(\App\Http\Middleware\PublicLocale::class)
    ->name('marketing.home');

Route::get('/aprender', function () {
    return view('marketing.learn.index');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.index');

Route::get('/aprender/primeiros-passos', function () {
    return view('marketing.learn.getting-started');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.getting-started');

Route::get('/aprender/organizar-clientes', function () {
    return view('marketing.learn.customers');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.customers');

Route::get('/aprender/processo-de-vendas', function () {
    return view('marketing.learn.sales');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.sales');

Route::get('/aprender/follow-up-e-atividades', function () {
    return view('marketing.learn.follow-up');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.follow-up');

Route::get('/aprender/centralizar-comunicacao', function () {
    return view('marketing.learn.communication');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.communication');

Route::get('/aprender/resultados-e-evolucao', function () {
    return view('marketing.learn.results');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.results');

Route::get('/aprender/automatize-e-escale', function () {
    return view('marketing.learn.automation');
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(
        \App\Http\Middleware\PublicLocale::class
    )
    ->name('marketing.learn.automation');

Route::get('/terms', function () {
    return view('marketing.legal', [
        'document' => 'terms',
    ]);
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(\App\Http\Middleware\PublicLocale::class)
    ->name('marketing.terms');

Route::get('/privacy', function () {
    return view('marketing.legal', [
        'document' => 'privacy',
    ]);
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(\App\Http\Middleware\PublicLocale::class)
    ->name('marketing.privacy');

Route::get('/robots.txt', function () {
    $sitemapUrl = url('/sitemap.xml');

    return response(
        "User-agent: *\n"
            . "Disallow:\n"
            . "Sitemap: {$sitemapUrl}\n",
        200,
        [
            'Content-Type' =>
                'text/plain; charset=UTF-8',
        ]
    );
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('marketing.robots');

Route::post(
    '/contact',
    [CommercialContactController::class, 'store']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware([
        \App\Http\Middleware\PublicLocale::class,
        'throttle:5,1',
    ])
    ->name('marketing.contact.store');

Route::get('/sitemap.xml', function () {
    $entries = [
        [route('marketing.home'), 'weekly', '1.0'],
        [route('register'), 'weekly', '0.9'],
        [
            route('marketing.learn.index'),
            'weekly',
            '0.8',
        ],
        [
            route('marketing.learn.getting-started'),
            'weekly',
            '0.9',
        ],
        [
            route('marketing.learn.customers'),
            'weekly',
            '0.9',
        ],
        [
            route('marketing.learn.sales'),
            'weekly',
            '0.9',
        ],
        [
            route('marketing.learn.follow-up'),
            'weekly',
            '0.9',
        ],
        [
            route('marketing.learn.communication'),
            'weekly',
            '0.9',
        ],
        [
            route('marketing.learn.results'),
            'weekly',
            '0.9',
        ],
        [
            route('marketing.learn.automation'),
            'weekly',
            '0.9',
        ],
        [route('marketing.terms'), 'monthly', '0.4'],
        [route('marketing.privacy'), 'monthly', '0.4'],
    ];

    $urls = collect($entries)
        ->map(function (array $entry): string {
            [$url, $changefreq, $priority] = $entry;

            return implode("\n", [
                '    <url>',
                '        <loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>',
                '        <changefreq>' . $changefreq . '</changefreq>',
                '        <priority>' . $priority . '</priority>',
                '    </url>',
            ]);
        })
        ->implode("\n");

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        . "\n"
        . $urls
        . "\n"
        . '</urlset>'
        . "\n";

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('marketing.sitemap');
Route::get('/register', function () {
    return view('auth.register', [
        'countries' => config('global.countries', []),
        'defaultCountry' => config(
            'global.defaults.country_code',
            'BR'
        ),
        'locales' => config('global.locales', []),
        'defaultLocale' => config(
            'global.defaults.locale',
            'pt-BR'
        ),
        'plans' => array_values(
            array_filter(
                \App\Support\PlanCatalog::definitions(),
                static fn (array $plan): bool =>
                    in_array(
                        $plan['code'],
                        [
                            'start',
                            'pro',
                            'business',
                        ],
                        true
                    )
            )
        ),
        'defaultPlan' => 'start',
        'trialDays' =>
            \App\Support\TrialPeriod::DEFAULT_DAYS,
    ]);
})
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(\App\Http\Middleware\PublicLocale::class)
    ->name('register');
Route::post(
    '/register',
    [SelfServiceRegistrationController::class, 'store']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('register.store');
Route::get(
    '/email/verify',
    function () {
        return view('auth.verify-email');
    }
)
    ->middleware('auth')
    ->name('verification.notice');

Route::get(
    '/email/verify/{id}/{hash}',
    [
        EmailVerificationController::class,
        'verify',
    ]
)
    ->middleware('signed')
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('verification.verify');

Route::post(
    '/email/verification-notification',
    [
        EmailVerificationController::class,
        'send',
    ]
)
    ->middleware([
        'auth',
        'throttle:6,1',
    ])
    ->name('verification.send');
Route::get(
    '/platform/login',
    [PlatformAdminController::class, 'showLogin']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('platform.login');

Route::post(
    '/platform/login',
    [PlatformAdminController::class, 'login']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('platform.login.store');

Route::get(
    '/platform',
    [PlatformAdminController::class, 'dashboard']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware('platform.admin')
    ->name('platform.dashboard');

Route::get(
    '/platform/tenants',
    [PlatformTenantController::class, 'index']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware('platform.admin')
    ->name('platform.tenants.index');

Route::get(
    '/platform/tenants/{tenant}',
    [PlatformTenantController::class, 'show']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware('platform.admin')
    ->name('platform.tenants.show');
Route::get(
    '/platform/health',
    [PlatformAdminController::class, 'health']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware('platform.admin')
    ->name('platform.health');

Route::get(
    '/platform/contacts',
    [PlatformCommercialContactController::class, 'index']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware('platform.admin')
    ->name('platform.contacts.index');

Route::post(
    '/platform/logout',
    [PlatformAdminController::class, 'logout']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware('platform.admin')
    ->name('platform.logout');
Route::get(
    '/idioma',
    [PublicLocaleController::class, 'update']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('public.locale.update');
Route::get(
    '/entrar',
    [WorkspaceLoginController::class, 'show']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->middleware(\App\Http\Middleware\PublicLocale::class)
    ->name('workspace.login');

Route::post(
    '/entrar',
    [WorkspaceLoginController::class, 'redirect']
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name('workspace.login.redirect');
Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::get(
    '/forgot-password',
    [PasswordResetController::class, 'showForgotPassword']
)->name('password.request');

Route::post(
    '/forgot-password',
    [PasswordResetController::class, 'sendResetLink']
)
    ->middleware('throttle:5,1')
    ->name('password.email');

Route::get(
    '/reset-password/{token}',
    [PasswordResetController::class, 'showResetPassword']
)->name('password.reset');

Route::post(
    '/reset-password',
    [PasswordResetController::class, 'reset']
)
    ->middleware('throttle:10,1')
    ->name('password.update');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('verified')
        ->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::get(
    '/billing',
    [SubscriptionBillingController::class, 'index']
)->name('billing.index');

Route::post(
    '/billing/checkout',
    [SubscriptionBillingController::class, 'checkout']
)->name('billing.checkout');

Route::post(
    '/billing/change-plan',
    [SubscriptionBillingController::class, 'changePlan']
)->name('billing.change-plan');

Route::post(
    '/billing/portal',
    [SubscriptionBillingController::class, 'portal']
)->name('billing.portal');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->middleware('feature:users')
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->middleware('feature:users')
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->middleware('feature:users')
        ->name('users.store');

    Route::get('/users/{id}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.update')
        ->middleware('feature:users')
        ->name('users.edit');

    Route::put('/users/{id}', [UserController::class, 'update'])
        ->middleware('permission:users.update')
        ->middleware('feature:users')
        ->name('users.update');

    Route::delete('/users/{id}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->middleware('feature:users')
        ->name('users.destroy');

    Route::get('/users/{id}/permissions', [UserPermissionController::class, 'edit'])
        ->middleware('permission:users.permissions')
        ->middleware('feature:users')
        ->name('users.permissions.edit');

    Route::put('/users/{id}/permissions', [UserPermissionController::class, 'update'])
        ->middleware('permission:users.permissions')
        ->middleware('feature:users')
        ->name('users.permissions.update');

    Route::get('/leads', [LeadController::class, 'index'])
        ->middleware('permission:leads.view')
        ->middleware('feature:leads')
        ->name('leads.index');

    Route::get('/leads/create', [LeadController::class, 'create'])
        ->middleware('permission:leads.create')
        ->middleware('feature:leads')
        ->name('leads.create');

    Route::post('/leads', [LeadController::class, 'store'])
        ->middleware('permission:leads.create')
        ->middleware('feature:leads')
        ->name('leads.store');

    Route::get('/leads/{id}/edit', [LeadController::class, 'edit'])
        ->middleware('permission:leads.update')
        ->middleware('feature:leads')
        ->name('leads.edit');

    Route::put('/leads/{id}', [LeadController::class, 'update'])
        ->middleware('permission:leads.update')
        ->middleware('feature:leads')
        ->name('leads.update');

    Route::delete('/leads/{id}', [LeadController::class, 'destroy'])
        ->middleware('permission:leads.delete')
        ->middleware('feature:leads')
        ->name('leads.destroy');
    Route::post(
        '/leads/{id}/convert',
        [LeadController::class, 'convert']
    )
        ->middleware('permission:leads.update')
        ->middleware('feature:leads')
        ->middleware('feature:customers')
        ->name('leads.convert');
    Route::get('/audit', [AuditLogController::class, 'index'])
        ->middleware('permission:audit.view')
        ->middleware('feature:audit')
        ->name('audit.index');

    Route::get(
        '/onboarding/company',
        [CompanyOnboardingController::class, 'edit']
    )
        ->name('onboarding.company.edit');

    Route::put(
        '/onboarding/company',
        [CompanyOnboardingController::class, 'update']
    )
        ->name('onboarding.company.update');
    Route::get('/settings', [TenantSettingsController::class, 'edit'])
        ->middleware('permission:settings.update')
        ->middleware('feature:branding')
        ->name('settings.edit');

    Route::put('/settings', [TenantSettingsController::class, 'update'])
        ->middleware('permission:settings.update')
        ->middleware('feature:branding')
        ->name('settings.update');

    Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:customers.view')
        ->middleware('feature:customers')
        ->name('customers.index');

    Route::get('/customers/create', [CustomerController::class, 'create'])
        ->middleware('permission:customers.create')
        ->middleware('feature:customers')
        ->name('customers.create');

    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:customers.create')
        ->middleware('feature:customers')
        ->name('customers.store');

    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.edit');

    Route::put('/customers/{id}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.update');

    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])
        ->middleware('permission:customers.delete')
        ->middleware('feature:customers')
        ->name('customers.destroy');

    Route::get('/customers/{id}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view')
        ->middleware('feature:customers')
        ->name('customers.show');

    Route::post(
        '/customers/{customerId}/contacts',
        [CustomerRelationshipController::class, 'storeContact']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.contacts.store');

    Route::put(
        '/customers/{customerId}/contacts/{contactId}',
        [CustomerRelationshipController::class, 'updateContact']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.contacts.update');

    Route::delete(
        '/customers/{customerId}/contacts/{contactId}',
        [CustomerRelationshipController::class, 'destroyContact']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.contacts.destroy');

    Route::post(
        '/customers/{customerId}/phones',
        [CustomerRelationshipController::class, 'storePhone']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.phones.store');

    Route::put(
        '/customers/{customerId}/phones/{phoneId}',
        [CustomerRelationshipController::class, 'updatePhone']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.phones.update');

    Route::delete(
        '/customers/{customerId}/phones/{phoneId}',
        [CustomerRelationshipController::class, 'destroyPhone']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.phones.destroy');

    Route::post(
        '/customers/{customerId}/emails',
        [CustomerRelationshipController::class, 'storeEmail']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.emails.store');

    Route::put(
        '/customers/{customerId}/emails/{emailId}',
        [CustomerRelationshipController::class, 'updateEmail']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.emails.update');

    Route::delete(
        '/customers/{customerId}/emails/{emailId}',
        [CustomerRelationshipController::class, 'destroyEmail']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.emails.destroy');

    Route::post(
        '/customers/{customerId}/addresses',
        [CustomerRelationshipController::class, 'storeAddress']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.addresses.store');

    Route::put(
        '/customers/{customerId}/addresses/{addressId}',
        [CustomerRelationshipController::class, 'updateAddress']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.addresses.update');

    Route::delete(
        '/customers/{customerId}/addresses/{addressId}',
        [CustomerRelationshipController::class, 'destroyAddress']
    )
        ->middleware('permission:customers.update')
        ->middleware('feature:customers')
        ->name('customers.addresses.destroy');
});
Route::middleware([
    'auth',
    'feature:imports',
])->group(function (): void {
    Route::get(
        '/imports',
        [ImportController::class, 'index']
    )
        ->middleware('permission:imports.view')
        ->name('imports.index');

    Route::get(
        '/imports/create',
        [ImportController::class, 'create']
    )
        ->middleware('permission:imports.create')
        ->name('imports.create');

    Route::post(
        '/imports',
        [ImportController::class, 'store']
    )
        ->middleware('permission:imports.create')
        ->name('imports.store');

    Route::get(
        '/imports/{id}/preview',
        [ImportController::class, 'preview']
    )
        ->middleware('permission:imports.create')
        ->name('imports.preview');

    Route::post(
        '/imports/{id}/dispatch',
        [ImportController::class, 'dispatch']
    )
        ->middleware('permission:imports.create')
        ->name('imports.dispatch');

    Route::get(
        '/imports/{id}',
        [ImportController::class, 'show']
    )
        ->middleware('permission:imports.view')
        ->name('imports.show');
});

Route::middleware([
    'auth',
    'feature:pipelines',
])->group(function (): void {
    Route::get(
        '/pipelines',
        [PipelineController::class, 'index']
    )
        ->middleware('permission:pipelines.view')
        ->name('pipelines.index');

    Route::get(
        '/pipelines/create',
        [PipelineController::class, 'create']
    )
        ->middleware('permission:pipelines.create')
        ->name('pipelines.create');

    Route::post(
        '/pipelines',
        [PipelineController::class, 'store']
    )
        ->middleware('permission:pipelines.create')
        ->name('pipelines.store');

    Route::get(
        '/pipelines/{id}/edit',
        [PipelineController::class, 'edit']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.edit');

    Route::put(
        '/pipelines/{id}',
        [PipelineController::class, 'update']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.update');

    Route::post(
        '/pipelines/{id}/default',
        [PipelineController::class, 'setDefault']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.default');

    Route::delete(
        '/pipelines/{id}',
        [PipelineController::class, 'destroy']
    )
        ->middleware('permission:pipelines.delete')
        ->name('pipelines.destroy');

    Route::post(
        '/pipelines/{pipelineId}/stages',
        [PipelineStageController::class, 'store']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.stages.store');

    Route::put(
        '/pipelines/{pipelineId}/stages/{stageId}',
        [PipelineStageController::class, 'update']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.stages.update');

    Route::delete(
        '/pipelines/{pipelineId}/stages/{stageId}',
        [PipelineStageController::class, 'destroy']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.stages.destroy');

    Route::post(
        '/pipelines/{pipelineId}/stages/reorder',
        [PipelineStageController::class, 'reorder']
    )
        ->middleware('permission:pipelines.update')
        ->name('pipelines.stages.reorder');
});
Route::middleware([
    'auth',
    'feature:opportunities',
])->group(function (): void {
    Route::get(
        '/opportunities',
        [OpportunityController::class, 'index']
    )
        ->middleware('permission:opportunities.view')
        ->name('opportunities.index');

    Route::get(
        '/opportunities/create',
        [OpportunityController::class, 'create']
    )
        ->middleware('permission:opportunities.create')
        ->name('opportunities.create');

    Route::post(
        '/opportunities',
        [OpportunityController::class, 'store']
    )
        ->middleware('permission:opportunities.create')
        ->name('opportunities.store');

    Route::get(
        '/opportunities/{id}/edit',
        [OpportunityController::class, 'edit']
    )
        ->middleware('permission:opportunities.update')
        ->name('opportunities.edit');

    Route::put(
        '/opportunities/{id}',
        [OpportunityController::class, 'update']
    )
        ->middleware('permission:opportunities.update')
        ->name('opportunities.update');

    Route::post(
        '/opportunities/{id}/stage',
        [OpportunityController::class, 'moveStage']
    )
        ->middleware('permission:opportunities.update')
        ->name('opportunities.stage');

    Route::delete(
        '/opportunities/{id}',
        [OpportunityController::class, 'destroy']
    )
        ->middleware('permission:opportunities.delete')
        ->name('opportunities.destroy');
});
Route::middleware([
    'auth',
    'feature:activities',
])->group(function (): void {
    Route::get(
        '/activities',
        [ActivityController::class, 'index']
    )
        ->middleware('permission:activities.view')
        ->name('activities.index');

    Route::get(
        '/activities/create',
        [ActivityController::class, 'create']
    )
        ->middleware('permission:activities.create')
        ->name('activities.create');

    Route::post(
        '/activities',
        [ActivityController::class, 'store']
    )
        ->middleware('permission:activities.create')
        ->name('activities.store');

    Route::get(
        '/activities/{id}/edit',
        [ActivityController::class, 'edit']
    )
        ->middleware('permission:activities.update')
        ->name('activities.edit');

    Route::put(
        '/activities/{id}',
        [ActivityController::class, 'update']
    )
        ->middleware('permission:activities.update')
        ->name('activities.update');

    Route::post(
        '/activities/{id}/complete',
        [ActivityController::class, 'complete']
    )
        ->middleware('permission:activities.update')
        ->name('activities.complete');

    Route::post(
        '/activities/{id}/reopen',
        [ActivityController::class, 'reopen']
    )
        ->middleware('permission:activities.update')
        ->name('activities.reopen');

    Route::post(
        '/activities/{id}/cancel',
        [ActivityController::class, 'cancel']
    )
        ->middleware('permission:activities.update')
        ->name('activities.cancel');

    Route::delete(
        '/activities/{id}',
        [ActivityController::class, 'destroy']
    )
        ->middleware('permission:activities.delete')
        ->name('activities.destroy');
});

Route::middleware('auth')->group(function (): void {
    Route::get(
        '/notifications',
        [NotificationController::class, 'index']
    )->name('notifications.index');

    Route::post(
        '/notifications/read-all',
        [NotificationController::class, 'readAll']
    )->name('notifications.read-all');

    Route::post(
        '/notifications/{id}/read',
        [NotificationController::class, 'read']
    )->name('notifications.read');
});

Route::middleware([
    'auth',
    'feature:catalog',
])->group(function (): void {
    Route::get(
        '/catalog',
        [CatalogController::class, 'index']
    )
        ->middleware('permission:catalog.view')
        ->name('catalog.index');

    Route::get(
        '/catalog/create',
        [CatalogController::class, 'create']
    )
        ->middleware('permission:catalog.create')
        ->name('catalog.create');

    Route::post(
        '/catalog',
        [CatalogController::class, 'store']
    )
        ->middleware('permission:catalog.create')
        ->name('catalog.store');

    Route::get(
        '/catalog/{id}/edit',
        [CatalogController::class, 'edit']
    )
        ->middleware('permission:catalog.update')
        ->name('catalog.edit');

    Route::put(
        '/catalog/{id}',
        [CatalogController::class, 'update']
    )
        ->middleware('permission:catalog.update')
        ->name('catalog.update');

    Route::delete(
        '/catalog/{id}',
        [CatalogController::class, 'destroy']
    )
        ->middleware('permission:catalog.delete')
        ->name('catalog.destroy');
});

Route::middleware([
    'auth',
    'feature:proposals',
])->group(function (): void {
    Route::get(
        '/proposals',
        [ProposalController::class, 'index']
    )
        ->middleware('permission:proposals.view')
        ->name('proposals.index');

    Route::get(
        '/proposals/create',
        [ProposalController::class, 'create']
    )
        ->middleware('permission:proposals.create')
        ->name('proposals.create');

    Route::post(
        '/proposals',
        [ProposalController::class, 'store']
    )
        ->middleware('permission:proposals.create')
        ->name('proposals.store');

    Route::post(
        '/proposals/{id}/send',
        [ProposalController::class, 'send']
    )
        ->middleware('permission:proposals.update')
        ->name('proposals.send');
    Route::get(
        '/proposals/{id}/pdf',
        [ProposalController::class, 'pdf']
    )
        ->middleware('permission:proposals.view')
        ->name('proposals.pdf');
    Route::get(
        '/proposals/{id}/edit',
        [ProposalController::class, 'edit']
    )
        ->middleware('permission:proposals.update')
        ->name('proposals.edit');

    Route::put(
        '/proposals/{id}',
        [ProposalController::class, 'update']
    )
        ->middleware('permission:proposals.update')
        ->name('proposals.update');

    Route::delete(
        '/proposals/{id}',
        [ProposalController::class, 'destroy']
    )
        ->middleware('permission:proposals.delete')
        ->name('proposals.destroy');
});

Route::middleware([
    'auth',
    'feature:sales',
])->group(function (): void {
    Route::get(
        '/sales',
        [SaleController::class, 'index']
    )
        ->middleware('permission:sales.view')
        ->name('sales.index');

    Route::get(
        '/opportunities/{opportunityId}/close-sale',
        [SaleController::class, 'create']
    )
        ->middleware('permission:sales.create')
        ->name('sales.create');

    Route::post(
        '/opportunities/{opportunityId}/close-sale',
        [SaleController::class, 'store']
    )
        ->middleware('permission:sales.create')
        ->name('sales.store');
});

Route::middleware([
    'auth',
    'feature:receivables',
])->group(function (): void {
    Route::get(
        '/receivables',
        [ReceivableController::class, 'index']
    )
        ->middleware('permission:receivables.view')
        ->name('receivables.index');

    Route::get(
        '/receivables/create',
        [ReceivableController::class, 'create']
    )
        ->middleware('permission:receivables.create')
        ->name('receivables.create');

    Route::post(
        '/receivables',
        [ReceivableController::class, 'store']
    )
        ->middleware('permission:receivables.create')
        ->name('receivables.store');

    Route::get(
        '/receivables/{id}/edit',
        [ReceivableController::class, 'edit']
    )
        ->middleware('permission:receivables.update')
        ->name('receivables.edit');

    Route::put(
        '/receivables/{id}',
        [ReceivableController::class, 'update']
    )
        ->middleware('permission:receivables.update')
        ->name('receivables.update');

    Route::post(
        '/receivables/{id}/pay',
        [ReceivableController::class, 'pay']
    )
        ->middleware('permission:receivables.update')
        ->name('receivables.pay');

    Route::post(
        '/receivables/{id}/cancel',
        [ReceivableController::class, 'cancel']
    )
        ->middleware('permission:receivables.update')
        ->name('receivables.cancel');
});

Route::middleware([
    'auth',
    'feature:charges',
])->group(function (): void {
    Route::get(
        '/charges',
        [ChargeController::class, 'index']
    )
        ->middleware('permission:charges.view')
        ->name('charges.index');

    Route::get(
        '/charges/create',
        [ChargeController::class, 'create']
    )
        ->middleware('permission:charges.create')
        ->name('charges.create');

    Route::post(
        '/charges',
        [ChargeController::class, 'store']
    )
        ->middleware('permission:charges.create')
        ->name('charges.store');

    Route::post(
        '/charges/{id}/sent',
        [ChargeController::class, 'markSent']
    )
        ->middleware('permission:charges.update')
        ->name('charges.sent');

    Route::post(
        '/charges/{id}/failed',
        [ChargeController::class, 'markFailed']
    )
        ->middleware('permission:charges.update')
        ->name('charges.failed');

    Route::post(
        '/charges/{id}/cancel',
        [ChargeController::class, 'cancel']
    )
        ->middleware('permission:charges.update')
        ->name('charges.cancel');
});

Route::get(
    '/financial-indicators',
    [FinancialIndicatorController::class, 'index']
)
    ->middleware([
        'auth',
        'feature:financial_indicators',
        'permission:financial_indicators.view',
    ])
    ->name('financial-indicators.index');

Route::middleware([
    'auth',
    'feature:email',
])->group(function (): void {
    Route::get(
        '/email',
        [EmailController::class, 'index']
    )
        ->middleware('permission:email.view')
        ->name('email.index');

    Route::get(
        '/email/create',
        [EmailController::class, 'create']
    )
        ->middleware('permission:email.create')
        ->name('email.create');

    Route::post(
        '/email',
        [EmailController::class, 'store']
    )
        ->middleware('permission:email.create')
        ->name('email.store');

    Route::post(
        '/email/{id}/send',
        [EmailController::class, 'send']
    )
        ->middleware('permission:email.send')
        ->name('email.send');

    Route::post(
        '/email/{id}/retry',
        [EmailController::class, 'retry']
    )
        ->middleware('permission:email.send')
        ->name('email.retry');

    Route::get(
        '/email/templates',
        [EmailTemplateController::class, 'index']
    )
        ->middleware('permission:email.templates')
        ->name('email.templates.index');

    Route::post(
        '/email/templates',
        [EmailTemplateController::class, 'store']
    )
        ->middleware('permission:email.templates')
        ->name('email.templates.store');

    Route::put(
        '/email/templates/{id}',
        [EmailTemplateController::class, 'update']
    )
        ->middleware('permission:email.templates')
        ->name('email.templates.update');

    Route::delete(
        '/email/templates/{id}',
        [EmailTemplateController::class, 'destroy']
    )
        ->middleware('permission:email.templates')
        ->name('email.templates.destroy');
});

Route::middleware([
    'auth',
    'feature:whatsapp',
])->group(function (): void {
    Route::get(
        '/whatsapp',
        [
            WhatsAppController::class,
            'index',
        ]
    )
        ->middleware(
            'permission:whatsapp.view'
        )
        ->name(
            'whatsapp.index'
        );

    Route::get(
        '/whatsapp/create',
        [
            WhatsAppController::class,
            'create',
        ]
    )
        ->middleware(
            'permission:whatsapp.create'
        )
        ->name(
            'whatsapp.create'
        );

    Route::post(
        '/whatsapp',
        [
            WhatsAppController::class,
            'store',
        ]
    )
        ->middleware(
            'permission:whatsapp.create'
        )
        ->name(
            'whatsapp.store'
        );

    Route::post(
        '/whatsapp/{id}/send',
        [
            WhatsAppController::class,
            'send',
        ]
    )
        ->middleware(
            'permission:whatsapp.send'
        )
        ->name(
            'whatsapp.send'
        );

    Route::get(
        '/whatsapp/templates',
        [
            WhatsAppTemplateController::class,
            'index',
        ]
    )
        ->middleware(
            'permission:whatsapp.templates'
        )
        ->name(
            'whatsapp.templates.index'
        );

    Route::post(
        '/whatsapp/templates',
        [
            WhatsAppTemplateController::class,
            'store',
        ]
    )
        ->middleware(
            'permission:whatsapp.templates'
        )
        ->name(
            'whatsapp.templates.store'
        );

    Route::put(
        '/whatsapp/templates/{id}',
        [
            WhatsAppTemplateController::class,
            'update',
        ]
    )
        ->middleware(
            'permission:whatsapp.templates'
        )
        ->name(
            'whatsapp.templates.update'
        );

    Route::delete(
        '/whatsapp/templates/{id}',
        [
            WhatsAppTemplateController::class,
            'destroy',
        ]
    )
        ->middleware(
            'permission:whatsapp.templates'
        )
        ->name(
            'whatsapp.templates.destroy'
        );
});
Route::middleware([
    'auth',
    'feature:inbox',
])->group(function (): void {
    Route::get(
        '/inbox',
        [
            ConversationController::class,
            'index',
        ]
    )
        ->middleware(
            'permission:inbox.view'
        )
        ->name(
            'inbox.index'
        );

    Route::get(
        '/inbox/{id}',
        [
            ConversationController::class,
            'show',
        ]
    )
        ->middleware(
            'permission:inbox.view'
        )
        ->name(
            'inbox.show'
        );

    Route::put(
        '/inbox/{id}/assignment',
        [
            ConversationController::class,
            'assign',
        ]
    )
        ->middleware(
            'permission:inbox.assign'
        )
        ->name(
            'inbox.assign'
        );

    Route::put(
        '/inbox/{id}/status',
        [
            ConversationController::class,
            'status',
        ]
    )
        ->middleware(
            'permission:inbox.manage'
        )
        ->name(
            'inbox.status'
        );
});
Route::post(
    '/ai/rewrite',
    [
        AiAssistantController::class,
        'rewrite',
    ]
)
    ->middleware([
        'auth',
        'feature:ai',
        'permission:ai.use',
    ])
    ->name('ai.rewrite');

    Route::post(
    '/webhooks/subscription/stripe',
    [
        StripeSubscriptionWebhookController::class,
        'handle',
    ]
)
    ->withoutMiddleware([
        ResolveTenant::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ])
    ->name(
        'webhooks.subscription.stripe'
    );

Route::post(
    '/webhooks/whatsapp/{tenantSlug}/{provider}',
    [
        WhatsAppWebhookController::class,
        'handle',
    ]
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name(
        'webhooks.whatsapp.handle'
    );


Route::post(
    '/webhooks/subscription/mercado-pago',
    [
        MercadoPagoSubscriptionWebhookController::class,
        'handle',
    ]
)
    ->withoutMiddleware([
        ResolveTenant::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ])
    ->name(
        'webhooks.subscription.mercado-pago'
    );
Route::post(
    '/webhooks/payment/{tenantSlug}/{provider}',
    [
        PaymentWebhookController::class,
        'handle',
    ]
)
    ->withoutMiddleware(
        ResolveTenant::class
    )
    ->name(
        'webhooks.payment.handle'
    );