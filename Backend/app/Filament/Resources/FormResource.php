<?php

namespace App\Filament\Resources;

use App\Filament\Exports\FormExporter;
use App\Filament\Resources\FormResource\Pages;
use App\Filament\Widgets\FormStatsOverview;
use App\Models\Program;
use App\Models\FormField;
use App\Models\UserProgram;
use Closure;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Form;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Carbon\Carbon;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Forms';

    protected static ?string $navigationGroup = 'Forms & Content';

    protected static ?int $navigationSort = 1;


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program')
                    ->schema([
                        // make currentProgramId() method in Form model is selected

                        Forms\Components\Select::make('program_id')
                            ->label('Program')
                            ->options(function () {
                                $user = auth()->user();

                                if ($user->isSuperAdmin()) {
                                    return Program::pluck('title', 'id')->toArray();
                                }

                                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                                    ->pluck('program_id')
                                    ->toArray();

                                return Program::whereIn('id', $supervisorPrograms)->pluck('title', 'id')->toArray();
                            })
                            ->default(currentProgramId())
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->helperText('Select a program / اختر البرنامج')
                            ->validationMessages([
                                'required' => 'Please select a program.',
                            ]),

                    ]),

                Forms\Components\Section::make('Form Attributes')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Name')
                                ->helperText('Enter the name in English')
                                ->required(),
                            Forms\Components\TextInput::make('name.ar')
                                ->label('الاسم')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->helperText('أدخل الاسم باللغة العربية')
                                ->required(),
                        ])->columns(),



                        Forms\Components\Group::make([
                            Forms\Components\Textarea::make('description.en')
                                ->label('Description')
                                ->helperText('Optional description in English')
                                ->nullable(),
                            Forms\Components\Textarea::make('description.ar')
                                ->label('الوصف')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->helperText('وصف اختياري باللغة العربية')
                                ->nullable(),
                        ])->columns(),

                        Forms\Components\Select::make('type')
                            ->label('Form Type')
                            ->helperText('Select the form type / اختر نوع النموذج')
                            ->options(function (callable $get) {
                                $programId = $get('program_id');
                                $allTypes = \App\Models\Form::getAvailableFormTypes();
                                $alwaysAllowed = ['evaluation','project'];

                                if (empty($programId)) {
                                    return $allTypes;
                                }

                                $existingTypes = \App\Models\Form::where('program_id', $programId)
                                    ->pluck('type')
                                    ->toArray();

                                $filteredExistingTypes = array_diff($existingTypes, $alwaysAllowed);

                                $filteredTypes = array_diff_key($allTypes, array_flip($filteredExistingTypes));

                                $currentType = $get('type');
                                if ($currentType && !array_key_exists($currentType, $filteredTypes)) {
                                    $filteredTypes[$currentType] = $allTypes[$currentType] ?? $currentType;
                                }

                                return $filteredTypes;
                            })
                            ->default(function (callable $get) {
                                return old('type');
                            })
                            ->afterStateHydrated(function ($state, callable $set) {
                                if (is_null($state)) {
                                    $set('type', old('type'));
                                }
                            })
                            ->required()
                            ->reactive()
                            ->live()
                            ->validationMessages([
                                'required' => 'Please select a form type.',
                            ]),


                        Forms\Components\Placeholder::make('registration_fields_info')
                            ->hiddenLabel()
                            ->content('The following fields will be automatically added: Participant Name, Participant Email')
                            ->columnSpanFull()
                            ->visible(fn($get) => $get('type') == 'registration'),

                        Forms\Components\Placeholder::make('projects_fields_info')
                            ->hiddenLabel()
                            ->content('The following fields will be automatically added: Project Name')
                            ->columnSpanFull()
                            ->visible(fn($get) => $get('type') == 'project'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->helperText('Toggle to publish or unpublish / فعّل للنشر أو إلغاء النشر')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Form Fields')
                    ->visible(fn($get) => $get('type') !== 'evaluation')
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        Forms\Components\Repeater::make('fields')
                            ->label('Fields')
                            ->relationship('fields')
                            ->reorderable()
                            ->orderColumn('sort')
                            ->collapsible()
                            ->cloneable()
                            ->reactive()
                            ->deletable(function ($get) {
                                $fields = $get('fields');
                                if (!$fields) {
                                    return true;
                                }
                                if (count($fields) === 1) {
                                    return false;
                                }
                                $firstField = reset($fields);
                                $lastField = end($fields);
                                $labelEnLastField = $lastField['label']['en'] ?? null;
                                if ($labelEnLastField === null) {
                                    return true;
                                }
                                $labelEn = $firstField['label']['en'] ?? null;
                                $nonDeletableLabels = ['Participant Name', 'Participant Email', 'Project Name'];
                                if (in_array($labelEn, $nonDeletableLabels)) {
                                    return false;
                                }

                                return true;
                            })
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('label.en')
                                        ->label('Label')
                                        ->required()
                                        ->reactive()
                                        ->helperText('Enter the label in English'),

                                    Forms\Components\Hidden::make('slug')
                                        ->label('Slug'),

                                    Forms\Components\TextInput::make('label.ar')
                                        ->label('التسمية')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                        ->helperText('أدخل التسمية باللغة العربية')
                                        ->required(),
                                ])->columns(),

                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('placeholder.en')
                                        ->label('Placeholder')
                                        ->helperText('Enter placeholder text in English')
                                        ->nullable(),
                                    Forms\Components\TextInput::make('placeholder.ar')
                                        ->label('العنصر النائب')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                        ->helperText('أدخل نص العنصر النائب باللغة العربية')
                                        ->nullable(),
                                ])->columns(),

                                Forms\Components\Select::make('type')
                                    ->label('Field Type')
                                    ->options(\App\Models\Form::FIELD_TYPES)
                                    ->required()
                                    ->helperText('Select the field type / اختر نوع الحقل')
                                    ->live(),

                                // Updated Options Field with Translation Support
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('options.en')
                                        ->label('Options')
                                        ->placeholder('e.g., Option 1, Option 2, Option 3')
                                        ->helperText('Enter multiple options separated by commas in English')
                                        ->visible(fn($get) => in_array($get('type'), ['dropdown', 'multi_select', 'radio', 'checkbox', 'rating']))
                                        ->required()
                                        ->live(onBlur: true),

                                    Forms\Components\TextInput::make('options.ar')
                                        ->label('الخيارات')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                        ->placeholder('مثال: خيار ١، خيار ٢، خيار ٣')
                                        ->helperText('أدخل خيارات متعددة مفصولة بفواصل باللغة العربية')
                                        ->visible(fn($get) => in_array($get('type'), ['dropdown', 'multi_select', 'radio', 'checkbox', 'rating']))
                                        ->required(),
                                ])->columns()
                                ->visible(fn($get) => in_array($get('type'), ['dropdown', 'multi_select', 'radio', 'checkbox', 'rating'])),

                                Forms\Components\CheckboxList::make('mandatory_options')
                                    ->label('Mandatory Options / الخيارات الإلزامية')
                                    ->helperText('Select which checkbox options must be checked to submit / حدد الخيارات التي يجب تحديدها للإرسال')
                                    ->options(function ($get) {
                                        $optionsEn = $get('options.en');
                                        if (empty($optionsEn)) {
                                            return [];
                                        }
                                        
                                        $options = \App\Models\FormField::parseOptionsString($optionsEn);
                                        
                                        $formattedOptions = [];
                                        foreach ($options as $index => $option) {
                                            $formattedOptions[$index + 1] = $option;
                                        }
                                        
                                        return $formattedOptions;
                                    })
                                    ->visible(fn($get) => $get('type') === 'checkbox' && !empty($get('options.en')))
                                  //  ->columns(2)
                                    ->gridDirection('row'),

                                Forms\Components\Repeater::make('validation_rules')
                                    ->collapsed()
                                    ->label('Validation Rules')
                                    ->reorderable(false)
                                    ->addActionLabel('Add Validation Rule')
                                    ->visible(function ($get) {
                                        $type = $get('type');
                                        $rules = match ($type) {
                                            'text', 'textarea' => ['min', 'max', 'regex'],
                                            'email' => ['email'],
                                            'number' => ['numeric', 'decimal_places', 'min', 'max'],
                                            'file' => ['file', 'mimes'],
                                            'date' => ['date', 'after', 'before', 'after_or_equal', 'before_or_equal', 'between'],
                                            'time' => ['after_time', 'before_time', 'between_time'],
                                            'url' => ['url'],
                                            'phone' => ['regex'],
                                            default => []
                                        };

                                        return !empty($rules);
                                    })
                                    ->schema([
                                        Forms\Components\Select::make('rule')
                                            ->helperText('Select a validation rule / اختر قاعدة التحقق')
                                            ->label('Rule')
                                            ->options(function ($get) {
                                                return match ($get('../../type')) {
                                                    'text', 'textarea' => [
                                                        'min' => 'Minimum Length',
                                                        'max' => 'Maximum Length',
                                                        'regex' => 'Regex Pattern',
                                                    ],
                                                    'email' => ['email' => 'Valid Email'],
                                                    'number' => [
                                                        'numeric' => 'Numeric',
                                                        'decimal_places' => 'Decimal Places',
                                                        'min' => 'Minimum Value',
                                                        'max' => 'Maximum Value',
                                                    ],
                                                    'file' => [
                                                        'file' => 'Valid File',
                                                        'mimes' => 'Allowed File Types',
                                                    ],
                                                    'date' => [
                                                        'date' => 'Valid Date',
                                                        'after' => 'After Date',
                                                        'before' => 'Before Date',
                                                        'after_or_equal' => 'After Or Equal Date',
                                                        'before_or_equal' => 'Before Or Equal Date',
                                                        'between' => 'Between Two Dates',
                                                    ],
                                                    'time' => [
                                                        'after_time' => 'After Time',
                                                        'before_time' => 'Before Time',
                                                        'between_time' => 'Between Two Times',
                                                    ],
                                                    'url' => ['url' => 'Valid URL'],
                                                    'phone' => ['regex' => 'Phone Format (e.g. +966512345678)'],
                                                    'paragraph' => [],
                                                    default => []
                                                };
                                            })
                                            ->disableOptionWhen(function ($value, $get, $component) {

                                                $currentItems = $get('../../validation_rules') ?? [];
                                                $currentPath = $component->getStatePath();

                                                foreach ($currentItems as $path => $item) {
                                                    if ($path === $currentPath || !isset($item['rule'])) {
                                                        continue;
                                                    }

                                                    if ($item['rule'] === $value) {
                                                        return true;
                                                    }
                                                }

                                                return false;
                                            })
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                if ($state) {
                                                    $set('value_date', null);
                                                    $set('start_date', null);
                                                    $set('end_date', null);
                                                    $set('value_time', null);
                                                    $set('start_time', null);
                                                    $set('end_time', null);
                                                    $set('value', null);
                                                    $set('max_file_size', null);
                                                    $set('allowed_mimes', null);
                                                }
                                            }),

                                        Forms\Components\DatePicker::make('value_date')
                                            ->label('Value / القيمة')
                                            ->visible(fn($get) => in_array($get('rule'), ['after', 'before', 'after_or_equal', 'before_or_equal'])),

                                        // Two Date Fields for "between"
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Start Date / تاريخ البدء')
                                            ->visible(fn($get) => $get('rule') === 'between')
                                            ->reactive()
                                            ->minDate(Carbon::tomorrow()), // Enforce > today

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('End Date / تاريخ الانتهاء')
                                            ->visible(fn($get) => $get('rule') === 'between')
                                            ->reactive()
                                            ->minDate(fn(callable $get) => $get('start_date') ? \Carbon\Carbon::parse($get('start_date'))->addDay() : null)
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                $start = $get('start_date');
                                                if ($start && $state && $state <= $start) {
                                                    // Reset invalid date
                                                    $set('end_date', null);
                                                }
                                            })->helperText('Must be after Start Date / يجب أن يكون بعد تاريخ البدء'),

                                        TimePicker::make('value_time')
                                            ->label('Time / الوقت')
                                            ->visible(fn($get) => in_array($get('rule'), ['after_time', 'before_time']))
                                            ->seconds(false),

                                        TimePicker::make('start_time')
                                            ->label('Start Time / وقت البدء')
                                            ->seconds(false)
                                            ->visible(fn($get) => $get('rule') === 'between_time')
                                            ->reactive(),

                                        TimePicker::make('end_time')
                                            ->label('End Time / وقت الانتهاء')
                                            ->seconds(false)
                                            ->visible(fn($get) => $get('rule') === 'between_time')
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                $start = $get('start_time');
                                                if ($start && $state) {
                                                    $startTime = Carbon::parse($start);
                                                    $endTime = Carbon::parse($state);
                                                    if ($endTime->lte($startTime)) {
                                                        $set('end_time', null); // reset invalid
                                                    }
                                                }
                                            })
                                            ->helperText('Must be after Start Time / يجب أن يكون بعد وقت البدء'),

                                        // Other fields (regex, min, max, etc.)
                                        Forms\Components\TextInput::make('value')
                                            ->label('Value')
                                            ->reactive()
                                            ->visible(fn($get) => in_array($get('rule'), ['min', 'max', 'regex', 'decimal_places']))
                                            ->helperText(fn($get) => $get('rule') === 'decimal_places'
                                                ? 'Number of allowed digits after the decimal point (e.g. 2 for prices)'
                                                : null),

                                        Forms\Components\TextInput::make('max_file_size')
                                            ->label('Max File Size (MB) / الحد الأقصى لحجم الملف (ميغابايت)')
                                            ->numeric()
                                            ->visible(fn($get) => in_array($get('rule'), ['file', 'mimes'])),

                                        Forms\Components\Hidden::make('allowed_mimes_string')
                                            ->visible(fn($get) => $get('rule') === 'mimes'),

                                        Forms\Components\Select::make('allowed_mimes')
                                            ->label('Allowed File Types / أنواع الملفات المسموح بها')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Select file types...')
                                            ->options([
                                                // Quick Selection Options
                                                'Quick Selection' => [
                                                    'all_images' => '🖼️ All Image Types',
                                                    'all_documents' => '📄 All Document Types',
                                                    'all_archives' => '📦 All Archive Types',
                                                    'all_media' => '🎵 All Media Types (Audio + Video)',
                                                ],

                                                // Documents
                                                'Documents' => [
                                                    'pdf' => 'PDF Document (.pdf)',
                                                    'doc' => 'Word Document (.doc)',
                                                    'docx' => 'Word Document (.docx)',
                                                    'rtf' => 'Rich Text Format (.rtf)',
                                                    'txt' => 'Text File (.txt)',
                                                    'odt' => 'OpenDocument Text (.odt)',
                                                ],
                                                // Spreadsheets
                                                'Spreadsheets' => [
                                                    'xls' => 'Excel Spreadsheet (.xls)',
                                                    'xlsx' => 'Excel Spreadsheet (.xlsx)',
                                                    'csv' => 'Comma Separated Values (.csv)',
                                                    'ods' => 'OpenDocument Spreadsheet (.ods)',
                                                ],
                                                // Presentations
                                                'Presentations' => [
                                                    'ppt' => 'PowerPoint Presentation (.ppt)',
                                                    'pptx' => 'PowerPoint Presentation (.pptx)',
                                                    'odp' => 'OpenDocument Presentation (.odp)',
                                                ],
                                                // Images
                                                'Images' => [
                                                    'jpg' => 'JPEG Image (.jpg)',
                                                    'jpeg' => 'JPEG Image (.jpeg)',
                                                    'png' => 'PNG Image (.png)',
                                                    'gif' => 'GIF Image (.gif)',
                                                    'bmp' => 'Bitmap Image (.bmp)',
                                                    'svg' => 'Scalable Vector Graphics (.svg)',
                                                    'webp' => 'WebP Image (.webp)',
                                                    'tiff' => 'TIFF Image (.tiff)',
                                                    'ico' => 'Icon File (.ico)',
                                                ],
                                                // Archives
                                                'Archives' => [
                                                    'zip' => 'ZIP Archive (.zip)',
                                                    'rar' => 'RAR Archive (.rar)',
                                                    '7z' => '7-Zip Archive (.7z)',
                                                    'tar' => 'TAR Archive (.tar)',
                                                    'gz' => 'GZIP Archive (.gz)',
                                                ],
                                                // Audio
                                                'Audio' => [
                                                    'mp3' => 'MP3 Audio (.mp3)',
                                                    'wav' => 'WAV Audio (.wav)',
                                                    'flac' => 'FLAC Audio (.flac)',
                                                    'aac' => 'AAC Audio (.aac)',
                                                    'ogg' => 'OGG Audio (.ogg)',
                                                ],
                                                // Video
                                                'Video' => [
                                                    'mp4' => 'MP4 Video (.mp4)',
                                                    'avi' => 'AVI Video (.avi)',
                                                    'mov' => 'QuickTime Video (.mov)',
                                                    'wmv' => 'Windows Media Video (.wmv)',
                                                    'flv' => 'Flash Video (.flv)',
                                                    'webm' => 'WebM Video (.webm)',
                                                ],
                                                // Code
                                                'Code Files' => [
                                                    'php' => 'PHP Script (.php)',
                                                    'js' => 'JavaScript (.js)',
                                                    'css' => 'CSS Stylesheet (.css)',
                                                    'html' => 'HTML Document (.html)',
                                                    'xml' => 'XML Document (.xml)',
                                                    'json' => 'JSON File (.json)',
                                                    'py' => 'Python Script (.py)',
                                                    'java' => 'Java Source (.java)',
                                                    'cpp' => 'C++ Source (.cpp)',
                                                    'c' => 'C Source (.c)',
                                                ],
                                            ])
                                            ->helperText('Select one or more file types. Use search to find specific formats.')
                                            ->visible(fn($get) => $get('rule') === 'mimes')
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                if (is_array($state)) {
                                                    $processedExtensions = [];

                                                    foreach ($state as $selected) {
                                                        // Handle special "all_" options
                                                        switch ($selected) {
                                                            case 'all_images':
                                                                $processedExtensions = array_merge($processedExtensions, [
                                                                    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'ico'
                                                                ]);
                                                                break;
                                                            case 'all_documents':
                                                                $processedExtensions = array_merge($processedExtensions, [
                                                                    'pdf', 'doc', 'docx', 'rtf', 'txt', 'odt'
                                                                ]);
                                                                break;
                                                            case 'all_archives':
                                                                $processedExtensions = array_merge($processedExtensions, [
                                                                    'zip', 'rar', '7z', 'tar', 'gz'
                                                                ]);
                                                                break;
                                                            case 'all_media':
                                                                $processedExtensions = array_merge($processedExtensions, [
                                                                    'mp3', 'wav', 'flac', 'aac', 'ogg', // Audio
                                                                    'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm' // Video
                                                                ]);
                                                                break;
                                                            default:
                                                                // Regular file extension
                                                                if (!str_starts_with($selected, 'all_')) {
                                                                    $processedExtensions[] = $selected;
                                                                }
                                                                break;
                                                        }
                                                    }

                                                    // Remove duplicates and convert to comma-separated string
                                                    $processedExtensions = array_unique($processedExtensions);
                                                    $set('allowed_mimes_string', implode(',', $processedExtensions));
                                                }
                                            })
                                            ->afterStateHydrated(function (callable $get, callable $set, $state) {
                                                // Convert comma-separated string back to array when loading existing data
                                                $allowedMimesString = $get('allowed_mimes_string');
                                                if (is_string($allowedMimesString) && !empty($allowedMimesString)) {
                                                    $extensions = array_map('trim', explode(',', $allowedMimesString));
                                                    $set('allowed_mimes', $extensions);
                                                } elseif (is_array($state) && !empty($state)) {
                                                    // If state is already an array, use it directly
                                                    $set('allowed_mimes', $state);
                                                }
                                            }),
                                    ])
                                    ->collapsible()->itemLabel(fn (array $state): ?string => $state['rule'] ?? null)
                                    ->createItemButtonLabel('Add Validation Rule')
                                    ->disableItemCreation(function ($get) {
                                        $fieldType = $get('type');
                                        $allRules = match ($fieldType) {
                                            'text', 'textarea' => ['min', 'max', 'regex'],
                                            'email' => ['email'],
                                            'number' => ['numeric', 'decimal_places', 'min', 'max'],
                                            'file' => ['file', 'mimes'],
                                            'date' => ['date', 'after', 'before', 'after_or_equal', 'before_or_equal', 'between'],
                                            'time' => ['after_time', 'before_time', 'between_time'],
                                            'url' => ['url'],
                                            'phone' => ['regex'],
                                           'paragraph' => [],
                                            default => []
                                        };

                                        $currentItems = $get('validation_rules') ?? [];
                                        $selectedRules = collect($currentItems)->pluck('rule')->filter()->toArray();

                                        return count($selectedRules) >= count($allRules);
                                    }),

                                Forms\Components\Toggle::make('required')
                                    ->label('Required Field / حقل مطلوب'),

                                Forms\Components\Toggle::make('conditional_logic')
                                    ->label('Conditional Logic / المنطق الشرطي')
                                    ->live(),

                                Forms\Components\Section::make('Conditional Logic Rules')
                                    ->visible(fn(callable $get) => $get('conditional_logic') === true)
                                    ->reactive()
                                    ->schema([
                                        Forms\Components\Repeater::make('conditional_logic_rules')
                                            ->schema([
                                                Forms\Components\Select::make('field_id')
                                                    ->label('Field / الحقل')
                                                    ->options(function (callable $get) {
                                                        $fields = $get('../../../../fields') ?? [];
                                                        return collect($fields)
                                                            ->filter(fn($f) => !empty($f['label']['en']))
                                                            ->mapWithKeys(fn($f, $index) => [$f['label']['en'] ?? $index => $f['label']['en']]);
                                                    })
                                                    ->searchable()
                                                    ->required()
                                                    ->reactive(),

                                                Forms\Components\Repeater::make('values')
                                                    ->label('Values to Compare / القيم للمقارنة')
                                                    ->schema(function (callable $get) {
                                                        $selectedFieldLabel = $get('field_id');
                                                        $fields = $get('../../../../fields') ?? [];

                                                        // Find the selected field
                                                        $selectedField = collect($fields)->first(function ($field) use ($selectedFieldLabel) {
                                                            $fieldLabel = $field['label']['en'] ?? '';
                                                            // Try exact match first
                                                            if ($fieldLabel === $selectedFieldLabel) {
                                                                return true;
                                                            }
                                                            // Try matching by slug as fallback
                                                            if (isset($field['slug']) && $field['slug'] === $selectedFieldLabel) {
                                                                return true;
                                                            }
                                                            return false;
                                                        });

                                                        if (!$selectedField) {
                                                            // Default to text inputs if no field selected
                                                            return [
                                                        Forms\Components\Fieldset::make('Value / القيمة')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('en')
                                                                    ->label('Value')
                                                                    ->required(),

                                                                Forms\Components\TextInput::make('ar')
                                                                    ->label('القيمة')
                                                                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                                                    ->required(),
                                                            ]),
                                                            ];
                                                        }

                                                        $fieldType = $selectedField['type'] ?? null;
                                                        $fieldOptions = $selectedField['options'] ?? [];


                                                        // Check if field has predefined options (dropdown, multi-select, radio, rating)
                                                        if (in_array($fieldType, ['dropdown', 'multi_select', 'radio', 'rating']) && !empty($fieldOptions)) {
                                                            // Process options to handle both string and array formats
                                                            $processedOptions = [];
                                                            if (isset($fieldOptions['en']) && isset($fieldOptions['ar']) &&
                                                                is_string($fieldOptions['en']) && is_string($fieldOptions['ar'])) {
                                                                // Convert string format to array
                                                                $enOptions = \App\Models\FormField::parseOptionsString($fieldOptions['en']);
                                                                $arOptions = \App\Models\FormField::parseOptionsString($fieldOptions['ar']);
                                                                $maxLength = max(count($enOptions), count($arOptions));

                                                                for ($i = 0; $i < $maxLength; $i++) {
                                                                    $processedOptions[] = [
                                                                        'en' => $enOptions[$i] ?? '',
                                                                        'ar' => $arOptions[$i] ?? ''
                                                                    ];
                                                                }
                                                            } elseif (is_array($fieldOptions)) {
                                                                $processedOptions = $fieldOptions;
                                                            }

                                                            $options = [];
                                                            foreach ($processedOptions as $option) {
                                                                if (is_array($option) && isset($option['en']) && isset($option['ar'])) {
                                                                    // Format as "english,arabic"
                                                                    $displayValue = $option['en'] . ',' . $option['ar'];
                                                                    $options[$displayValue] = $displayValue;
                                                                } elseif (is_string($option)) {
                                                                    $options[$option] = $option;
                                                                }
                                                            }

                                                            // Use Select component for fields with predefined options

                                                            return [
                                                                Forms\Components\Select::make('value')
                                                                    ->label('Select Value / اختر القيمة')
                                                                    ->options($options)
                                                                    ->searchable()
                                                                    ->placeholder('Select a value / اختر قيمة')
                                                                    ->default(function (callable $get) {
                                                                        // Get the current value and convert it to the right format
                                                                        $currentValue = $get('value');

                                                                        if (is_array($currentValue) && isset($currentValue['en']) && isset($currentValue['ar'])) {
                                                                            return $currentValue['en'] . ',' . $currentValue['ar'];
                                                                        }
                                                                        // If it's already a string with comma, return as is
                                                                        if (is_string($currentValue) && strpos($currentValue, ',') !== false) {
                                                                            return $currentValue;
                                                                        }
                                                                        return $currentValue;
                                                                    })
                                                                    ->afterStateUpdated(function (callable $set, $state) {
                                                                        // When a value is selected, ensure it's properly formatted
                                                                        if ($state && is_string($state) && strpos($state, ',') !== false) {
                                                                            $set('value', $state);
                                                                        }
                                                                    })
                                                                    ->live()
                                                                    ->reactive()
                                                                    ->dehydrated()
                                                                    ->validationAttribute('value')
                                                                    ->extraInputAttributes(['data-testid' => 'conditional-logic-select'])
                                                                    ->helperText('Select a value from the dropdown / اختر قيمة من القائمة')
                                                            ];
                                                        } else {
                                                            // Use TextInput for other field types
                                                            return [
                                                                Forms\Components\Fieldset::make('Value / القيمة')
                                                                    ->schema([
                                                                        Forms\Components\TextInput::make('en')
                                                                            ->label('Value')
                                                                            ->required()
                                                                            ->default(function (callable $get) {
                                                                                $currentValue = $get('value');
                                                                                if (is_array($currentValue) && isset($currentValue['en'])) {
                                                                                    return $currentValue['en'];
                                                                                }
                                                                                return $currentValue;
                                                                            }),
                                                                        Forms\Components\TextInput::make('ar')
                                                                            ->label('القيمة')
                                                                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                                                            ->required()
                                                                            ->default(function (callable $get) {
                                                                                $currentValue = $get('value');
                                                                                if (is_array($currentValue) && isset($currentValue['ar'])) {
                                                                                    return $currentValue['ar'];
                                                                                }
                                                                                return $currentValue;
                                                                            }),
                                                                    ]),
                                                            ];
                                                        }
                                                    })
                                                    ->minItems(1)
                                                    ->collapsible()
                                                    ->required()
                                                    ->reactive(),

                                            ])
                                            ->columns(1)
                                            ->reorderable(false)
                                            ->reactive()
                                            ->label('Multiple Conditions / شروط متعددة')
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Evaluation Criteria Configuration / تكوين معايير التقييم')
                    ->visible(fn($get) => $get('type') === 'evaluation')
                    ->schema([
                        Forms\Components\Repeater::make('evaluation_criteria')
                            ->label('Main Criteria / المعايير الرئيسية')
                            ->afterStateHydrated(fn($component, $state) => $component->state($state ?? []))
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('label.en')
                                        ->label('Main Criterion Label')
                                        ->required(),

                                    Forms\Components\TextInput::make('label.ar')
                                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                    ->label('تسمية المعيار الرئيسي')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                        ->required(),
                                ])->columns(),

                                Forms\Components\Hidden::make('slug')
                                    ->label('Slug'),

                                Forms\Components\TextInput::make('weight')
                                    ->label('Main Criterion Weight (%) / وزن المعيار الرئيسي (%)')
                                    ->numeric()
                                    ->helperText('The weight of the main criterion must be between 0 and 100%.')
                                    ->default(0)
                                    ->rules(['required', 'numeric', 'min:0', 'max:100'])
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Calculate total weight of main criteria
                                        $criteria = $get('../../evaluation_criteria') ?? [];
                                        $totalWeight = collect($criteria)->sum(function ($item) {
                                            $weight = $item['weight'] ?? 0;
                                            // Convert to float if possible, otherwise 0
                                            return is_numeric($weight) ? (float)$weight : 0;
                                        });

                                        // Update the total weight display
                                        $set('../../main_criteria_total', $totalWeight);

                                        // Show validation message
                                        if ($totalWeight !== 100) {
                                            $set('../../main_criteria_validation', "Total main criteria weight: {$totalWeight}%. Must equal 100%.");
                                        } else {
                                            $set('../../main_criteria_validation', null);
                                        }
                                    }),

                                Forms\Components\Select::make('scoring_method')
                                    ->label('Scoring Method / طريقة التقييم')
                                    ->options([
                                        'numeric_scale' => 'Numeric Scale (e.g., 1-5)',
                                        'percentage' => 'Percentage (0-100%)',
                                        'yes_no' => 'Yes/No with Points',
                                        'multiple_choice' => 'Multiple Choice with Points',
                                        'custom_range' => 'Custom Score Range',
                                    ])
                                    ->reactive()
                                    ->live()
                                    ->required(),

                                // Multiple Choice Configuration
                                Forms\Components\Group::make([
                                    Forms\Components\Select::make('selection_type')
                                        ->label('Selection Type / نوع الاختيار')
                                        ->options([
                                            'single' => 'Single Selection (Radio Buttons)',
                                            'multiple' => 'Multiple Selections (Checkboxes)',
                                        ])
                                        ->required()
                                        ->default('single')
                                        ->visible(fn($get) => $get('scoring_method') === 'multiple_choice'),

                                    Forms\Components\Toggle::make('required')
                                        ->label('Required / مطلوب')
                                        ->helperText('If required, judge must select at least one option. To allow score of 0, include an option with 0 points. / إذا كان مطلوباً، يجب على المحكم اختيار خيار واحد على الأقل. للسماح بدرجة 0، قم بتضمين خيار بقيمة 0.')
                                        ->default(false)
                                        ->visible(fn($get) => $get('scoring_method') === 'multiple_choice'),

                                    Forms\Components\Repeater::make('multiple_choice_options')
                                        ->label('Options / الخيارات')
                                        ->schema([
                                            Forms\Components\Group::make([
                                                Forms\Components\TextInput::make('label.en')
                                                    ->label('Option Label (English)')
                                                    ->required()
                                                    ->columnSpan(2),

                                                Forms\Components\TextInput::make('label.ar')
                                                    ->label('تسمية الخيار (عربي)')
                                                    ->required()
                                                    ->columnSpan(2),

                                                Forms\Components\TextInput::make('points')
                                                    ->label('Points / النقاط')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(0)
                                                    ->helperText('Can be positive, negative, or zero / يمكن أن تكون موجبة أو سالبة أو صفر')
                                                    ->columnSpan(1),
                                            ])->columns(5),
                                        ])
                                        ->minItems(1)
                                        ->collapsible()
                                        ->reorderable()
                                        ->cloneable()
                                        ->addActionLabel('Add Option / إضافة خيار')
                                        ->visible(fn($get) => $get('scoring_method') === 'multiple_choice'),
                                ])
                                    ->visible(fn($get) => $get('scoring_method') === 'multiple_choice')
                                    ->columnSpanFull(),

                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('min_score')
                                        ->label('Minimum Score / الحد الأدنى للنقاط')
                                        ->numeric()
                                        ->required(),

                                    Forms\Components\TextInput::make('max_score')
                                        ->label('Maximum Score / الحد الأقصى للنقاط')
                                        ->numeric()
                                        ->required(),
                                ])
                                    ->visible(fn($get) => $get('scoring_method') === 'custom_range')
                                    ->columns(),

                                Forms\Components\Select::make('aggregation_method')
                                    ->label('Aggregation Method for Subcriteria / طريقة تجميع المعايير الفرعية')
                                    ->options([
                                        'sum' => 'Sum of Subcriteria Scores',
                                        'average' => 'Average of Subcriteria Scores',
                                    ])
                                    ->reactive()
                                    ->live()
                                    ->required(fn(callable $get) => !empty($get('subcriteria'))),

                                Forms\Components\Group::make([
                                    Forms\Components\Toggle::make('enable_comments_criteria')
                                        ->label('Allow Judge Comment / السماح بتعليق المحكم')
                                        ->live()
                                        ->default(false),

                                    Forms\Components\TextInput::make('comment_max_chars')
                                        ->label('Max Comment Characters / الحد الأقصى لعدد حروف التعليق')
                                        ->numeric()
                                        ->nullable()
                                        ->visible(fn(callable $get) => $get('enable_comments_criteria') === true),
                                ])->columns(),

                                Forms\Components\Repeater::make('subcriteria')
                                    ->label('Subcriteria / المعايير الفرعية')
                                    ->schema([
                                        Forms\Components\Group::make([
                                            Forms\Components\TextInput::make('label.en')
                                                ->label('Subcriterion Label ')
                                                ->required(),

                                            Forms\Components\TextInput::make('label.ar')
                                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                                ->label('تسمية المعيار الفرعي')
                                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                                ->required(),
                                        ])->columns(),

                                        Forms\Components\Hidden::make('slug')
                                            ->label('Slug'),

                                        Forms\Components\TextInput::make('weight')
                                            ->label(function (\Filament\Forms\Get $get) {
                                                return $get('../../aggregation_method') === 'sum'
                                                    ? 'Subcriterion Weight (%) (required) / وزن المعيار الفرعي (%) (مطلوب)'
                                                    : 'Subcriterion Weight (%) (optional) / وزن المعيار الفرعي (%) (اختياري)';
                                            })
                                            ->numeric()
                                            ->reactive()
                                            ->live()
                                            ->rules(function (\Filament\Forms\Get $get) {
                                                return $get('../../aggregation_method') === 'sum'
                                                    ? ['required', 'numeric']
                                                    : ['nullable', 'numeric'];
                                            })
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                // Get all criteria and recalculate all subcriteria totals
                                                $criteria = $get('../../../evaluation_criteria') ?? [];

                                                // Update subcriteria totals for all main criteria
                                                foreach ($criteria as $index => $criterion) {
                                                    if (isset($criterion['subcriteria']) && is_array($criterion['subcriteria'])) {
                                                        $subcriteria = $criterion['subcriteria'];
                                                        $totalSubWeight = collect($subcriteria)->sum(function ($sub) {
                                                            return is_numeric($sub['weight'] ?? 0) ? (float) $sub['weight'] : 0;
                                                        });

                                                        // Update the sub-criteria total for this main criterion
                                                        $set("../../evaluation_criteria.{$index}.subcriteria_total", $totalSubWeight);

                                                        // Show validation message
                                                        if ($totalSubWeight !== 100) {
                                                            $set("../../evaluation_criteria.{$index}.subcriteria_validation", "Total sub-criteria weight: {$totalSubWeight}%. Must equal 100%.");
                                                        } else {
                                                            $set("../../evaluation_criteria.{$index}.subcriteria_validation", null);
                                                        }
                                                    }
                                                }
                                            }),
                                        Forms\Components\Select::make('scoring_method')
                                            ->label('Scoring Method / طريقة التقييم')
                                            ->options([
                                                'numeric_scale' => 'Numeric Scale (e.g., 1-5)',
                                                'percentage' => 'Percentage (0-100%)',
                                                'yes_no' => 'Yes/No with Points',
                                                'multiple_choice' => 'Multiple Choice with Points',
                                                'custom_range' => 'Custom Score Range',
                                            ])
                                            ->reactive()
                                            ->live()
                                            ->required(),

                                        // Multiple Choice Configuration for Subcriteria
                                        Forms\Components\Group::make([
                                            Forms\Components\Select::make('selection_type')
                                                ->label('Selection Type / نوع الاختيار')
                                                ->options([
                                                    'single' => 'Single Selection (Radio Buttons)',
                                                    'multiple' => 'Multiple Selections (Checkboxes)',
                                                ])
                                                ->required()
                                                ->default('single')
                                                ->visible(fn($get) => $get('scoring_method') === 'multiple_choice'),

                                            Forms\Components\Toggle::make('required')
                                                ->label('Required / مطلوب')
                                                ->helperText('If required, judge must select at least one option. To allow score of 0, include an option with 0 points. / إذا كان مطلوباً، يجب على المحكم اختيار خيار واحد على الأقل. للسماح بدرجة 0، قم بتضمين خيار بقيمة 0.')
                                                ->default(false)
                                                ->visible(fn($get) => $get('scoring_method') === 'multiple_choice'),

                                            Forms\Components\Repeater::make('multiple_choice_options')
                                                ->label('Options / الخيارات')
                                                ->schema([
                                                    Forms\Components\Group::make([
                                                        Forms\Components\TextInput::make('label.en')
                                                            ->label('Option Label (English)')
                                                            ->required()
                                                            ->columnSpan(2),

                                                        Forms\Components\TextInput::make('label.ar')
                                                            ->label('تسمية الخيار (عربي)')
                                                            ->required()
                                                            ->columnSpan(2),

                                                        Forms\Components\TextInput::make('points')
                                                            ->label('Points / النقاط')
                                                            ->numeric()
                                                            ->required()
                                                            ->default(0)
                                                            ->helperText('Can be positive, negative, or zero / يمكن أن تكون موجبة أو سالبة أو صفر')
                                                            ->columnSpan(1),
                                                    ])->columns(5),
                                                ])
                                                ->minItems(1)
                                                ->collapsible()
                                                ->reorderable()
                                                ->cloneable()
                                                ->addActionLabel('Add Option / إضافة خيار')
                                                ->visible(fn($get) => $get('scoring_method') === 'multiple_choice'),
                                        ])
                                            ->visible(fn($get) => $get('scoring_method') === 'multiple_choice')
                                            ->columnSpanFull(),

                                        Forms\Components\Group::make([
                                            Forms\Components\TextInput::make('min_score')
                                                ->label('Minimum Score / الحد الأدنى للنقاط')
                                                ->numeric()
                                                ->required(),

                                            Forms\Components\TextInput::make('max_score')
                                                ->label('Maximum Score / الحد الأقصى للنقاط')
                                                ->numeric()
                                                ->required(),
                                        ])
                                            ->visible(fn($get) => $get('scoring_method') === 'custom_range')
                                            ->columns(),

                                        Forms\Components\Group::make([
                                            Forms\Components\Toggle::make('enable_comments')
                                                ->label('Allow Judge Comment / السماح بتعليق المحكم')
                                                ->live()
                                                ->default(false),

                                            Forms\Components\TextInput::make('comment_max_chars')
                                                ->label('Max Comment Characters / الحد الأقصى لعدد حروف التعليق')
                                                ->numeric()
                                                ->nullable()
                                                ->visible(fn(callable $get) => $get('enable_comments') === true),
                                        ])->columns(),
                                    ])
                                    ->collapsible()
                                    ->reorderable()
                                    ->cloneable()
                                    ->columnSpanFull(),

                                // Sub-criteria validation display for this main criterion
                                Forms\Components\Placeholder::make('subcriteria_total')
                                    ->label('Total Sub-criteria Weight')
                                    ->content(function (callable $get) {
                                        $subcriteria = $get('subcriteria') ?? [];
                                        $totalWeight = collect($subcriteria)->sum(function ($item) {
                                            $weight = $item['weight'] ?? 0;
                                            return is_numeric($weight) ? (float)$weight : 0;
                                        });
                                        return "Total: {$totalWeight}%";
                                    })
                                    ->visible(fn($get) => !empty($get('subcriteria'))),

                                Forms\Components\Placeholder::make('subcriteria_validation')
                                    ->label('Sub-criteria Validation Status')
                                    ->content(function (callable $get) {
                                        $subcriteria = $get('subcriteria') ?? [];
                                        $totalWeight = collect($subcriteria)->sum(function ($item) {
                                            $weight = $item['weight'] ?? 0;
                                            return is_numeric($weight) ? (float)$weight : 0;
                                        });

                                        if (abs($totalWeight - 100) < 0.01) {
                                            return '✅ Sub-criteria weights total 100% - Valid';
                                        } elseif ($totalWeight > 0) {
                                            return "❌ Sub-criteria weights total {$totalWeight}% - Must equal 100%";
                                        }

                                        return '⚠️ Add sub-criteria and set weights';
                                    })
                                    ->visible(fn($get) => !empty($get('subcriteria')))
                                    ->extraAttributes(function (callable $get) {
                                        $subcriteria = $get('subcriteria') ?? [];
                                        $totalWeight = collect($subcriteria)->sum(function ($item) {
                                            $weight = $item['weight'] ?? 0;
                                            return is_numeric($weight) ? (float)$weight : 0;
                                        });

                                        if (abs($totalWeight - 100) < 0.01) {
                                            return ['class' => 'text-green-600 font-semibold'];
                                        } elseif ($totalWeight > 0) {
                                            return ['class' => 'text-red-600 font-semibold'];
                                        }

                                        return ['class' => 'text-yellow-600 font-semibold'];
                                    }),
                            ])
                            ->collapsible()
                            ->reorderable()
                            ->columnSpanFull(),

                        // Main criteria validation display
                        Forms\Components\Placeholder::make('main_criteria_total')
                            ->label('Total Main Criteria Weight')
                            ->content(function (callable $get) {
                                $criteria = $get('evaluation_criteria') ?? [];
                                $totalWeight = collect($criteria)->sum(function ($item) {
                                    $weight = $item['weight'] ?? 0;
                                    return is_numeric($weight) ? (float)$weight : 0;
                                });
                                return "Total: {$totalWeight}%";
                            })
                            ->visible(fn($get) => !empty($get('evaluation_criteria'))),

                        Forms\Components\Placeholder::make('main_criteria_validation')
                            ->label('Validation Status')
                            ->content(function (callable $get) {
                                $criteria = $get('evaluation_criteria') ?? [];
                                $totalWeight = collect($criteria)->sum(function ($item) {
                                    $weight = $item['weight'] ?? 0;
                                    return is_numeric($weight) ? (float)$weight : 0;
                                });

                                if (abs($totalWeight - 100) < 0.01) {
                                    return '✅ Main criteria weights total 100% - Valid';
                                } elseif ($totalWeight > 0) {
                                    return "❌ Main criteria weights total {$totalWeight}% - Must equal 100%";
                                }

                                return '⚠️ Add main criteria and set weights';
                            })
                            ->visible(fn($get) => !empty($get('evaluation_criteria')))
                            ->extraAttributes(function (callable $get) {
                                $criteria = $get('evaluation_criteria') ?? [];
                                $totalWeight = collect($criteria)->sum(function ($item) {
                                    $weight = $item['weight'] ?? 0;
                                    return is_numeric($weight) ? (float)$weight : 0;
                                });

                                if (abs($totalWeight - 100) < 0.01) {
                                    return ['class' => 'text-green-600 font-semibold'];
                                } elseif ($totalWeight > 0) {
                                    return ['class' => 'text-red-600 font-semibold'];
                                }

                                return ['class' => 'text-yellow-600 font-semibold'];
                            }),
                    ]),

                Forms\Components\Section::make('Overall Evaluation Comments / التعليقات العامة على التقييم')
                    ->description('Enable or disable the ability to leave overall feedback on the project. / تفعيل أو تعطيل إمكانية ترك تعليق عام على المشروع')
                    ->visible(fn($get) => $get('type') === 'evaluation')
                    ->schema([
                        Forms\Components\Toggle::make('enable_overall_comments')
                            ->label('Enable Overall Comments / تفعيل التعليقات العامة')
                            ->helperText('Allow judges to submit an overall comment about the project. / السماح للمقيّمين بكتابة تعليق عام حول المشروع')
                            ->default(false)
                    ]),

                Forms\Components\Section::make('Scoring System Configuration / تكوين نظام التقييم')
                    ->visible(fn($get) => $get('type') === 'evaluation')
                    ->schema([
                        Forms\Components\TextInput::make('total_score')
                            ->label('Total Score / الدرجة الكلية')
                            ->numeric()
                            ->default(100)
                            ->required()
                            ->afterStateHydrated(fn($component, $state) => $component->state($state ?? 100)),

                        Forms\Components\Select::make('rounding_rule')
                            ->label('Rounding Rule / قاعدة التقريب')
                            ->options([
                                '1' => 'Nearest Whole Number',
                                '0.5' => 'Nearest 0.5',
                                '0.1' => 'Nearest 0.1',
                            ])
                            ->default('1')
                            ->afterStateHydrated(fn($component, $state) => $component->state($state ?? '1')),
                    ]),

                Forms\Components\Section::make('Compliance and Agreement / الامتثال والموافقة')
                    ->visible(fn($get) => $get('type') === 'evaluation')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Textarea::make('evaluation_agreement_text.en')
                                ->label('Evaluation Agreement Text')
                                ->nullable()
                                ->requiredIf('require_agreement_acceptance', true)
                                ->afterStateHydrated(function ($component, $state) {
                                    // Handle both old format (string) and new format (array)
                                    if (is_array($state)) {
                                        $component->state($state['en'] ?? '');
                                    } else {
                                        $component->state($state ?? '');
                                    }
                                }),
                            Forms\Components\Textarea::make('evaluation_agreement_text.ar')
                                ->label('نص اتفاقية التقييم')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->nullable()
                                ->requiredIf('require_agreement_acceptance', true)
                                ->afterStateHydrated(function ($component, $state) {
                                    // Handle both old format (string) and new format (array)
                                    if (is_array($state)) {
                                        $component->state($state['ar'] ?? '');
                                    } else {
                                        $component->state($state ?? '');
                                    }
                                }),
                        ])->columns(),

                        Forms\Components\Toggle::make('require_agreement_acceptance')
                            ->label('Require Judges to Accept Agreement / إلزام المحكمين بالموافقة على الاتفاقية')
                            ->default(false)
                            ->afterStateHydrated(fn($component, $state) => $component->state($state ?? false)),
                    ]),
            ]);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();

                if ($user->isSuperAdmin()) {
                    return $query;
                }

                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                    ->pluck('program_id')
                    ->toArray();

                return $query->whereIn('program_id', $supervisorPrograms);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('program.title')->label('Program')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Submissions')
                    ->default(0)
                    ->getStateUsing(fn($record) => $record->submissionTrend()->sum('count')),

                Tables\Columns\IconColumn::make('trend')
                    ->label('Trend')
                    ->getStateUsing(fn($record) => $record->trend)
                    ->icon(fn($state) => $state === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Form Type')
                    ->options(Form::getAvailableFormTypes())
                    ->multiple()
                    ->placeholder('All'),

                Tables\Filters\SelectFilter::make('is_published')
                    ->label('Published')
                    ->options([
                        '1' => 'Published',
                        '0' => 'Draft',
                    ])
                    ->multiple()
                    ->placeholder('All'),

                Tables\Filters\Filter::make('created_at')
                    ->label('Created Date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->displayFormat('d/m/Y')
                            ->label('Created At From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->displayFormat('d/m/Y')
                            ->label('Created At To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),

                Tables\Filters\Filter::make('updated_at')
                    ->label('Updated Date')
                    ->form([
                        Forms\Components\DatePicker::make('updated_from')
                            ->displayFormat('d/m/Y')
                            ->label('Updated At From'),
                        Forms\Components\DatePicker::make('updated_until')
                            ->displayFormat('d/m/Y')
                            ->label('Updated At To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['updated_from'], fn($q) => $q->whereDate('updated_at', '>=', $data['updated_from']))
                            ->when($data['updated_until'], fn($q) => $q->whereDate('updated_at', '<=', $data['updated_until']));
                    }),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->isArchived()),

                Action::make('archive')
                    ->label(__('form_archive.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('form_archive.confirm_archive'))
                    ->modalDescription(__('form_archive.archive_confirmation'))
                    ->visible(fn ($record) => !$record->isArchived() && static::canArchive($record))
                    ->action(function (Form $record) {
                        try {
                            $approvalService = new \App\Services\FormApprovalService();
                            
                            $result = $approvalService->processAction(
                                'archive',
                                [
                                    'is_archived' => true,
                                    'form_id' => $record->id,
                                    'name' => $record->name,
                                    'old_values' => ['is_archived' => $record->is_archived ?? false],
                                ],
                                $record->id,
                                'Form archive request / طلب أرشفة النموذج',
                                auth()->id()
                            );

                            if ($result['success']) {
                                if ($result['requires_approval']) {
                                    Notification::make()
                                        ->title('Archive Request Submitted / تم تقديم طلب الأرشفة')
                                        ->body('Your form archive request has been submitted for approval. / تم تقديم طلب أرشفة النموذج للموافقة.')
                                        ->success()
                                        ->send();
                                } else {
                                    // Execute immediately if no workflow
                                    $record->archive();
                                    Notification::make()
                                        ->title(__('form_archive.form_archived'))
                                        ->success()
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->title('Error / خطأ')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('form_archive.failed_to_archive'))
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('restore')
                    ->label(__('form_archive.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('form_archive.confirm_restore'))
                    ->modalDescription(__('form_archive.restore_confirmation'))
                    ->action(function (Form $record) {
                        try {
                            $record->restore();

                            Notification::make()
                                ->title(__('form_archive.form_restored'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('form_archive.failed_to_restore'))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => static::canRestore($record)),

                Action::make('delete')
                    ->label('Delete / حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize(fn ($record) => static::canDelete($record))
                    ->modalHeading('Delete Form / حذف النموذج')
                    ->modalDescription('Are you sure you want to delete this form? This action will be submitted for approval. / هل أنت متأكد من حذف هذا النموذج؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->action(function (Form $record) {
                        try {
                            // Use FormApprovalService for deleting forms
                            $approvalService = new \App\Services\FormApprovalService();
                            $result = $approvalService->processAction(
                                'delete',
                                ['form_id' => $record->id, 'name' => $record->name],
                                $record->id,
                                'Form deletion request / طلب حذف النموذج',
                                auth()->id()
                            );

                            if ($result['success']) {
                                if ($result['requires_approval']) {
                                    Notification::make()
                                        ->title('Deletion Request Submitted / تم تقديم طلب الحذف')
                                        ->body('Your form deletion request has been submitted for approval. / تم تقديم طلب حذف النموذج للموافقة.')
                                        ->success()
                                        ->send();
                                    // Don't delete the record - it needs approval
                                } else {
                                    // Execute immediately if no workflow
                                    $record->delete();
                                    Notification::make()
                                        ->title('Form Deleted / تم حذف النموذج')
                                        ->body('The form has been deleted successfully. / تم حذف النموذج بنجاح.')
                                        ->success()
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->title('Error / خطأ')
                                    ->body('Failed to submit deletion request. / فشل في تقديم طلب الحذف.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error / خطأ')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn ($record) => !$record->isArchived() && auth()->user()?->can('update Form'))
                    ->action(function (Form $record) {

                        // Duplicate Form
                        $newForm = $record->replicate();
                        $newForm->name = $record->name . ' (Copy)';
                        $newForm->save();

                        // Duplicate related fields
                        foreach ($record->fields as $field) {
                            $newField = $field->replicate();
                            $newField->form_id = $newForm->id;
                            $newField->save();
                        }

                        Notification::make()
                            ->title('Form duplicated successfully.')
                            ->success()
                            ->send();

                        return redirect(FormResource::getUrl());
                    }),
            ])

            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('archive')
                        ->label(__('form_archive.archive_selected'))
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('form_archive.confirm_archive'))
                        ->modalDescription(__('form_archive.archive_selected_confirmation'))
                        ->action(function (Collection $records) {
                            try {
                                $count = 0;
                                $alreadyArchived = 0;

                                foreach ($records as $record) {
                                    if (!$record->isArchived()) {
                                        $record->archive();
                                        $count++;
                                    } else {
                                        $alreadyArchived++;
                                    }
                                }

                                if ($count > 0) {
                                    Notification::make()
                                        ->title(__('form_archive.forms_archived'))
                                        ->body(__('form_archive.successfully_archived_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }

                                if ($alreadyArchived > 0) {
                                    Notification::make()
                                        ->title(__('form_archive.warning'))
                                        ->body(__('form_archive.already_archived_count', ['count' => $alreadyArchived]))
                                        ->warning()
                                        ->send();
                                }

                                if ($count === 0 && $alreadyArchived > 0) {
                                    Notification::make()
                                        ->title(__('form_archive.no_action_taken'))
                                        ->body(__('form_archive.all_selected_already_archived'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('form_archive.failed_to_archive_selected'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('archive Form') ?? false),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(__('form_archive.restore_selected'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('form_archive.confirm_restore'))
                        ->modalDescription(__('form_archive.restore_selected_confirmation'))
                        ->action(function (Collection $records) {
                            try {
                                $count = 0;
                                $alreadyActive = 0;

                                foreach ($records as $record) {
                                    if ($record->isArchived()) {
                                        $record->restore();
                                        $count++;
                                    } else {
                                        $alreadyActive++;
                                    }
                                }

                                if ($count > 0) {
                                    Notification::make()
                                        ->title(__('form_archive.forms_restored'))
                                        ->body(__('form_archive.successfully_restored_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                }

                                if ($alreadyActive > 0) {
                                    Notification::make()
                                        ->title(__('form_archive.warning'))
                                        ->body(__('form_archive.already_active_count', ['count' => $alreadyActive]))
                                        ->warning()
                                        ->send();
                                }

                                if ($count === 0 && $alreadyActive > 0) {
                                    Notification::make()
                                        ->title(__('form_archive.no_action_taken'))
                                        ->body(__('form_archive.all_selected_already_active'))
                                        ->warning()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('form_archive.failed_to_restore_selected'))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn () => auth()->user()?->can('restore Form') ?? false),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete Form'))
                        ->modalDescription('Are you sure you want to delete this form? This action cannot be undone.'),

                    Tables\Actions\BulkAction::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->can('update Form'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $newForm = $record->replicate();
                                $newForm->name = $record->name . ' (Copy)';
                                $newForm->save();

                                foreach ($record->fields as $field) {
                                    $newField = $field->replicate();
                                    $newField->form_id = $newForm->id;
                                    $newField->save();
                                }
                            }

                            Notification::make()
                                ->title('Selected forms duplicated successfully.')
                                ->success()
                                ->send();
                        }),
                ]),
                Tables\Actions\ExportBulkAction::make()
                    ->exporter(FormExporter::class)
                    ->columnMapping(false)
                    ->fileName('Forms_List_' . now()->format('Y-m-d'))
                    ->label('Export Form')
                    ->modalHeading('Export Form'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            FormStatsOverview::class,
        ];
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForms::route('/'),
            'create' => Pages\CreateForm::route('/create'),
            'edit' => Pages\EditForm::route('/{record}/edit'),
            'view' => Pages\ViewForm::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Form') ?? false;
    }

    /**
     * IDOR prevention: verify user has access to the form's program before allowing view.
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->can('view Form')) {
            return false;
        }
        return static::userCanAccessFormProgram($user, $record->program_id);
    }

    public static function canCreate(): bool
    {
        if (!auth()->user()?->can('create Form')) {
            return false;
        }
        return !empty(currentProgramId());
    }

    /**
     * IDOR prevention: verify user has access to the form's program before allowing edit.
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->can('update Form') || $record->isArchived()) {
            return false;
        }
        return static::userCanAccessFormProgram($user, $record->program_id);
    }

    /**
     * IDOR prevention: verify user has access to the form's program before allowing delete.
     */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->can('delete Form')) {
            return false;
        }
        return static::userCanAccessFormProgram($user, $record->program_id);
    }

    /**
     * IDOR prevention: verify user has access to the form's program before allowing archive.
     */
    public static function canArchive(Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->can('archive Form') || $record->isArchived()) {
            return false;
        }
        return static::userCanAccessFormProgram($user, $record->program_id);
    }

    /**
     * IDOR prevention: verify user has access to the form's program before allowing restore.
     */
    public static function canRestore(Model $record): bool
    {
        $user = auth()->user();
        if (!$user || !$user->can('restore Form') || !$record->isArchived()) {
            return false;
        }
        return static::userCanAccessFormProgram($user, $record->program_id);
    }

    /**
     * IDOR prevention: verify user has authorization to access forms for the given program.
     * Super admins have full access. Others must be assigned via user_programs.
     */
    protected static function userCanAccessFormProgram($user, ?int $programId): bool
    {
        if ($programId === null) {
            return false;
        }
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }
        return $user->programs()->where('programs.id', $programId)->exists();
    }
}
