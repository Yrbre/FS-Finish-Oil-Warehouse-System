{{-- resources/views/partials/sidebar.blade.php --}}
<nav class="sidebar">
    <ul class="nav flex-column">
        @foreach (config('menu') as $menu)
            @php
                $canSeeParent = is_null($menu['permission']) || auth()->user()->can($menu['permission']);
            @endphp

            @if ($canSeeParent)
                @if (isset($menu['children']))
                    @php
                        $visibleChildren = collect($menu['children'])->filter(function ($child) {
                            return is_null($child['permission']) || auth()->user()->can($child['permission']);
                        });
                    @endphp

                    @if ($visibleChildren->isNotEmpty())
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="{{ $menu['icon'] }}"></i> {{ $menu['label'] }}
                            </span>
                            <ul class="nav flex-column ms-3">
                                @foreach ($visibleChildren as $child)
                                    <li class="nav-item">
                                        <a href="{{ route($child['route']) }}"
                                            class="nav-link {{ request()->routeIs($child['route']) ? 'active' : '' }}">
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @else
                    <li class="nav-item">
                        <a href="{{ route($menu['route']) }}"
                            class="nav-link {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                            <i class="{{ $menu['icon'] }}"></i> {{ $menu['label'] }}
                        </a>
                    </li>
                @endif
            @endif
        @endforeach
    </ul>
</nav>
