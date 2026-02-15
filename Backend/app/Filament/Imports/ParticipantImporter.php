<?php

namespace App\Filament\Imports;

use App\Models\City;
use App\Models\Country;
use App\Models\Nationality;
use App\Models\Participant;
use App\Notifications\ParticipantAccountImported;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class ParticipantImporter extends Importer
{
    protected static ?string $model = Participant::class;

    public static function getColumns(): array
    {
        $nationalities = Nationality::all()->pluck('name', 'id')->values()->toArray();
        $countries = Country::all()->pluck('name', 'id')->values()->toArray();
        $cities = City::all()->pluck('name', 'id')->values()->toArray();

        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['bail', 'required', 'email', 'max:255', 'unique:participants,email']),
            ImportColumn::make('phone')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'unique:participants,phone']),
            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(['required'])
                ->examples([
                    'male',
                    'female',
                ]),
            ImportColumn::make('date_of_birth')
                ->requiredMapping()
                ->label('Date of Birth')
                ->examples([
                    '12 Mar, 2001',
                    '01 Jan, 1995',
                    '25 Dec, 2000',
                ])
                ->castStateUsing(function ($state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    try {
                        // Try to parse format "d M, Y" (e.g. "12 Mar, 2001")
                        return Carbon::createFromFormat('d M, Y', $state)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If it fails, pass raw value to trigger validation
                        return $state;
                    }
                }),

            ImportColumn::make('nationality_id')
                ->label('Nationality')
                ->examples($nationalities)
                ->requiredMapping()
                ->rules(['required', 'exists:nationalities,id'])
                ->castStateUsing(function ($state) {
                    if (!$state) return null;

                    $id = Nationality::where('name->en', trim($state))->value('id');

                    return $id ?? trim($state);
                }),

            ImportColumn::make('country_id')
                ->label('Country')
                ->examples($countries)
                ->requiredMapping()
                ->rules(['required', 'max:255', 'exists:countries,id'])
                ->castStateUsing(function ($state) {
                    if (!$state) return null;

                    $id = Country::where('name->en', trim($state))->value('id');

                    return $id ?? trim($state);
                }),

            ImportColumn::make('residence_city_id')
                ->label('Residence City')
                ->examples($cities)
                ->requiredMapping()
                ->rules(['max:255', 'exists:cities,id'])
                ->castStateUsing(function ($state) {
                    if (!$state) return null;

                    $id = City::where('name->en', trim($state))->value('id');

                    return $id ?? trim($state);
                }),

            ImportColumn::make('educational_background')
                ->requiredMapping()
                ->rules(['required'])
                ->examples([
                    'high_school',
                    'diploma',
                    'bachelor',
                    'master',
                    'phd',
                ]),
            ImportColumn::make('current_role')
                ->requiredMapping()
                ->rules(['required'])
                ->examples([
                    'high_school_student',
                    'university_student',
                    'recently_graduated',
                    'private_sector_employee',
                    'government_sector_employee',
                    'non_profit_sector_employee',
                    'freelancer',
                    'unemployed',
                ]),
            ImportColumn::make('place_of_work_study')
                ->rules(['max:255']),
            ImportColumn::make('years_of_experience')
                ->requiredMapping()
                ->rules(['required'])
                ->examples([
                    'less_than_one',
                    'one_to_three',
                    'three_to_five',
                    'five_to_ten',
                    'more_than_ten',
                    'no_experience'
                ]),
            ImportColumn::make('experience_or_skills'),
            ImportColumn::make('key_achievements'),
        ];
    }

    public function resolveRecord(): ?Participant
    {
        // return Participant::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Participant();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your participant import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported . ';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import . ';
        }

        return $body;
    }

    public function getValidationRules(): array
    {
        return [
            'name' => ['required', 'regex:/^[\p{L} ]+$/u', 'min:2'],
            'email' => ['required', 'email', 'max:255', 'unique:participants,email'],
            'phone' => ['required', 'numeric', 'unique:participants,phone'],
            'gender' => ['required', 'in:male,female,Male,Female'],
            'date_of_birth' => ['required', 'bail', 'date_format:Y-m-d', 'before_or_equal:' . now()->subYears(10)->format('Y-m-d')],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'country_id' => ['required', 'exists:countries,id'],
            'residence_city_id' => ['required', 'exists:cities,id'],
            'educational_background' => ['required', 'in:high_school,diploma,bachelor,master,phd'],
            'current_role' => ['required', 'in:high_school_student,university_student,recently_graduated,private_sector_employee,government_sector_employee,non_profit_sector_employee,freelancer,unemployed'],
            'place_of_work_study' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['required', 'in:less_than_one,one_to_three,three_to_five,five_to_ten,more_than_ten,no_experience'],
            'experience_or_skills' => ['nullable', 'string', 'max:300'],
            'key_achievements' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'required' => __('validation.required'),
            'string' => __('validation.string'),
            'max' => __('validation.max', ['max' => ':max']),
            'regex' => __('validation.regex'),

            'name.regex' => __('validation.name.regex'),
            'name.min' => __('validation.name.min'),

            'email' => __('validation.email.format'),
            'unique' => __('validation.email.unique'),

            'phone.regex' => __('validation.phone.regex'),
            'phone.unique' => __('validation.phone.unique'),

            'date_of_birth.date_format' => __('validation.date_of_birth.format'),
            'date_of_birth.before_or_equal' => __('validation.date_of_birth.age'),
        ];
    }

    protected function afterCreate(): void
    {
        $password = Str::random(12);

        $this->record->password = $password;
        $this->record->save();

        $this->record->markEmailAsVerified();

        $this->record->notify(new ParticipantAccountImported($this->record, $password));
    }

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return now()->addMinute();
    }
}
