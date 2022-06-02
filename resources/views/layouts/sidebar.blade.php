<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<nav id="sidebarMenu" class="col-md-2 col-lg-1 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <x-nav-item route="dashboard" icon="bi-house">
                {{ __('Dashboard') }}
            </x-nav-item>
            <x-nav-item route="listings" icon="bi-pin-angle">
                {{ __('Listings') }}
            </x-nav-item>
            <x-nav-item route="users.index" icon="bi-people">
                {{ __('Users') }}
            </x-nav-item>
            <x-nav-item route="subscriptions.index" icon="bi-bell">
                {{ __('Subscriptions') }}
            </x-nav-item>
        </ul>
    </div>
</nav>
