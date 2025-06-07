<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6 md:p-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-neutral-200 mb-6">Application Settings</h2>

        <!-- Success/Error Message Display -->
        {{-- @if (session()->has('success'))
            <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 dark:bg-green-800/30 dark:border-green-900 dark:text-green-500" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 dark:bg-red-800/30 dark:border-red-900 dark:text-red-500" role="alert">
                {{ session('error') }}
            </div>
        @endif --}}

        <form wire:submit.prevent="saveSettings">
            <!-- Currency Settings Section -->
            <div class="mb-8 pb-8 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">Currency Settings</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="currency_code" class="block text-sm font-medium mb-2 dark:text-white">Currency Code (e.g., USD, TZS)</label>
                        <input type="text" id="currency_code" wire:model.defer="currency_code" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="USD">
                        @error('currency_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="currency_symbol" class="block text-sm font-medium mb-2 dark:text-white">Currency Symbol (e.g., $, TSh)</label>
                        <input type="text" id="currency_symbol" wire:model.defer="currency_symbol" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="$">
                        @error('currency_symbol') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Email Notification Preferences Section -->
            <div class="mb-8 pb-8 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">Email Notification Preferences</h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="email_notifications_enabled" wire:model.defer="email_notifications_enabled" class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800">
                        <label for="email_notifications_enabled" class="text-sm text-gray-500 ms-3 dark:text-neutral-400">Enable all email notifications</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="email_budget_alerts" wire:model.defer="email_budget_alerts" class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800">
                        <label for="email_budget_alerts" class="text-sm text-gray-500 ms-3 dark:text-neutral-400">Receive budget overrun alerts</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="email_recurring_reminders" wire:model.defer="email_recurring_reminders" class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800">
                        <label for="email_recurring_reminders" class="text-sm text-gray-500 ms-3 dark:text-neutral-400">Receive reminders for recurring transactions</label>
                    </div>
                </div>
                @error('email_notifications_enabled') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                @error('email_budget_alerts') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                @error('email_recurring_reminders') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- General Settings Section -->
            <div class="mb-8 pb-8 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">General Settings</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="app_name" class="block text-sm font-medium mb-2 dark:text-white">Application Name</label>
                        <input type="text" id="app_name" wire:model.defer="app_name" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="My Expense Tracker">
                        @error('app_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="timezone" class="block text-sm font-medium mb-2 dark:text-white">Timezone</label>
                        <select id="timezone" wire:model.defer="timezone" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}">{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="first_day_of_week" class="block text-sm font-medium mb-2 dark:text-white">First Day of Week</label>
                        <select id="first_day_of_week" wire:model.defer="first_day_of_week" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:checked:bg-blue-500 dark:checked:border-blue-500 dark:focus:ring-offset-gray-800">
                            <option value="0">Sunday</option>
                            <option value="1">Monday</option>
                            <option value="6">Saturday</option>
                        </select>
                        @error('first_day_of_week') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Email Configuration Section -->
            <div class="mb-8 pb-8 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">Email Configuration</h3>
                <p class="text-sm text-gray-600 dark:text-neutral-400 mb-4">These settings configure how your application sends emails. This typically requires an external mail service provider.</p>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="mail_mailer" class="block text-sm font-medium mb-2 dark:text-white">Mail Mailer</label>
                        <select id="mail_mailer" wire:model.defer="mail_mailer" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="mailgun">Mailgun</option>
                            <option value="ses">SES</option>
                            <option value="log">Log (for debugging)</option>
                            <option value="array">Array (for testing)</option>
                        </select>
                        @error('mail_mailer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="mail_host" class="block text-sm font-medium mb-2 dark:text-white">Mail Host</label>
                        <input type="text" id="mail_host" wire:model.defer="mail_host" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="smtp.example.com">
                        @error('mail_host') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="mail_port" class="block text-sm font-medium mb-2 dark:text-white">Mail Port</label>
                        <input type="number" id="mail_port" wire:model.defer="mail_port" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="587">
                        @error('mail_port') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="mail_username" class="block text-sm font-medium mb-2 dark:text-white">Mail Username</label>
                        <input type="text" id="mail_username" wire:model.defer="mail_username" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="user@example.com">
                        @error('mail_username') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="mail_password" class="block text-sm font-medium mb-2 dark:text-white">Mail Password</label>
                        <input type="password" id="mail_password" wire:model.defer="mail_password" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="********">
                        @error('mail_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="mail_from_address" class="block text-sm font-medium mb-2 dark:text-white">Mail From Address</label>
                        <input type="email" id="mail_from_address" wire:model.defer="mail_from_address" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="no-reply@example.com">
                        @error('mail_from_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="mail_from_name" class="block text-sm font-medium mb-2 dark:text-white">Mail From Name</label>
                        <input type="text" id="mail_from_name" wire:model.defer="mail_from_name" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400" placeholder="My Expense App">
                        @error('mail_from_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
