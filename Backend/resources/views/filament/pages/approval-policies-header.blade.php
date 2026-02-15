<div class="fi-header">
    <div class="fi-header-heading">
        <h1 class="fi-header-title text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            {{ __('approval_workflow.approval_policies') }}
        </h1>
        <p class="fi-header-subtitle text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ __('approval_workflow.configure_approval_workflows') }}
        </p>
    </div>
    
    <div class="fi-header-actions">
        <a href="{{ route('filament.admin.resources.approval-workflows.create') }}" 
           class="fi-btn fi-btn-color-primary fi-btn-size-sm fi-btn-style-filled">
            <span class="fi-btn-label">{{ __('approval_workflow.new_policy') }}</span>
        </a>
    </div>
</div>
