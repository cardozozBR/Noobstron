<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionSync;
use App\Services\SubscriptionService;
use App\Services\TenantContext;
use App\Services\TrialService;
use App\Support\SubscriptionPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SelfServiceRegistrationController extends Controller
{
    public function store(
        Request $request,
        RolePermissionSync $permissionSync,
        TrialService $trialService,
        SubscriptionService $subscriptionService,
        TenantContext $tenantContext,
    ): RedirectResponse {
        $data = $request->validate([
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                static function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail,
                ): void {
                    if (
                        !is_string($value) ||
                        filter_var(
                            $value,
                            FILTER_VALIDATE_EMAIL
                        ) === false
                    ) {
                        $fail(
                            __('validation.email', [
                                'attribute' =>
                                    __('registration.fields.email'),
                            ])
                        );
                    }
                },
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'country_code' => [
                'required',
                Rule::in(
                    array_keys(
                        config('global.countries', [])
                    )
                ),
            ],

            'locale' => [
                'required',
                Rule::in(
                    array_keys(
                        config('global.locales', [])
                    )
                ),
            ],

            'plan_code' => [
                'required',
                Rule::in([
                    'start',
                    'pro',
                    'business',
                ]),
                Rule::exists('plans', 'code')
                    ->where(
                        static fn ($query) =>
                            $query->where(
                                'active',
                                true
                            )
                    ),
            ],
        ]);

        $slug = Str::slug($data['company_name']);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'company_name' =>
                    'Não foi possível gerar o identificador da empresa.',
            ]);
        }

        if (
            Tenant::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'company_name' =>
                    'Já existe uma empresa com este identificador.',
            ]);
        }

        $plan = Plan::query()
            ->where('code', $data['plan_code'])
            ->firstOrFail();

        $registration = DB::transaction(
            function () use (
                $data,
                $slug,
                $plan,
                $permissionSync,
                $trialService,
                $subscriptionService,
                $tenantContext,
            ): array {
                $tenant = Tenant::query()->create([
                    'name' => $data['company_name'],
                    'slug' => $slug,
                    'status' => 'active',
                    'country_code' => $data['country_code'],
                    'locale' => $data['locale'],
                ]);

                $tenantContext->set($tenant);

                $user = User::query()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'admin',
                ]);

                $permissionSync->sync($user);

                $trialService->start($tenant);

                $periodStart = CarbonImmutable::now('UTC');

                $subscriptionService->create(
                    $tenant,
                    $plan,
                    new SubscriptionPeriod(
                        $periodStart,
                        $periodStart->addMonthNoOverflow(),
                    ),
                );

                return [
                    'tenant' => $tenant->refresh(),
                    'user' => $user,
                ];
            }
        );

        /** @var Tenant $tenant */
        $tenant = $registration['tenant'];

        /** @var User $user */
        $user = $registration['user'];

        $user->sendEmailVerificationNotification();

        $appUrl = (string) config('app.url');

        $scheme = parse_url(
            $appUrl,
            PHP_URL_SCHEME
        ) ?: 'http';

        $host = parse_url(
            $appUrl,
            PHP_URL_HOST
        );

        $port = parse_url(
            $appUrl,
            PHP_URL_PORT
        );

        // APP_URL intentionally stays environment-centric, but local Docker
        // requests commonly reach Laravel through a published non-default
        // port (for example localhost:8000). When APP_URL has no explicit
        // port and the request is for the same public host, preserve the
        // request port in the tenant redirect.
        if (
            $port === null &&
            strcasecmp($request->getHost(), (string) $host) === 0
        ) {
            $requestPort = $request->getPort();
            $defaultPort = $scheme === 'https' ? 443 : 80;

            if ($requestPort !== $defaultPort) {
                $port = $requestPort;
            }
        }

        if (!$host) {
            throw new \RuntimeException(
                'Application URL must contain a valid host.'
            );
        }

        $tenantHost = $tenant->slug . '.' . $host;

        $tenantUrl = $scheme
            . '://'
            . $tenantHost
            . ($port ? ':' . $port : '')
            . '/login';

        return redirect()->to(
            $tenantUrl
        );
    }
}