@if (!empty($breadcrumbs))
    <nav
        class="platform-breadcrumbs"
        aria-label="{{ __('platform.breadcrumbs.aria_label') }}"
    >
        <ol class="platform-breadcrumbs__list">
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="platform-breadcrumbs__item">
                    @if (
                        ! $loop->last
                        && filled($breadcrumb['url'] ?? null)
                    )
                        <a
                            class="platform-breadcrumbs__link"
                            href="{{ $breadcrumb['url'] }}"
                        >
                            {{ $breadcrumb['label'] }}
                        </a>
                    @else
                        <span
                            class="platform-breadcrumbs__current"
                            @if ($loop->last)
                                aria-current="page"
                            @endif
                        >
                            {{ $breadcrumb['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
