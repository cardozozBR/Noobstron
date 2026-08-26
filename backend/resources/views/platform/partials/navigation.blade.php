<header class="platform-header">
    <div class="platform-header__inner">
        <a
            class="platform-brand"
            href="{{ route('platform.dashboard') }}"
        >
            {{ __('platform.brand') }}
        </a>

        <nav
            class="platform-navigation"
            aria-label="{{ __('platform.nav.aria_label') }}"
        >
            <a
                class="platform-navigation__link{{ request()->routeIs('platform.dashboard') ? ' is-active' : '' }}"
                href="{{ route('platform.dashboard') }}"
            >
                {{ __('platform.nav.dashboard') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.tenants.*') ? ' is-active' : '' }}"
                href="{{ route('platform.tenants.index') }}"
            >
                {{ __('platform.nav.tenants') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.contacts.*') ? ' is-active' : '' }}"
                href="{{ route('platform.contacts.index') }}"
            >
                {{ __('platform.nav.contacts') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.health') ? ' is-active' : '' }}"
                href="{{ route('platform.health') }}"
            >
                {{ __('platform.nav.health') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.jobs*') ? ' is-active' : '' }}"
                href="{{ route('platform.jobs') }}"
            >
                {{ __('platform.nav.jobs') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.webhooks*') ? ' is-active' : '' }}"
                href="{{ route('platform.webhooks') }}"
            >
                {{ __('platform.nav.webhooks') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.email-failures*') ? ' is-active' : '' }}"
                href="{{ route('platform.email-failures') }}"
            >
                {{ __('platform.nav.email_failures') }}
            </a>

            <a
                class="platform-navigation__link{{ request()->routeIs('platform.whatsapp-failures*') ? ' is-active' : '' }}"
                href="{{ route('platform.whatsapp-failures') }}"
            >
                {{ __('platform.nav.whatsapp_failures') }}
            </a>
        </nav>

        <form
            method="POST"
            action="{{ route('platform.logout') }}"
            class="platform-navigation__logout"
        >
            @csrf

            <button
                class="logout-button"
                type="submit"
            >
                {{ __('platform.logout') }}
            </button>
        </form>
    </div>
</header>
