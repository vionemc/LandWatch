<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-2 col-lg-1 me-0 px-3" href="#">{{ config('app.name') }}</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link nav-link">{{ __('Sign out') }}</button>
            </form>
        </li>
    </ul>
</header>
