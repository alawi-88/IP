<?php
/**
 * Create Kanz venture directly via Laravel internals, bypassing OTP.
 */

// Bootstrap Laravel
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Venture;
use App\Services\Ai\VentureGenerationService;

// Find participant user
$user = User::where('email', 'participant@test.com')->first();
if (!$user) {
    echo "ERROR: participant@test.com not found\n";
    exit(1);
}
echo "Found user: {$user->id} - {$user->email}\n";

// Create the Kanz venture
$venture = Venture::create([
    'user_id' => $user->id,
    'title' => 'Kanz',
    'idea_prompt' => 'A Saudi fintech app that teaches children financial literacy through savings accounts, debit cards, and task-based earning with parental controls. Kanz (كنز) is headquartered in Riyadh, Saudi Arabia at 6622 Al-Yasmin District. The app provides children aged 6-18 with their own savings accounts and prepaid debit cards, while parents can set tasks, approve spending, and monitor financial habits. Features include goal-based savings, instant money transfers between family members, educational financial content in Arabic, and gamified rewards for good financial habits. The business model is freemium with premium family subscriptions.',
    'industry' => 'FinTech / EdTech',
    'target_market' => 'Saudi Arabian families with children aged 6-18',
    'business_model' => 'Freemium with premium subscription',
    'status' => 'pending',
]);

echo "Created venture: ID={$venture->id}, Title={$venture->title}\n";

// Trigger generation
$service = new VentureGenerationService();
$service->generate($venture);

echo "Generation dispatched for venture {$venture->id}\n";

// Verify tabs and sections created
$tabs = $venture->tabs()->withCount('sections')->get();
echo "\nTabs created:\n";
foreach ($tabs as $tab) {
    echo "  [{$tab->slug}] {$tab->label_en} - {$tab->sections_count} sections\n";
}

$totalSections = $venture->tabs()->with('sections')->get()->flatMap(fn($t) => $t->sections)->count();
echo "\nTotal sections: {$totalSections}\n";
echo "Venture status: {$venture->fresh()->status}\n";
