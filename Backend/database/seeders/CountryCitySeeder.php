<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryCitySeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'country' => ['en' => 'Egypt', 'ar' => 'مصر'],
                'cities'  => [
                    ['en' => 'Cairo',            'ar' => 'القاهرة'],
                    ['en' => 'Alexandria',       'ar' => 'الإسكندرية'],
                    ['en' => 'Giza',             'ar' => 'الجيزة'],
                    ['en' => 'Shubra El-Kheima', 'ar' => 'شبرا الخيمة'],
                    ['en' => 'Port Said',        'ar' => 'بورسعيد'],
                    ['en' => 'Suez',             'ar' => 'السويس'],
                    ['en' => 'Mansoura',         'ar' => 'المنصورة'],
                    ['en' => 'Tanta',            'ar' => 'طنطا'],
                    ['en' => 'Aswan',            'ar' => 'أسوان'],
                    ['en' => 'Ismailia',         'ar' => 'الإسماعيلية'],
                ],
            ],
            [
                'country' => ['en' => 'Saudi Arabia', 'ar' => 'المملكة العربية السعودية'],
                'cities'  => [
                    ['en' => 'Riyadh',         'ar' => 'الرياض'],
                    ['en' => 'Jeddah',         'ar' => 'جدة'],
                    ['en' => 'Mecca',          'ar' => 'مكة'],
                    ['en' => 'Medina',         'ar' => 'المدينة المنورة'],
                    ['en' => 'Dammam',         'ar' => 'الدمام'],
                    ['en' => 'Taif',           'ar' => 'الطائف'],
                    ['en' => 'Tabuk',          'ar' => 'تبوك'],
                    ['en' => 'Buraidah',       'ar' => 'بريدة'],
                    ['en' => 'Khamis Mushait', 'ar' => 'خميس مشيط'],
                    ['en' => 'Abha',           'ar' => 'أبها'],
                ],
            ],
            [
                'country' => ['en' => 'United Arab Emirates', 'ar' => 'الإمارات العربية المتحدة'],
                'cities'  => [
                    ['en' => 'Dubai',            'ar' => 'دبي'],
                    ['en' => 'Abu Dhabi',        'ar' => 'أبوظبي'],
                    ['en' => 'Sharjah',          'ar' => 'الشارقة'],
                    ['en' => 'Ajman',            'ar' => 'عجمان'],
                    ['en' => 'Ras Al Khaimah',   'ar' => 'رأس الخيمة'],
                    ['en' => 'Fujairah',         'ar' => 'الفجيرة'],
                    ['en' => 'Al Ain',           'ar' => 'العين'],
                    ['en' => 'Umm Al Quwain',    'ar' => 'أم القيوين'],
                    ['en' => 'Kalba',            'ar' => 'كلباء'],
                    ['en' => 'Dibba',            'ar' => 'دبا'],
                ],
            ],
            [
                'country' => ['en' => 'Qatar', 'ar' => 'قطر'],
                'cities'  => [
                    ['en' => 'Doha',            'ar' => 'الدوحة'],
                    ['en' => 'Al Rayyan',       'ar' => 'الريان'],
                    ['en' => 'Al Wakrah',       'ar' => 'الوكرة'],
                    ['en' => 'Al Khor',         'ar' => 'الخور'],
                    ['en' => 'Umm Salal',       'ar' => 'أم صلال'],
                    ['en' => 'Mesaieed',        'ar' => 'مسعيد'],
                    ['en' => 'Dukhan',          'ar' => 'دوخان'],
                    ['en' => 'Al Shahaniya',    'ar' => 'الشحانية'],
                    ['en' => 'Al Daayen',       'ar' => 'الضيعة'],
                    ['en' => 'Al Shamal',       'ar' => 'الشمال'],
                ],
            ],
            [
                'country' => ['en' => 'Kuwait', 'ar' => 'الكويت'],
                'cities'  => [
                    ['en' => 'Kuwait City', 'ar' => 'مدينة الكويت'],
                    ['en' => 'Hawalli',     'ar' => 'حولي'],
                    ['en' => 'Salmiya',     'ar' => 'السالمية'],
                    ['en' => 'Farwaniya',   'ar' => 'الفروانية'],
                    ['en' => 'Jahra',       'ar' => 'الجهراء'],
                    ['en' => 'Ahmadi',      'ar' => 'الأحمدي'],
                    ['en' => 'Fahaheel',    'ar' => 'فحيحيل'],
                    ['en' => 'Mangaf',      'ar' => 'منقف'],
                    ['en' => 'Sabahiya',    'ar' => 'صباحية'],
                    ['en' => 'Mahboula',    'ar' => 'المحبولة'],
                ],
            ],
            [
                'country' => ['en' => 'Bahrain', 'ar' => 'البحرين'],
                'cities'  => [
                    ['en' => 'Manama',      'ar' => 'المنامة'],
                    ['en' => 'Riffa',       'ar' => 'الرفاع'],
                    ['en' => 'Muharraq',    'ar' => 'المحرق'],
                    ['en' => 'Isa Town',    'ar' => 'مدينة عيسى'],
                    ['en' => 'Sitra',       'ar' => 'سترة'],
                    ['en' => 'Hamad Town',  'ar' => 'مدينة حمد'],
                    ['en' => 'Budaiya',     'ar' => 'بديعة'],
                    ['en' => 'A\'ali',      'ar' => 'العلي'],
                    ['en' => 'Zallaq',      'ar' => 'زلاق'],
                    ['en' => 'Jidhafs',     'ar' => 'جيدفص'],
                ],
            ],
            [
                'country' => ['en' => 'Oman', 'ar' => 'عمان'],
                'cities'  => [
                    ['en' => 'Muscat',   'ar' => 'مسقط'],
                    ['en' => 'Salalah',  'ar' => 'صلالة'],
                    ['en' => 'Sohar',    'ar' => 'صحم'],
                    ['en' => 'Sur',      'ar' => 'صور'],
                    ['en' => 'Nizwa',    'ar' => 'نزوى'],
                    ['en' => 'Ibra',     'ar' => 'إبراء'],
                    ['en' => 'Rustaq',   'ar' => 'رستاق'],
                    ['en' => 'Seeb',     'ar' => 'سيب'],
                    ['en' => 'Barka',    'ar' => 'بركاء'],
                    ['en' => 'Shinas',   'ar' => 'شيناس'],
                ],
            ],
            [
                'country' => ['en' => 'Lebanon', 'ar' => 'لبنان'],
                'cities'  => [
                    ['en' => 'Beirut',   'ar' => 'بيروت'],
                    ['en' => 'Tripoli',  'ar' => 'طرابلس'],
                    ['en' => 'Sidon',    'ar' => 'صيدا'],
                    ['en' => 'Tyre',     'ar' => 'صور'],
                    ['en' => 'Byblos',   'ar' => 'جبيل'],
                    ['en' => 'Zahle',    'ar' => 'زحلة'],
                    ['en' => 'Baalbek',  'ar' => 'بعلبك'],
                    ['en' => 'Jounieh',  'ar' => 'جونية'],
                    ['en' => 'Batroun',  'ar' => 'بترون'],
                    ['en' => 'Nabatieh', 'ar' => 'النبطية'],
                ],
            ],
            [
                'country' => ['en' => 'Jordan', 'ar' => 'الأردن'],
                'cities'  => [
                    ['en' => 'Amman',    'ar' => 'عمان'],
                    ['en' => 'Zarqa',    'ar' => 'الزرقاء'],
                    ['en' => 'Irbid',    'ar' => 'إربد'],
                    ['en' => 'Aqaba',    'ar' => 'العقبة'],
                    ['en' => 'Madaba',   'ar' => 'مادبا'],
                    ['en' => 'Salt',     'ar' => 'السلط'],
                    ['en' => 'Jerash',   'ar' => 'جرش'],
                    ['en' => 'Maan',     'ar' => 'معان'],
                    ['en' => 'Karak',    'ar' => 'الكرك'],
                    ['en' => 'Tafileh',  'ar' => 'الطفيلة'],
                ],
            ],
            [
                'country' => ['en' => 'Morocco', 'ar' => 'المغرب'],
                'cities'  => [
                    ['en' => 'Casablanca', 'ar' => 'الدار البيضاء'],
                    ['en' => 'Rabat',      'ar' => 'الرباط'],
                    ['en' => 'Marrakech',  'ar' => 'مراكش'],
                    ['en' => 'Fes',        'ar' => 'فاس'],
                    ['en' => 'Tangier',    'ar' => 'طنجة'],
                    ['en' => 'Agadir',     'ar' => 'أكادير'],
                    ['en' => 'Oujda',      'ar' => 'وجدة'],
                    ['en' => 'Meknes',     'ar' => 'مكناس'],
                    ['en' => 'Tetouan',    'ar' => 'تطوان'],
                    ['en' => 'Essaouira',  'ar' => 'الصويرة'],
                ],
            ],
        ];

        foreach ($data as $entry) {
            // Insert country and get its ID
            $countryId = DB::table('countries')->insertGetId([
                'name' => json_encode($entry['country']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert the cities for the current country
            foreach ($entry['cities'] as $city) {
                DB::table('cities')->insert([
                    'country_id' => $countryId,
                    'name'       => json_encode($city),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
