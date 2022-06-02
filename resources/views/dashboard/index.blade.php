<!--suppress HtmlUnknownTag -->
<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>
    <div class="dashboard">
        <div class="row mb-4">
            <div class="col">
                <div class="card dashboard-card border-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">{{ __('Total listings') }}</div>
                                <div class="h5 mb-0 fw-bold gray-dark placeholder-glow data-total"><span class="placeholder col-4">&nbsp;</span></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card dashboard-card border-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">{{ __('Listings added today') }}</div>
                                <div class="h5 mb-0 fw-bold gray-dark placeholder-glow data-total_created"><span class="placeholder col-4">&nbsp;</span></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card dashboard-card border-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">{{ __('Listings updated today') }}</div>
                                <div class="h5 mb-0 fw-bold gray-dark placeholder-glow data-total_updated"><span class="placeholder col-4">&nbsp;</span></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card dashboard-card border-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">{{ __('Total active listings') }}</div>
                                <div class="h5 mb-0 fw-bold gray-dark placeholder-glow data-total_active"><span class="placeholder col-4">&nbsp;</span></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-3">
                <div class="card dashboard-card border-secondary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row g-0 align-items-center">
                            <div class="col me-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">{{ __('Pending jobs') }}</div>
                                <div class="h5 mb-0 fw-bold gray-dark placeholder-glow data-pending_jobs"><span class="placeholder col-4">&nbsp;</span></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
