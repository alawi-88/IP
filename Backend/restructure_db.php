<?php
/**
 * Restructure venture_section_configs, venture_tabs, venture_sections
 * to match the desired tab/section structure from screenshots.
 */

$pdo = new PDO('mysql:host=db;dbname=innovation;charset=utf8mb4', 'innovation', 'innovation');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== RESTRUCTURING TABS AND SECTIONS ===\n\n";

// Step 1: Clear existing venture_section_configs
echo "1. Clearing venture_section_configs...\n";
$pdo->exec("DELETE FROM venture_section_configs");
echo "   Done.\n\n";

// Step 2: Insert new venture_section_configs
echo "2. Inserting new venture_section_configs...\n";

$configs = [
    // Dashboard (tab sort 0)
    ['dashboard', 'dashboard_about', 'About', 'نبذة', 'information-circle', '#3B82F6', 'text_content', 1],
    ['dashboard', 'dashboard_swot', 'SWOT Analysis', 'تحليل SWOT', 'chart-bar-square', '#8B5CF6', 'swot_grid', 2],
    ['dashboard', 'dashboard_pestel', 'PESTEL Analysis', 'تحليل PESTEL', 'globe-alt', '#EC4899', 'pestel', 3],
    ['dashboard', 'dashboard_porters', "Porter's Five Forces", 'قوى بورتر الخمس', 'shield-check', '#F59E0B', 'key_value', 4],
    ['dashboard', 'dashboard_cage', 'CAGE Framework', 'إطار CAGE', 'cube', '#10B981', 'key_value', 5],
    ['dashboard', 'dashboard_viability', 'Viability Score', 'نقاط الجدوى', 'chart-pie', '#6366F1', 'viability_score', 6],
    ['dashboard', 'dashboard_market_size', 'Market Size', 'حجم السوق', 'presentation-chart-bar', '#0EA5E9', 'stat_cards', 7],
    ['dashboard', 'dashboard_industry_insight', 'Industry Insight', 'رؤية الصناعة', 'light-bulb', '#F97316', 'text_content', 8],

    // Strategic Frameworks (tab sort 1)
    ['strategic_frameworks', 'sf_ip_strategy', 'IP Strategy', 'استراتيجية الملكية الفكرية', 'lock-closed', '#8B5CF6', 'text_content', 1],
    ['strategic_frameworks', 'sf_swot', 'SWOT Analysis', 'تحليل SWOT', 'chart-bar-square', '#3B82F6', 'swot_grid', 2],
    ['strategic_frameworks', 'sf_pestel', 'PESTEL Analysis', 'تحليل PESTEL', 'globe-alt', '#EC4899', 'pestel', 3],
    ['strategic_frameworks', 'sf_porters', "Porter's Five Forces", 'قوى بورتر الخمس', 'shield-check', '#F59E0B', 'key_value', 4],
    ['strategic_frameworks', 'sf_cage', 'CAGE Framework', 'إطار CAGE', 'cube', '#10B981', 'key_value', 5],

    // Path to MVP (tab sort 2)
    ['path_to_mvp', 'mvp_definition', 'MVP Definition', 'تعريف المنتج الأولي', 'clipboard-document-check', '#3B82F6', 'text_content', 1],
    ['path_to_mvp', 'mvp_technical_architecture', 'Technical Architecture', 'البنية التقنية', 'cpu-chip', '#8B5CF6', 'tech_architecture', 2],
    ['path_to_mvp', 'mvp_development_roadmap', 'Development Roadmap', 'خارطة طريق التطوير', 'map', '#10B981', 'journey_timeline', 3],
    ['path_to_mvp', 'mvp_risks_mitigations', 'Key Risks & Mitigations', 'المخاطر الرئيسية والتخفيف', 'exclamation-triangle', '#EF4444', 'comparison_table', 4],

    // Unique Selling Points (tab sort 3)
    ['unique_selling_points', 'usp_overview', 'USP Overview', 'نظرة عامة على نقاط البيع الفريدة', 'star', '#F59E0B', 'text_content', 1],
    ['unique_selling_points', 'usp_differentiators', 'Key Differentiators', 'عوامل التميز الرئيسية', 'sparkles', '#8B5CF6', 'stat_cards', 2],
    ['unique_selling_points', 'usp_competitive_comparison', 'Competitive Comparison', 'المقارنة التنافسية', 'scale', '#3B82F6', 'comparison_table', 3],

    // Customer Persona (tab sort 4)
    ['customer_persona', 'persona_primary', 'Primary Persona', 'الشخصية الأساسية', 'user', '#3B82F6', 'persona_card', 1],
    ['customer_persona', 'persona_secondary', 'Secondary Persona', 'الشخصية الثانوية', 'users', '#10B981', 'persona_card', 2],
    ['customer_persona', 'persona_buyer_journey', 'Buyer Journey', 'رحلة المشتري', 'arrow-trending-up', '#F59E0B', 'journey_timeline', 3],

    // Finances (tab sort 5)
    ['finances', 'fin_revenue_model', 'Revenue Model', 'نموذج الإيرادات', 'banknotes', '#10B981', 'text_content', 1],
    ['finances', 'fin_projections', 'Financial Projections (Year 1)', 'التوقعات المالية (السنة 1)', 'chart-bar', '#3B82F6', 'comparison_table', 2],
    ['finances', 'fin_cost_structure', 'Cost Structure', 'هيكل التكاليف', 'calculator', '#F59E0B', 'comparison_table', 3],
    ['finances', 'fin_funding_strategy', 'Funding Strategy', 'استراتيجية التمويل', 'currency-dollar', '#8B5CF6', 'text_content', 4],
    ['finances', 'fin_key_metrics', 'Key Financial Metrics', 'المقاييس المالية الرئيسية', 'presentation-chart-line', '#EC4899', 'stat_cards', 5],

    // Go-to-Market Strategy (tab sort 6)
    ['go_to_market', 'gtm_strategy', 'Go-to-Market Strategy', 'استراتيجية الذهاب إلى السوق', 'rocket-launch', '#3B82F6', 'text_content', 1],
    ['go_to_market', 'gtm_launch_plan', 'Launch Plan', 'خطة الإطلاق', 'calendar-days', '#10B981', 'journey_timeline', 2],
    ['go_to_market', 'gtm_partnerships', 'Key Partnerships', 'الشراكات الرئيسية', 'link', '#F59E0B', 'stat_cards', 3],

    // Competitive Analysis VRIO (tab sort 7)
    ['competitive_analysis_vrio', 'vrio_analysis', 'VRIO Analysis', 'تحليل VRIO', 'table-cells', '#3B82F6', 'comparison_table', 1],
    ['competitive_analysis_vrio', 'vrio_resources', 'Resource Assessment', 'تقييم الموارد', 'cube-transparent', '#8B5CF6', 'stat_cards', 2],
    ['competitive_analysis_vrio', 'vrio_advantages', 'Competitive Advantages', 'المزايا التنافسية', 'trophy', '#F59E0B', 'text_content', 3],
];

