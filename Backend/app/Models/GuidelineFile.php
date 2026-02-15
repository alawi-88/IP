<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;
use Filament\Forms;
use Filament\Tables;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Rules\GuidelineFile as GuidelineFileRule;

class GuidelineFile extends Model
{
    use HasTranslations, LogsActivity, HasActivityLog;

    protected array $logFields = [
        'title',
        'attachment',
        'guideline.title'
    ];

    protected string $moduleName = 'Guideline File';
    protected string $logName = 'guideline_file';

    public array $translatable = [
        'title',
        'description',
    ];

    protected $fillable = ['guideline_id', 'title', 'attachment', 'file_type', 'description'];

    public function guideline(): BelongsTo
    {
        return $this->belongsTo(Guideline::class);
    }
    public function getAttachmentValue()
    {
        return $this->attachment;
    }
    public function getAttachmentVideoValue()
    {
        return $this->attachment_video;
    }
    public function getAttachmentDocumentValue()
    {
        return $this->attachment_document;
    }
    public function getAttachmentImageValue()
    {
        return $this->attachment_image;
    }
    public function getFileTypeValue()
    {
        return $this->file_type;
    }
    public static function form(): array
    {
       
        return [
            Forms\Components\TextInput::make('title.en')
                ->label('File Title')
                ->required()
                ->placeholder('Enter file title')
                ->rules(['required'])
                ->validationMessages([
                    'required' => 'Title is required',
                ]),

            Forms\Components\TextInput::make('title.ar')
                ->label('العنوان')
                ->required()
                ->placeholder('أدخل عنوان الملف')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->validationMessages([
                    'required' => 'عنوان الملف مطلوب',
                ])
                ->rules(['required']),

            Forms\Components\Textarea::make('description.en')
                ->label('File Description')
                ->placeholder('Enter file description')
                ->required()
                ->rules(['required'])
                ->validationMessages([
                    'required' => 'File description is required',
                ])
                ->rows(3),

            Forms\Components\Textarea::make('description.ar')
                ->label('الوصف')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->placeholder('أدخل وصف الملف')
                ->required()
                ->rules(['required'])
                ->validationMessages([
                    'required' => 'وصف الملف مطلوب',
                ])
                ->rows(3),

                Forms\Components\Select::make('file_type')
                    ->label('File Type / نوع الملف')
                    ->options([
                        'video' => 'Video / فيديو',
                        'document' => 'Document / مستند',
                        'image' => 'Image / صورة',
                    ])
                    ->default('video')
                    ->required()
                    ->rules(['required'])
                    ->validationMessages([
                        'required' => 'File type is required / نوع الملف مطلوب',
                    ])
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('attachment_video', null);
                        $set('attachment_document', null);
                        $set('attachment_image', null);
                    }),

