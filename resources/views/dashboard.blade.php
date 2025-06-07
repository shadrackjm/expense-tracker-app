<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
          {{-- Stats Overview --}}
        <livewire:stats-overview />

        {{-- Recent Activity and Budgets --}}
        <livewire:recent-activity />
    </div>
</x-layouts.app>
