<?php
// Seed mentors for Transportation Sandbox 2025
try {
    $mentors = [
        [
            'name' => json_encode(['ar' => 'فهد الراشد', 'en' => 'Fahad Al-Rashid']),
            'email' => 'fahad.mentor@test.com',
            'phone' => '+966501234567',
            'password' => password_hash('Password1!@#', PASSWORD_BCRYPT),
            'experience' => json_encode(['ar' => '15 سنة في تنظيم النقل والمواصلات', 'en' => '15 years in transport regulation']),
            'brief' => json_encode(['ar' => 'خبير في تنظيم قطاع النقل وتطوير السياسات', 'en' => 'Expert in transport sector regulation and policy development']),
            'profession' => json_encode(['ar' => 'مستشار تنظيم النقل', 'en' => 'Transport Regulation Consultant']),
            'status' => 'active',
            'linkedin' => 'https://linkedin.com/in/fahad-rashid',
        ],
        [
            'name' => json_encode(['ar' => 'سارة المطيري', 'en' => 'Sarah Al-Mutairi']),
            'email' => 'sarah.mobility@test.com',
            'phone' => '+966509876543',
            'password' => password_hash('Password1!@#', PASSWORD_BCRYPT),
            'experience' => json_encode(['ar' => '10 سنوات في التنقل الذكي والمركبات الكهربائية', 'en' => '10 years in smart mobility and electric vehicles']),
            'brief' => json_encode(['ar' => 'متخصصة في حلول التنقل المستدام', 'en' => 'Specialist in sustainable mobility solutions']),
            'profession' => json_encode(['ar' => 'مديرة التنقل الذكي', 'en' => 'Smart Mobility Director']),
            'status' => 'active',
            'linkedin' => 'https://linkedin.com/in/sarah-mutairi',
        ],
        [
            'name' => json_encode(['ar' => 'خالد العمري', 'en' => 'Khalid Al-Omari']),
            'email' => 'khalid.ops@test.com',
            'phone' => '+966505551234',
            'password' => password_hash('Password1!@#', PASSWORD_BCRYPT),
            'experience' => json_encode(['ar' => '12 سنة في إدارة عمليات النقل', 'en' => '12 years in transport operations management']),
            'brief' => json_encode(['ar' => 'خبير في الكفاءة التشغيلية وإدارة الأساطيل', 'en' => 'Expert in operational efficiency and fleet management']),
            'profession' => json_encode(['ar' => 'مدير العمليات التشغيلية', 'en' => 'Operations Manager']),
            'status' => 'active',
            'linkedin' => 'https://linkedin.com/in/khalid-omari',
        ],
    ];

    // Use Laravel's DB facade
    foreach ($mentors as $m) {
        \Illuminate\Support\Facades\DB::table('mentors')->insert(array_merge($m, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    $mentorIds = \Illuminate\Support\Facades\DB::table('mentors')
        ->whereIn('email', ['fahad.mentor@test.com', 'sarah.mobility@test.com', 'khalid.ops@test.com'])
        ->pluck('id');

    foreach ($mentorIds as $mid) {
        \Illuminate\Support\Facades\DB::table('mentor_competitions')->insert([
            'mentor_id' => $mid,
            'competition_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    echo "SUCCESS: Created " . count($mentorIds) . " mentors linked to competition 3. IDs: " . $mentorIds->implode(', ') . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