$stmt = $pdo->prepare("INSERT INTO venture_section_configs (tab_slug, section_slug, label_en, label_ar, icon, color, component_type, sort_order, is_visible, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");

foreach ($configs as $c) {
    $stmt->execute($c);
    echo "   Inserted: {$c[0]} / {$c[1]} ({$c[2]})\n";
}
echo "   Total: " . count($configs) . " configs inserted.\n\n";

// Step 3: Delete old venture_tabs and venture_sections for venture 5
echo "3. Cleaning up venture 5 old data...\n";
// Get old tab IDs
$oldTabs = $pdo->query("SELECT id FROM venture_tabs WHERE venture_id = 5")->fetchAll(PDO::FETCH_COLUMN);
if ($oldTabs) {
    $ids = implode(',', $oldTabs);
    $pdo->exec("DELETE FROM venture_sections WHERE venture_tab_id IN ($ids)");
    echo "   Deleted old sections for venture 5.\n";
    $pdo->exec("DELETE FROM venture_tabs WHERE venture_id = 5");
    echo "   Deleted old tabs for venture 5.\n";
}
echo "\n";

// Step 4: Create new venture_tabs for venture 5
echo "4. Creating new venture_tabs for venture 5...\n";
$tabDefs = [
    ['dashboard', 'Dashboard', 'لوحة المعلومات', 'squares-2x2', 0],
    ['strategic_frameworks', 'Strategic Frameworks', 'الأطر الاستراتيجية', 'academic-cap', 1],
    ['path_to_mvp', 'Path to MVP', 'المسار نحو المنتج الأولي', 'rocket-launch', 2],
    ['unique_selling_points', 'Unique Selling Points', 'نقاط البيع الفريدة', 'star', 3],
    ['customer_persona', 'Customer Persona', 'شخصية العميل', 'user-group', 4],
    ['finances', 'Finances', 'المالية', 'banknotes', 5],
    ['go_to_market', 'Go-to-Market Strategy', 'استراتيجية الذهاب إلى السوق', 'megaphone', 6],
    ['competitive_analysis_vrio', 'Competitive Analysis (VRIO)', 'التحليل التنافسي (VRIO)', 'chart-bar', 7],
];

$tabStmt = $pdo->prepare("INSERT INTO venture_tabs (venture_id, slug, label_en, label_ar, icon, sort_order, is_visible, created_at, updated_at) VALUES (5, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
$tabIds = [];
foreach ($tabDefs as $t) {
    $tabStmt->execute($t);
    $tabIds[$t[0]] = $pdo->lastInsertId();
    echo "   Created tab: {$t[1]} (id={$tabIds[$t[0]]})\n";
}
echo "\n";

// Step 5: Create venture_sections for venture 5 (pending status, no content)
echo "5. Creating venture_sections for venture 5...\n";
$secStmt = $pdo->prepare("INSERT INTO venture_sections (venture_id, venture_tab_id, slug, label_en, label_ar, component_type, sort_order, is_visible, status, generation_attempts, created_at, updated_at) VALUES (5, ?, ?, ?, ?, ?, ?, 1, 'pending', 0, NOW(), NOW())");

$sectionCount = 0;
foreach ($configs as $c) {
    $tabSlug = $c[0];
    $sectionSlug = $c[1];
    $labelEn = $c[2];
    $labelAr = $c[3];
    $componentType = $c[6];
    $sortOrder = $c[7];
    $tabId = $tabIds[$tabSlug];

    $secStmt->execute([$tabId, $sectionSlug, $labelEn, $labelAr, $componentType, $sortOrder]);
    $sectionCount++;
    echo "   Created section: {$sectionSlug} in tab {$tabSlug}\n";
}
echo "   Total: {$sectionCount} sections created.\n\n";

echo "=== RESTRUCTURING COMPLETE ===\n";
echo "New structure: " . count($tabDefs) . " tabs, {$sectionCount} sections\n";

// Verify
echo "\n=== VERIFICATION ===\n";
$rows = $pdo->query("SELECT vt.label_en as tab, vs.slug, vs.label_en, vs.component_type, vs.sort_order FROM venture_tabs vt JOIN venture_sections vs ON vs.venture_tab_id = vt.id WHERE vt.venture_id = 5 ORDER BY vt.sort_order, vs.sort_order");
foreach ($rows as $r) {
    echo "  [{$r['tab']}] {$r['slug']} - {$r['label_en']} ({$r['component_type']})\n";
}