            // Forms\Components\FileUpload::make('attachment')
            //     ->label('Upload File / رفع الملف')
            //     ->required()
            //     ->directory('guidelines/files')
            //     ->disk('public')
            //     ->downloadable()
            //     ->placeholder('Select file / اختر ملف')
            //     ->acceptedFileTypes(function (callable $get) {
            //         return match ($get('file_type')) {
            //             'video' => ['video/mp4', 'video/mov', 'video/avi', 'video/quicktime'],
            //             'document' => [
            //                 'application/pdf',
            //                 'application/msword',
            //                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            //             ],
            //             'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            //             default => [],
            //         };
            //     })
            //     // ->acceptedFileTypes([
            //     //     'video/mp4', 'video/mov', 'video/avi', 'video/quicktime',
            //     //     'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            //     //     'image/jpeg', 'image/png', 'image/gif', 'image/webp'
            //     // ])
            //     ->maxSize(25 * 1000) // 25MB
            //     ->helperText('Supported formats: Video (MP4, MOV, AVI), Document (PDF, DOC, DOCX), Image (JPEG, PNG, GIF, WebP). Max size: 25MB / التنسيقات المدعومة: فيديو (MP4، MOV، AVI)، مستند (PDF، DOC، DOCX)، صورة (JPEG، PNG، GIF، WebP). الحد الأقصى: 25 ميجابايت')
            //     ->validationMessages([
            //         'required' => 'File is required / الملف مطلوب',
            //         'mimes' => 'Unsupported file format / تنسيق الملف غير مدعوم',
            //         'max' => 'File size exceeds limit / حجم الملف يتجاوز الحد المسموح به',
            //     ])
            //     ->extraAttributes([
            //         'data-label-max-file-size' => 'Maximum file size is 25MB',
            //         'data-label-max-file-size-exceeded' => 'File is too large',
            //     ])
            //     ->rules([
            //         'required',
            //         'file',
            //         'max:25000', // 25MB in KB
            //     ])
            //     ->storeFileNamesIn('original_filename')
            //     ->visibility('public')
            //     ->reorderable()
            //     ->appendFiles(),
                Forms\Components\Group::make([
                    Forms\Components\FileUpload::make('attachment_video')
                        ->label('Upload Video / رفع الفيديو')
                        ->visible(fn (callable $get) => $get('file_type') === 'video')
                        ->required(fn (callable $get) => $get('file_type') === 'video')
                        ->directory('guidelines/files')
                        ->disk('public')
                        ->downloadable()
                        ->placeholder('Select video file / اختر ملف فيديو')
                        ->acceptedFileTypes(['video/mp4', 'video/mov', 'video/avi', 'video/quicktime'])
                        ->maxSize(25000) // 25MB
                        ->helperText('Supported formats: MP4, MOV, AVI, QuickTime. Max size: 25MB / التنسيقات المدعومة: MP4، MOV، AVI، QuickTime. الحد الأقصى: 25 ميجابايت')
                        ->validationMessages([
                            'required' => 'Video file is required / ملف الفيديو مطلوب',
                            'mimes' => 'Unsupported video format / تنسيق الفيديو غير مدعوم',
                            'max' => 'Video size exceeds limit / حجم الفيديو يتجاوز الحد المسموح به',
                        ])
                        ->extraAttributes([
                            'data-label-max-file-size' => 'Maximum file size is 25MB',
                            'data-label-max-file-size-exceeded' => 'File is too large',
                        ])
                        ->rules([
                            fn (callable $get) => $get('file_type') === 'video' ? 'required' : 'nullable',
                            'file',
                            'max:25000',
                        ])
                        ->storeFileNamesIn('original_filename')
                        ->visibility('public')
                        //->reorderable()
                        //->appendFiles()
                        ->default(function ($record, $get) {
                            // لو بعمل edit record (يعني عندي موديل محمّل)
                            if ($record && $record->file_type === 'video' && $record->attachment) {
                                return $record->attachment;
                            }
                        
                            // لو create ولسه شغال على form state
                            if ($get('file_type') === 'video' && $get('attachment')) {
                                return $get('attachment');
                            }
                        
                            return null;
                        }),
    
                    Forms\Components\FileUpload::make('attachment_document')
                        ->label('Upload Document / رفع المستند')
                        ->visible(fn (callable $get) => $get('file_type') === 'document')
                        ->required(fn (callable $get) => $get('file_type') === 'document')
                        ->directory('guidelines/files')
                        ->disk('public')
                        ->downloadable()
                        ->placeholder('Select document file / اختر ملف مستند')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(25 * 1000) // 25MB
                        ->helperText('Supported formats: PDF, DOC, DOCX. Max size: 25MB / التنسيقات المدعومة: PDF، DOC، DOCX. الحد الأقصى: 25 ميجابايت')
                        ->validationMessages([
                            'required' => 'Document file is required / ملف المستند مطلوب',
                            'mimes' => 'Unsupported document format / تنسيق المستند غير مدعوم',
                            'max' => 'Document size exceeds limit / حجم المستند يتجاوز الحد المسموح به',
                        ])
                        ->extraAttributes([
                            'data-label-max-file-size' => 'Maximum file size is 25MB',
                            'data-label-max-file-size-exceeded' => 'File is too large',
                        ])
                        ->rules([
                            fn (callable $get) => $get('file_type') === 'document' ? 'required' : 'nullable',
                            'file',
                            'max:25000',
                        ])
                        ->storeFileNamesIn('original_filename')
                        ->visibility('public')
                        //->reorderable()
                        //->appendFiles()
                        ->default(function ($record, $get) {
                            // لو بعمل edit record (يعني عندي موديل محمّل)
                            if ($record && $record->file_type === 'document' && $record->attachment) {
                                return $record->attachment;
                            }
                        
                            // لو create ولسه شغال على form state
                            if ($get('file_type') === 'document' && $get('attachment')) {
                                return $get('attachment');
                            }
                        
                            return null;
                        }),
    
                    Forms\Components\FileUpload::make('attachment_image')
                        ->label('Upload Image / رفع الصورة')
                        ->visible(fn (callable $get) => $get('file_type') === 'image')
                        ->required(fn (callable $get) => $get('file_type') === 'image')
                        ->directory('guidelines/files')
                        ->disk('public')
                        ->downloadable()
                        ->placeholder('Select image file / اختر ملف صورة')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->maxSize(25 * 1000) // 25MB
                        ->helperText('Supported formats: JPEG, PNG, GIF, WebP. Max size: 25MB / التنسيقات المدعومة: JPEG، PNG، GIF، WebP. الحد الأقصى: 25 ميجابايت')
                        ->validationMessages([
                            'required' => 'Image file is required / ملف الصورة مطلوب',
                            'mimes' => 'Unsupported image format / تنسيق الصورة غير مدعوم',
                            'max' => 'Image size exceeds limit / حجم الصورة يتجاوز الحد المسموح به',
                        ])
                        ->extraAttributes([
                            'data-label-max-file-size' => 'Maximum file size is 25MB',
                            'data-label-max-file-size-exceeded' => 'File is too large',
                        ])
                        ->rules([
                            fn (callable $get) => $get('file_type') === 'image' ? 'required' : 'nullable',
                            'file',
                            'max:25000',
                        ])
                        ->storeFileNamesIn('original_filename')
                        ->visibility('public')
                        //->reorderable()
                        //->appendFiles()
                        ->default(function ($record, $get) {
                            // لو بعمل edit record (يعني عندي موديل محمّل)
                            if ($record && $record->file_type === 'image' && $record->attachment) {
                                return [$record->attachment];
                            }
                        
                            // لو create ولسه شغال على form state
                            if ($get('file_type') === 'image' && $get('attachment')) {
                                return $get('attachment');
                            }
                        
                            return null;
                        })
                ]),
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->label('File Title / عنوان الملف')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('file_type')
                ->label('Type / النوع')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'video' => 'success',
                    'document' => 'info',
                    'image' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'video' => 'Video / فيديو',
                    'document' => 'Document / مستند',
                    'image' => 'Image / صورة',
                    default => $state,
                }),

            Tables\Columns\TextColumn::make('description')
                ->label('Description / الوصف')
                ->limit(50)
                ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                    $state = $column->getState();
                    if (strlen($state) <= 50) {
                        return null;
                    }
                    return $state;
                }),

            Tables\Columns\IconColumn::make('attachment')
                ->label('File / الملف')
                ->icon(fn($record) => match($record->file_type) {
                    'video' => 'heroicon-o-play',
                    'document' => 'heroicon-o-document-text',
                    'image' => 'heroicon-o-photo',
                    default => 'heroicon-o-arrow-down-tray',
                })
                ->url(fn($record) => Storage::url($record->attachment))
                ->openUrlInNewTab()
                ->tooltip(fn($record) => match($record->file_type) {
                    'video' => 'Play Video / تشغيل الفيديو',
                    'document' => 'Download Document / تحميل المستند',
                    'image' => 'View Image / عرض الصورة',
                    default => 'Download File / تحميل الملف',
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Created / تم الإنشاء')
                ->dateTime()
                ->searchable()
                ->sortable()
        ];
    }

    public static function details(): array
    {
        return [
            Section::make('File Details / تفاصيل الملف')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')
                        ->label('العنوان')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'ar')),
                    TextEntry::make('title')
                        ->label('File Title')
                        ->getStateUsing(fn($record) => $record->getTranslation('title', 'en')),
                    TextEntry::make('file_type')
                        ->label('File Type / نوع الملف')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'video' => 'success',
                            'document' => 'info',
                            'image' => 'warning',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'video' => 'Video / فيديو',
                            'document' => 'Document / مستند',
                            'image' => 'Image / صورة',
                            default => $state,
                        }),
                    TextEntry::make('description')
                        ->label('الوصف')
                        ->getStateUsing(fn($record) => $record->getTranslation('description', 'ar'))
                        ->columnSpan(2),
                    TextEntry::make('description')
                        ->label('File Description')
                        ->getStateUsing(fn($record) => $record->getTranslation('description', 'en'))
                        ->columnSpan(2),
                    TextEntry::make('attachment')
                        ->label('File URL / رابط الملف')
                        ->url(fn($record) => Storage::url($record->attachment))
                        ->openUrlInNewTab()
                        ->copyable()
                        ->columnSpan(2),
                ]),
        ];
    }

}
