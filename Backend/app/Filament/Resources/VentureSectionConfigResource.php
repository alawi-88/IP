<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureSectionConfigResource\Pages;
use App\Models\VentureSectionConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VentureSectionConfigResource extends Resource
{
    protected static ?string $model = VentureSectionConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Section Builder';

    /**
     * Get the list of available Heroicon outline icons for the icon picker.
     */
    public static function getHeroiconOutlineOptions(): array
    {
        return [
            'academic-cap' => 'Academic Cap',
            'adjustments-horizontal' => 'Adjustments Horizontal',
            'adjustments-vertical' => 'Adjustments Vertical',
            'archive-box' => 'Archive Box',
            'archive-box-arrow-down' => 'Archive Box Arrow Down',
            'arrow-down' => 'Arrow Down',
            'arrow-down-circle' => 'Arrow Down Circle',
            'arrow-down-tray' => 'Arrow Down Tray',
            'arrow-left' => 'Arrow Left',
            'arrow-left-circle' => 'Arrow Left Circle',
            'arrow-long-down' => 'Arrow Long Down',
            'arrow-long-left' => 'Arrow Long Left',
            'arrow-long-right' => 'Arrow Long Right',
            'arrow-long-up' => 'Arrow Long Up',
            'arrow-path' => 'Arrow Path',
            'arrow-right' => 'Arrow Right',
            'arrow-right-circle' => 'Arrow Right Circle',
            'arrow-top-right-on-square' => 'Arrow Top Right On Square',
            'arrow-trending-down' => 'Arrow Trending Down',
            'arrow-trending-up' => 'Arrow Trending Up',
            'arrow-up' => 'Arrow Up',
            'arrow-up-circle' => 'Arrow Up Circle',
            'arrow-up-tray' => 'Arrow Up Tray',
            'arrow-uturn-left' => 'Arrow U-Turn Left',
            'arrow-uturn-right' => 'Arrow U-Turn Right',
            'arrows-pointing-in' => 'Arrows Pointing In',
            'arrows-pointing-out' => 'Arrows Pointing Out',
            'arrows-right-left' => 'Arrows Right Left',
            'arrows-up-down' => 'Arrows Up Down',
            'at-symbol' => 'At Symbol',
            'backspace' => 'Backspace',
            'banknotes' => 'Banknotes',
            'bars-2' => 'Bars 2',
            'bars-3' => 'Bars 3',
            'bars-3-bottom-left' => 'Bars 3 Bottom Left',
            'bars-3-bottom-right' => 'Bars 3 Bottom Right',
            'bars-4' => 'Bars 4',
            'battery-0' => 'Battery 0%',
            'battery-50' => 'Battery 50%',
            'battery-100' => 'Battery 100%',
            'beaker' => 'Beaker',
            'bell' => 'Bell',
            'bell-alert' => 'Bell Alert',
            'bell-slash' => 'Bell Slash',
            'bolt' => 'Bolt',
            'bolt-slash' => 'Bolt Slash',
            'book-open' => 'Book Open',
            'bookmark' => 'Bookmark',
            'bookmark-slash' => 'Bookmark Slash',
            'bookmark-square' => 'Bookmark Square',
            'briefcase' => 'Briefcase',
            'bug-ant' => 'Bug Ant',
            'building-library' => 'Building Library',
            'building-office' => 'Building Office',
            'building-office-2' => 'Building Office 2',
            'building-storefront' => 'Building Storefront',
            'cake' => 'Cake',
            'calculator' => 'Calculator',
            'calendar' => 'Calendar',
            'calendar-days' => 'Calendar Days',
            'camera' => 'Camera',
            'chart-bar' => 'Chart Bar',
            'chart-bar-square' => 'Chart Bar Square',
            'chart-pie' => 'Chart Pie',
            'chat-bubble-bottom-center' => 'Chat Bubble Bottom Center',
            'chat-bubble-bottom-center-text' => 'Chat Bubble Bottom Center Text',
            'chat-bubble-left' => 'Chat Bubble Left',
            'chat-bubble-left-ellipsis' => 'Chat Bubble Left Ellipsis',
            'chat-bubble-left-right' => 'Chat Bubble Left Right',
            'chat-bubble-oval-left' => 'Chat Bubble Oval Left',
            'chat-bubble-oval-left-ellipsis' => 'Chat Bubble Oval Left Ellipsis',
            'check' => 'Check',
            'check-badge' => 'Check Badge',
            'check-circle' => 'Check Circle',
            'chevron-double-down' => 'Chevron Double Down',
            'chevron-double-left' => 'Chevron Double Left',
            'chevron-double-right' => 'Chevron Double Right',
            'chevron-double-up' => 'Chevron Double Up',
            'chevron-down' => 'Chevron Down',
            'chevron-left' => 'Chevron Left',
            'chevron-right' => 'Chevron Right',
            'chevron-up' => 'Chevron Up',
            'chevron-up-down' => 'Chevron Up Down',
            'circle-stack' => 'Circle Stack',
            'clipboard' => 'Clipboard',
            'clipboard-document' => 'Clipboard Document',
            'clipboard-document-check' => 'Clipboard Document Check',
            'clipboard-document-list' => 'Clipboard Document List',
            'clock' => 'Clock',
            'cloud' => 'Cloud',
            'cloud-arrow-down' => 'Cloud Arrow Down',
            'cloud-arrow-up' => 'Cloud Arrow Up',
            'code-bracket' => 'Code Bracket',
            'code-bracket-square' => 'Code Bracket Square',
            'cog' => 'Cog',
            'cog-6-tooth' => 'Cog 6 Tooth',
            'cog-8-tooth' => 'Cog 8 Tooth',
            'command-line' => 'Command Line',
            'computer-desktop' => 'Computer Desktop',
            'cpu-chip' => 'CPU Chip',
            'credit-card' => 'Credit Card',
            'cube' => 'Cube',
            'cube-transparent' => 'Cube Transparent',
            'currency-bangladeshi' => 'Currency Bangladeshi',
            'currency-dollar' => 'Currency Dollar',
            'currency-euro' => 'Currency Euro',
            'currency-pound' => 'Currency Pound',
            'currency-rupee' => 'Currency Rupee',
            'currency-yen' => 'Currency Yen',
            'cursor-arrow-rays' => 'Cursor Arrow Rays',
            'cursor-arrow-ripple' => 'Cursor Arrow Ripple',
            'device-phone-mobile' => 'Device Phone Mobile',
            'device-tablet' => 'Device Tablet',
            'document' => 'Document',
            'document-arrow-down' => 'Document Arrow Down',
            'document-arrow-up' => 'Document Arrow Up',
            'document-chart-bar' => 'Document Chart Bar',
            'document-check' => 'Document Check',
            'document-duplicate' => 'Document Duplicate',
            'document-magnifying-glass' => 'Document Magnifying Glass',
            'document-minus' => 'Document Minus',
            'document-plus' => 'Document Plus',
            'document-text' => 'Document Text',
            'ellipsis-horizontal' => 'Ellipsis Horizontal',
            'ellipsis-horizontal-circle' => 'Ellipsis Horizontal Circle',
            'ellipsis-vertical' => 'Ellipsis Vertical',
            'envelope' => 'Envelope',
            'envelope-open' => 'Envelope Open',
            'exclamation-circle' => 'Exclamation Circle',
            'exclamation-triangle' => 'Exclamation Triangle',
            'eye' => 'Eye',
            'eye-dropper' => 'Eye Dropper',
            'eye-slash' => 'Eye Slash',
            'face-frown' => 'Face Frown',
            'face-smile' => 'Face Smile',
            'film' => 'Film',
            'finger-print' => 'Finger Print',
            'fire' => 'Fire',
            'flag' => 'Flag',
            'folder' => 'Folder',
            'folder-arrow-down' => 'Folder Arrow Down',
            'folder-minus' => 'Folder Minus',
            'folder-open' => 'Folder Open',
            'folder-plus' => 'Folder Plus',
            'forward' => 'Forward',
            'funnel' => 'Funnel',
            'gif' => 'GIF',
            'gift' => 'Gift',
            'gift-top' => 'Gift Top',
            'globe-alt' => 'Globe Alt',
            'globe-americas' => 'Globe Americas',
            'globe-asia-australia' => 'Globe Asia Australia',
            'globe-europe-africa' => 'Globe Europe Africa',
            'hand-raised' => 'Hand Raised',
            'hand-thumb-down' => 'Hand Thumb Down',
            'hand-thumb-up' => 'Hand Thumb Up',
            'hashtag' => 'Hashtag',
            'heart' => 'Heart',
            'home' => 'Home',
            'home-modern' => 'Home Modern',
            'identification' => 'Identification',
            'inbox' => 'Inbox',
            'inbox-arrow-down' => 'Inbox Arrow Down',
            'inbox-stack' => 'Inbox Stack',
            'information-circle' => 'Information Circle',
            'key' => 'Key',
            'language' => 'Language',
            'lifebuoy' => 'Lifebuoy',
            'light-bulb' => 'Light Bulb',
            'link' => 'Link',
            'list-bullet' => 'List Bullet',
            'lock-closed' => 'Lock Closed',
            'lock-open' => 'Lock Open',
            'magnifying-glass' => 'Magnifying Glass',
            'magnifying-glass-circle' => 'Magnifying Glass Circle',
            'magnifying-glass-minus' => 'Magnifying Glass Minus',
            'magnifying-glass-plus' => 'Magnifying Glass Plus',
            'map' => 'Map',
            'map-pin' => 'Map Pin',
            'megaphone' => 'Megaphone',
            'microphone' => 'Microphone',
            'minus' => 'Minus',
            'minus-circle' => 'Minus Circle',
            'moon' => 'Moon',
            'musical-note' => 'Musical Note',
            'newspaper' => 'Newspaper',
            'no-symbol' => 'No Symbol',
            'paint-brush' => 'Paint Brush',
            'paper-airplane' => 'Paper Airplane',
            'paper-clip' => 'Paper Clip',
            'pause' => 'Pause',
            'pause-circle' => 'Pause Circle',
            'pencil' => 'Pencil',
            'pencil-square' => 'Pencil Square',
            'percent-badge' => 'Percent Badge',
            'phone' => 'Phone',
            'phone-arrow-down-left' => 'Phone Arrow Down Left',
            'phone-arrow-up-right' => 'Phone Arrow Up Right',
            'phone-x-mark' => 'Phone X Mark',
            'photo' => 'Photo',
            'play' => 'Play',
            'play-circle' => 'Play Circle',
            'play-pause' => 'Play Pause',
            'plus' => 'Plus',
            'plus-circle' => 'Plus Circle',
            'power' => 'Power',
            'presentation-chart-bar' => 'Presentation Chart Bar',
            'presentation-chart-line' => 'Presentation Chart Line',
            'printer' => 'Printer',
            'puzzle-piece' => 'Puzzle Piece',
            'qr-code' => 'QR Code',
            'question-mark-circle' => 'Question Mark Circle',
            'queue-list' => 'Queue List',
            'radio' => 'Radio',
            'receipt-percent' => 'Receipt Percent',
            'receipt-refund' => 'Receipt Refund',
            'rectangle-group' => 'Rectangle Group',
            'rectangle-stack' => 'Rectangle Stack',
            'rocket-launch' => 'Rocket Launch',
            'rss' => 'RSS',
            'scale' => 'Scale',
            'scissors' => 'Scissors',
            'server' => 'Server',
            'server-stack' => 'Server Stack',
            'share' => 'Share',
            'shield-check' => 'Shield Check',
            'shield-exclamation' => 'Shield Exclamation',
            'shopping-bag' => 'Shopping Bag',
            'shopping-cart' => 'Shopping Cart',
            'signal' => 'Signal',
            'signal-slash' => 'Signal Slash',
            'sparkles' => 'Sparkles',
            'speaker-wave' => 'Speaker Wave',
            'speaker-x-mark' => 'Speaker X Mark',
            'square-2-stack' => 'Square 2 Stack',
            'square-3-stack-3d' => 'Square 3 Stack 3D',
            'squares-2x2' => 'Squares 2x2',
            'squares-plus' => 'Squares Plus',
            'star' => 'Star',
            'stop' => 'Stop',
            'stop-circle' => 'Stop Circle',
            'sun' => 'Sun',
            'swatch' => 'Swatch',
            'table-cells' => 'Table Cells',
            'tag' => 'Tag',
            'ticket' => 'Ticket',
            'trash' => 'Trash',
            'trophy' => 'Trophy',
            'truck' => 'Truck',
            'tv' => 'TV',
            'user' => 'User',
            'user-circle' => 'User Circle',
            'user-group' => 'User Group',
            'user-minus' => 'User Minus',
            'user-plus' => 'User Plus',
            'users' => 'Users',
            'variable' => 'Variable',
            'video-camera' => 'Video Camera',
            'video-camera-slash' => 'Video Camera Slash',
            'view-columns' => 'View Columns',
            'viewfinder-circle' => 'Viewfinder Circle',
            'wallet' => 'Wallet',
            'wifi' => 'Wifi',
            'window' => 'Window',
            'wrench' => 'Wrench',
            'wrench-screwdriver' => 'Wrench Screwdriver',
            'x-circle' => 'X Circle',
            'x-mark' => 'X Mark',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Section Identity')
                    ->description('Define the section slug, labels, and tab placement')
                    ->schema([
                        Forms\Components\TextInput::make('section_slug')
                            ->required()
                            ->unique('venture_section_configs', 'section_slug', ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(['sm' => 2, 'lg' => 1]),

                        Forms\Components\Select::make('tab_slug')
                            ->required()
                            ->options(fn () => \App\Models\VentureTabConfig::ordered()->pluck('label_en', 'tab_slug')->toArray())
                            ->searchable()
                            ->columnSpan(['sm' => 2, 'lg' => 1]),

                        Forms\Components\TextInput::make('label_en')
                            ->label('Label (English)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['sm' => 2, 'lg' => 1]),

                        Forms\Components\TextInput::make('label_ar')
                            ->label('Label (Arabic)')
                            ->maxLength(255)
                            ->columnSpan(['sm' => 2, 'lg' => 1]),
                    ])->columns(2),

                Forms\Components\Section::make('Appearance')
                    ->description('Configure visual appearance of the section')
                    ->schema([
                        Forms\Components\Select::make('icon')
                            ->label('Icon')
                            ->searchable()
                            ->preload()
                            ->allowHtml()
                            ->getSearchResultsUsing(function (string $search): array {
                                $icons = static::getHeroiconOutlineOptions();
                                $results = [];
                                $count = 0;
                                foreach ($icons as $key => $label) {
                                    if ($count >= 30) break;
                                    if (empty($search) || stripos($label, $search) !== false || stripos($key, $search) !== false) {
                                        try {
                                            $iconSvg = svg('heroicon-o-' . $key, 'w-4 h-4 inline-block mr-1')->toHtml();
                                        } catch (\Exception $e) {
                                            $iconSvg = '';
                                        }
                                        $results[$key] = $iconSvg . e($label);
                                        $count++;
                                    }
                                }
                                return $results;
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (!$value) return null;
                                $icons = static::getHeroiconOutlineOptions();
                                $label = $icons[$value] ?? ucwords(str_replace('-', ' ', $value));
                                try {
                                    $iconSvg = svg('heroicon-o-' . $value, 'w-5 h-5 inline-block mr-1')->toHtml();
                                } catch (\Exception $e) {
                                    $iconSvg = '';
                                }
                                return $iconSvg . e($label);
                            })
                            ->helperText('Search for an icon by name (e.g. "rocket", "chart", "user")')
                            ->columnSpan(['sm' => 2, 'lg' => 1]),

                        Forms\Components\ColorPicker::make('color')
                            ->columnSpan(['sm' => 2, 'lg' => 1]),

                        Forms\Components\Select::make('component_type')
                            ->required()
                            ->options([
                                'Data Display' => [
                                    'text_content' => '📝 Text Content — Paragraphs, rich text, descriptions',
                                    'stat_cards' => '📊 Stat Cards — Key metrics with labels and values',
                                    'key_value' => '🔑 Key Value — Label-value pairs in a list',
                                    'progress_bars' => '📈 Progress Bars — Percentage-based bar charts',
                                ],
                                'Analysis & Frameworks' => [
                                    'swot_grid' => '🎯 SWOT Grid — Strengths, Weaknesses, Opportunities, Threats',
                                    'risk_matrix' => '⚠️ Risk Matrix — Risk items with severity levels',
                                    'comparison_table' => '📋 Comparison Table — Multi-column data comparison',
                                    'viability_score' => '🏆 Viability Score — Score with breakdown categories',
                                    'pestel' => '🌍 PESTEL — Political, Economic, Social, Tech, Environmental, Legal',
                                ],
                                'Visual & Timeline' => [
                                    'timeline' => '📅 Timeline — Stage-based timeline with actions',
                                    'journey_timeline' => '🗺️ Journey Timeline — Journey stages with milestones',
                                    'funnel_chart' => '🔽 Funnel Chart — Conversion funnel stages',
                                ],
                                'Business & Strategy' => [
                                    'persona_cards' => '👤 Persona Cards — Customer personas with goals/pain points',
                                    'pricing_cards' => '💰 Pricing Cards — Pricing tiers and plans',
                                    'funding_strategy' => '🏦 Funding Strategy — Funding rounds and targets',
                                    'growth_channels' => '📣 Growth Channels — Marketing channels with metrics',
                                    'partnerships' => '🤝 Partnerships — Partnership types and details',
                                    'differentiators' => '💎 Differentiators — Competitive advantages',
                                ],
                                'Technical & Planning' => [
                                    'tech_architecture' => '💻 Tech Architecture — Technology stack breakdown',
                                    'development_roadmap' => '🗺️ Development Roadmap — Phased development plan',
                                    'launch_plan' => '🚀 Launch Plan — Launch phases with tasks',
                                    'mvp_definition' => '🎯 MVP Definition — Core concept with must-have features',
                                    'milestones' => '🏁 Milestones — Revenue projections and milestones',
                                ],
                                'Layout' => [
                                    'canvas_grid' => '🧩 Canvas Grid — Business model canvas layout',
                                    'cost_table' => '💵 Cost Table — Cost breakdown table',
                                    'line_chart' => '📉 Line Chart — Trend data visualization',
                                ],
                            ])
                            ->searchable()
                            ->helperText('Determines how the AI-generated content is displayed to participants')
                            ->columnSpan(['sm' => 2, 'lg' => 2]),
                    ])->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->integer()
                            ->default(0)
                            ->columnSpan(['sm' => 2, 'lg' => 1]),

                        Forms\Components\Toggle::make('is_visible')
                            ->default(true)
                            ->columnSpan(['sm' => 2, 'lg' => 1]),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentureSectionConfigs::route('/'),
            'create' => Pages\CreateVentureSectionConfig::route('/create'),
            'edit' => Pages\EditVentureSectionConfig::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view VentureSectionConfig') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create VentureSectionConfig') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update VentureSectionConfig') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete VentureSectionConfig') ?? false;
    }
}
