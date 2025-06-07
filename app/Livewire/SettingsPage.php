<?php

namespace App\Livewire;

use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting; // Ensure the Setting model is imported

class SettingsPage extends Component
{
    // Currency settings
    public $currency_code;
    public $currency_symbol;

    // Email notification settings
    public $email_notifications_enabled;
    public $email_budget_alerts;
    public $email_recurring_reminders;

    // General settings
    public $timezone; // e.g., 'Africa/Dar_es_Salaam'
    public $first_day_of_week; // 0 for Sunday, 1 for Monday
    public $app_name; // Added new property for app name

    // Mail settings
    public $mail_mailer;
    public $mail_host;
    public $mail_port;
    public $mail_username;
    public $mail_password;
    public $mail_from_address;
    public $mail_from_name;

    /**
     * Mount lifecycle hook to load existing settings.
     * This runs once when the component is first initialized.
     */
    public function mount()
    {
        if (Auth::check()) {
            // Populate public properties using the Setting::get() method
            $this->currency_code = Setting::get('currency_code', 'TZS');
            $this->currency_symbol = Setting::get('currency_symbol', 'TSh');
            $this->email_notifications_enabled = (bool) Setting::get('email_notifications_enabled', true);
            $this->email_budget_alerts = (bool) Setting::get('email_budget_alerts', true);
            $this->email_recurring_reminders = (bool) Setting::get('email_recurring_reminders', true);
            $this->timezone = Setting::get('timezone', config('app.timezone', 'UTC'));
            $this->first_day_of_week = (int) Setting::get('first_day_of_week', 1); // Default to Monday (1)
            $this->app_name = Setting::get('app_name', config('app.name', 'My Application')); // Load app name

            // Load mail settings, prioritizing database settings over config values
            $this->mail_mailer = Setting::get('mail_mailer', config('mail.mailers.smtp.transport', 'smtp'));
            $this->mail_host = Setting::get('mail_host', config('mail.mailers.smtp.host', 'smtp.mailtrap.io'));
            $this->mail_port = (int) Setting::get('mail_port', config('mail.mailers.smtp.port', 2525));
            $this->mail_username = Setting::get('mail_username', config('mail.mailers.smtp.username', ''));
            $this->mail_password = Setting::get('mail_password', config('mail.mailers.smtp.password', ''));
            $this->mail_from_address = Setting::get('mail_from_address', config('mail.from.address', ''));
            $this->mail_from_name = Setting::get('mail_from_name', config('mail.from.name', 'My Application'));

        } else {
            // Set default values if not authenticated
            $this->setDefaultSettings();
            session()->flash('error', 'You must be logged in to manage settings.');
            // Consider redirecting to login page if this component requires authentication
            // return redirect()->route('login');
        }
    }

    /**
     * Set default values for properties if user is not authenticated or settings are missing.
     */
    private function setDefaultSettings()
    {
        $this->currency_code = 'TZS';
        $this->currency_symbol = 'TSh';
        $this->email_notifications_enabled = true;
        $this->email_budget_alerts = true;
        $this->email_recurring_reminders = true;
        $this->timezone = config('app.timezone', 'UTC');
        $this->first_day_of_week = 1;
        $this->app_name = config('app.name', 'My Application');

        $this->mail_mailer = config('mail.mailers.smtp.transport', 'smtp');
        $this->mail_host = config('mail.mailers.smtp.host', 'smtp.mailtrap.io');
        $this->mail_port = config('mail.mailers.smtp.port', 2525);
        $this->mail_username = config('mail.mailers.smtp.username', '');
        $this->mail_password = config('mail.mailers.smtp.password', '');
        $this->mail_from_address = config('mail.from.address', '');
        $this->mail_from_name = config('mail.from.name', 'My Application');
    }

    /**
     * Validation rules for the settings.
     */
    protected function rules(): array
    {
        return [
            'currency_code' => ['required', 'string', 'max:3', 'min:3'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'email_notifications_enabled' => ['boolean'],
            'email_budget_alerts' => ['boolean'],
            'email_recurring_reminders' => ['boolean'],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())], // Validate against a list of valid timezones
            'first_day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'app_name' => ['required', 'string', 'max:255'], // Validation for app name

            // Validation rules for mail settings
            'mail_mailer' => ['required', 'string', Rule::in(['smtp', 'sendmail', 'mailgun', 'ses', 'log', 'array'])], // Added 'log' and 'array' common mailers
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Save the updated settings to the database.
     */
    public function saveSettings()
    {
        // Ensure user is authenticated before saving
        if (!Auth::check()) {
            session()->flash('error', 'Authentication required to save settings.');
            return;
        }

        $this->validate(); // Validate the inputs

        // Store settings using Setting::set()
        Setting::set('currency_code', $this->currency_code, 'currency');
        Setting::set('currency_symbol', $this->currency_symbol, 'currency');
        Setting::set('email_notifications_enabled', $this->email_notifications_enabled, 'email');
        Setting::set('email_budget_alerts', $this->email_budget_alerts, 'email');
        Setting::set('email_recurring_reminders', $this->email_recurring_reminders, 'email');
        Setting::set('timezone', $this->timezone, 'general');
        Setting::set('first_day_of_week', $this->first_day_of_week, 'general');
        Setting::set('app_name', $this->app_name, 'general'); // Save app name

        // Save mail settings
        Setting::set('mail_mailer', $this->mail_mailer, 'mail');
        Setting::set('mail_host', $this->mail_host, 'mail');
        Setting::set('mail_port', $this->mail_port, 'mail');
        Setting::set('mail_username', $this->mail_username, 'mail');
        Setting::set('mail_password', $this->mail_password, 'mail');
        Setting::set('mail_from_address', $this->mail_from_address, 'mail');
        Setting::set('mail_from_name', $this->mail_from_name, 'mail');

        Toaster::success('Settings saved successfully!');
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        // Get available timezones for the dropdown
        $timezones = timezone_identifiers_list();

        return view('livewire.settings-page', [
            'timezones' => $timezones,
        ]);
    }
}
