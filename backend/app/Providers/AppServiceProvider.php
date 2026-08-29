<?php

namespace App\Providers;

use App\Enums\AutomationActionType;
use App\Services\AiProviderRegistry;
use App\Services\AssignResponsibleActionHandler;
use App\Services\AutomationActionExecutor;
use App\Services\ChangeStageActionHandler;
use App\Services\CreateNotificationActionHandler;
use App\Services\CreateTaskActionHandler;
use App\Services\MercadoPagoSubscriptionProvider;
use App\Services\MetaWhatsAppProvider;
use App\Services\MetaWhatsAppWebhookNormalizer;
use App\Services\MetaWhatsAppWebhookVerifier;
use App\Services\OpenAiProvider;
use App\Services\PaymentProviderRegistry;
use App\Services\SendEmailActionHandler;
use App\Services\SendWebhookActionHandler;
use App\Services\SendWhatsAppActionHandler;
use App\Services\StripeSubscriptionProvider;
use App\Services\SubscriptionPaymentProviderRegistry;
use App\Services\TenantContext;
use App\Services\WhatsAppProviderRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            PaymentProviderRegistry::class
        );

        $this->app->singleton(
            SubscriptionPaymentProviderRegistry::class,
            function ($app): SubscriptionPaymentProviderRegistry {
                $registry =
                    new SubscriptionPaymentProviderRegistry;

                $registry->register(
                    $app->make(
                        MercadoPagoSubscriptionProvider::class
                    )
                );

                $registry->register(
                    $app->make(
                        StripeSubscriptionProvider::class
                    )
                );

                return $registry;
            }
        );

        $this->app->singleton(
            AiProviderRegistry::class,
            function ($app): AiProviderRegistry {
                $registry =
                    new AiProviderRegistry;

                $registry->register(
                    $app->make(
                        OpenAiProvider::class
                    )
                );

                return $registry;
            }
        );

        $this->app->scoped(
            TenantContext::class,
            function () {
                return new TenantContext;
            }
        );

        $this->app->scoped(
            WhatsAppProviderRegistry::class,
            function ($app): WhatsAppProviderRegistry {
                $registry =
                    new WhatsAppProviderRegistry;

                $registry->register(
                    $app->make(
                        MetaWhatsAppProvider::class
                    )
                );

                return $registry;
            }
        );

        $this->app->scoped(
            'whatsapp.webhook.verifier.meta',
            function ($app): MetaWhatsAppWebhookVerifier {
                return $app->make(
                    MetaWhatsAppWebhookVerifier::class
                );
            }
        );

        $this->app->scoped(
            'whatsapp.webhook.normalizer.meta',
            function ($app): MetaWhatsAppWebhookNormalizer {
                return $app->make(
                    MetaWhatsAppWebhookNormalizer::class
                );
            }
        );
        $this->app->scoped(
            AutomationActionExecutor::class,
            function ($app) {
                $executor =
                    new AutomationActionExecutor;

                $executor->register(
                    AutomationActionType::CREATE_TASK,
                    $app->make(
                        CreateTaskActionHandler::class
                    )
                );

                $executor->register(
                    AutomationActionType::SEND_EMAIL,
                    $app->make(
                        SendEmailActionHandler::class
                    )
                );

                $executor->register(
                    AutomationActionType::SEND_WHATSAPP,
                    $app->make(
                        SendWhatsAppActionHandler::class
                    )
                );

                $executor->register(
                    AutomationActionType::CHANGE_STAGE,
                    $app->make(
                        ChangeStageActionHandler::class
                    )
                );

                $executor->register(
                    AutomationActionType::ASSIGN_RESPONSIBLE,
                    $app->make(
                        AssignResponsibleActionHandler::class
                    )
                );

                $executor->register(
                    AutomationActionType::CREATE_NOTIFICATION,
                    $app->make(
                        CreateNotificationActionHandler::class
                    )
                );

                $executor->register(
                    AutomationActionType::SEND_WEBHOOK,
                    $app->make(
                        SendWebhookActionHandler::class
                    )
                );

                return $executor;
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
