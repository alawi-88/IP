#!/usr/bin/env python3
"""
Comprehensive script to replace all 'competition' terminology with 'program'
across the entire codebase (backend PHP + frontend TypeScript).

Run AFTER file renames are complete.
"""

import os
import sys

# ─────────────────────────────────────────────────
# PHP / Blade replacement rules (ordered: most specific first)
# ─────────────────────────────────────────────────
PHP_REPLACEMENTS = [
    # ── Compound class names (must come before simple 'Competition') ──
    ('CompetitionApplicationStatus',                'ProgramApplicationStatus'),
    ('CompetitionApplicationScope',                 'ProgramApplicationScope'),
    ('CompetitionApplicationResource',              'ProgramApplicationResource'),
    ('CompetitionApplicationExporter',              'ProgramApplicationExporter'),
    ('CompetitionApplicationImporter',              'ProgramApplicationImporter'),
    ('DynamicCompetitionApplicationImporter',       'DynamicProgramApplicationImporter'),
    ('ListCompetitionApplications',                 'ListProgramApplications'),
    ('ViewCompetitionApplication',                  'ViewProgramApplication'),
    ('CompetitionApplication',                      'ProgramApplication'),

    ('BrandingCompetitionResource',                 'BrandingProgramResource'),
    ('CreateBrandingCompetition',                   'CreateBrandingProgram'),
    ('EditBrandingCompetition',                     'EditBrandingProgram'),
    ('ListBrandingCompetitions',                    'ListBrandingPrograms'),
    ('BrandingCompetition',                         'BrandingProgram'),

    ('UserCompetition',                             'UserProgram'),
    ('MentorCompetition',                           'MentorProgram'),

    ('CompetitionsRelationManager',                 'ProgramsRelationManager'),
    ('CompetitionResource',                         'ProgramResource'),
    ('CompetitionExporter',                         'ProgramExporter'),
    ('CompetitionStatistics',                       'ProgramStatistics'),
    ('CompetitionStatsCount',                       'ProgramStatsCount'),
    ('CompetitionSubTrackPercentageStats',          'ProgramSubTrackPercentageStats'),
    ('CompetitionTrackPercentageStats',             'ProgramTrackPercentageStats'),

    ('CreateCompetitionTabs',                       'CreateProgramTabs'),
    ('CreateCompetitionStages',                     'CreateProgramStages'),
    ('HandleFormCompetitionStagesCreated',          'HandleFormProgramStagesCreated'),
    ('FormCompetitionStagesCreated',                'FormProgramStagesCreated'),
    ('CompetitionCreated',                          'ProgramCreated'),

    ('ProcessCompetitionApplicationAiEvaluation',   'ProcessProgramApplicationAiEvaluation'),
    ('EmailExistsInCompetition',                    'EmailExistsInProgram'),
    ('CompetitionRegistration',                     'ProgramRegistration'),

    ('ApprovedCompetitionApplication',              'ApprovedProgramApplication'),
    ('ApprovedCompetitionTab',                      'ApprovedProgramTab'),
    ('approved_competition_application',            'approved_program_application'),
    ('approved_competition_tab',                    'approved_program_tab'),

    ('SyncMentorsToCompetitions',                   'SyncMentorsToPrograms'),
    ('FilterByCompetition',                         'FilterByProgram'),

    ('ManageCompetition',                           'ManageProgram'),
    ('CreateCompetition',                           'CreateProgram'),
    ('EditCompetition',                             'EditProgram'),
    ('ListCompetitions',                            'ListPrograms'),
    ('ViewCompetition',                             'ViewProgram'),

    # ── Namespace paths (PHP uses backslash) ──
    ('App\\Filament\\Resources\\CompetitionResource\\', 'App\\Filament\\Resources\\ProgramResource\\'),
    ('App\\Filters\\Competitions\\',                'App\\Filters\\Programs\\'),
    ('App\\Traits\\Competition\\',                  'App\\Traits\\Program\\'),
    ('App\\Traits\\CompetitionApplication\\',       'App\\Traits\\ProgramApplication\\'),

    # ── DB column / table names ──
    ('competition_application_id',                  'program_application_id'),
    ('competition_applications',                    'program_applications'),
    ('competition_judge',                           'program_judge'),
    ('competition_labels',                          'program_labels'),
    ('competition_tabs',                            'program_tabs'),
    ('branding_competitions',                       'branding_programs'),
    ('user_competitions',                           'user_programs'),
    ('mentor_competitions',                         'mentor_programs'),
    ('competition_id',                              'program_id'),

    # ── API route / URL strings ──
    ('competition-applications',                    'program-applications'),
    ('competition-tabs',                            'program-tabs'),
    ('my-competition-applications',                 'my-program-applications'),

    # ── Session keys ──
    ('current_competition_id',                      'current_program_id'),

    # ── Lang file reference strings ──
    ("competition_application.",                    "program_application."),
    ("'competition_application'",                   "'program_application'"),
    ('"competition_application"',                   '"program_application"'),

    # ── Generic class/type name (after all compound names) ──
    ('Competition',                                 'Program'),

    # ── Lowercase identifiers ──
    ('competition_application',                     'program_application'),
    ('competitions',                                'programs'),
    ('competition',                                 'program'),
]

# ─────────────────────────────────────────────────
# TypeScript / TSX replacement rules
# ─────────────────────────────────────────────────
TS_REPLACEMENTS = [
    # ── Interface names: resolve collision before renaming Competition→Program ──
    # Old 'Program' wrapper → 'ProgramList'  (must come before Competition→Program)
    ('interface Program {',                         'interface ProgramList {'),
    ('interface MyProgram {',                       'interface ProgramApplicationList {'),
    (': Program;',                                  ': ProgramList;'),
    (': MyProgram;',                                ': ProgramApplicationList;'),
    ('Program>',                                    'ProgramList>'),   # generics like Program[]
    ('MyProgram>',                                  'ProgramApplicationList>'),
    (': Program[]',                                 ': ProgramList[]'),
    (': MyProgram[]',                               ': ProgramApplicationList[]'),
    ('MyProgram | null',                            'ProgramApplicationList | null'),
    ('Program | null',                              'ProgramList | null'),
    ('MyProgram | undefined',                       'ProgramApplicationList | undefined'),
    ('Program | undefined',                         'ProgramList | undefined'),

    # ── MyCompetition → ProgramApplication ──
    ('interface MyCompetition {',                   'interface ProgramApplication {'),
    ('MyCompetition[]',                             'ProgramApplication[]'),
    ('MyCompetition |',                             'ProgramApplication |'),
    ('MyCompetition;',                              'ProgramApplication;'),
    ('MyCompetition>',                              'ProgramApplication>'),
    (': MyCompetition',                             ': ProgramApplication'),
    ('as MyCompetition',                            'as ProgramApplication'),

    # ── Competition → Program (interface + usages) ──
    ('interface Competition {',                     'interface Program {'),
    ('Competition[]',                               'Program[]'),
    ('Competition |',                               'Program |'),
    ('Competition;',                                'Program;'),
    ('Competition>',                                'Program>'),
    ('Competition,',                                'Program,'),
    (': Competition',                               ': Program'),
    ('as Competition',                              'as Program'),
    ('Competition}',                                'Program}'),
    ('{Competition',                                '{Program'),
    ('competition: Competition',                    'program: Program'),
    ('competition?: Competition',                   'program?: Program'),
    ('competition: null',                           'program: null'),

    # ── URL param: competitionId → programId ──
    ('competitionId',                               'programId'),
    ('competition_id',                              'program_id'),

    # ── API endpoint strings ──
    ('competition-applications',                    'program-applications'),
    ('competition-tabs',                            'program-tabs'),
    ('/competitions',                               '/programs'),
    ('my-competition-applications',                 'my-program-applications'),

    # ── Route paths ──
    ('my-competitions',                             'my-programs'),
    ('/competitions/',                              '/programs/'),

    # ── Query/cache keys ──
    ('queryKey: ["competition',                     'queryKey: ["program'),
    ("queryKey: ['competition",                     "queryKey: ['program"),
    ('"competition-',                               '"program-'),
    ("'competition-",                               "'program-"),
    ('"competition"',                               '"program"'),
    ("'competition'",                               "'program'"),
    ('"competitions"',                              '"programs"'),
    ("'competitions'",                              "'programs'"),

    # ── Translation keys ──
    ('"competition-information"',                   '"program-information"'),
    ('"my-competitions"',                           '"my-programs"'),
    ('"sorry-no-competitions-available"',           '"sorry-no-programs-available"'),
    ('"join-the-competition"',                      '"join-the-program"'),
    ('"sorry-you-have-not-registered-for-a-competition-yet"',
     '"sorry-you-have-not-registered-for-a-program-yet"'),
    ('"discover-the-available-competitions"',       '"discover-the-available-programs"'),
    ('"about-the-competition"',                     '"about-the-program"'),
    ('"what-interests-you-in-participating-in-the-competition"',
     '"what-interests-you-in-participating-in-the-program"'),
    ('"interested-in-competition-topics"',          '"interested-in-program-topics"'),
    ('"have-you-or-a-member-of-your-team-ever-participated-in-a-competition"',
     '"have-you-or-a-member-of-your-team-ever-participated-in-a-program"'),
    ('"you-have-successfully-registered-for-the-competition"',
     '"you-have-successfully-registered-for-the-program"'),
    ('"go-to-competitions"',                        '"go-to-programs"'),
    ('"no-projects-found-for-competition"',         '"no-projects-found-for-program"'),
    ('"competition"',                               '"program"'),
    ('"competitions"',                              '"programs"'),

    # ── Generic lowercase property names ──
    ('.competition.',                               '.program.'),
    ('.competition?.',                              '.program?.'),
    (' competition ',                               ' program '),
    ('competition,',                                'program,'),
    ('competition)',                                'program)'),
    ('(competition',                                '(program'),
    ('=competition',                                '=program'),
    ('competition=',                                'program='),
    ('competition}',                                'program}'),
    ('{competition',                                '{program'),
]

# ─────────────────────────────────────────────────
# JSON translation key replacement rules
# ─────────────────────────────────────────────────
JSON_KEY_REPLACEMENTS = [
    ('"competition-information":', '"program-information":'),
    ('"my-competitions":', '"my-programs":'),
    ('"sorry-no-competitions-available":', '"sorry-no-programs-available":'),
    ('"join-the-competition":', '"join-the-program":'),
    ('"sorry-you-have-not-registered-for-a-competition-yet":', '"sorry-you-have-not-registered-for-a-program-yet":'),
    ('"discover-the-available-competitions":', '"discover-the-available-programs":'),
    ('"about-the-competition":', '"about-the-program":'),
    ('"what-interests-you-in-participating-in-the-competition":', '"what-interests-you-in-participating-in-the-program":'),
    ('"interested-in-competition-topics":', '"interested-in-program-topics":'),
    ('"have-you-or-a-member-of-your-team-ever-participated-in-a-competition":', '"have-you-or-a-member-of-your-team-ever-participated-in-a-program":'),
    ('"you-have-successfully-registered-for-the-competition":', '"you-have-successfully-registered-for-the-program":'),
    ('"go-to-competitions":', '"go-to-programs":'),
    ('"no-projects-found-for-competition":', '"no-projects-found-for-program":'),
    ('"competition":', '"program":'),
    # Fix Arabic value for program-information key
    ('"program-information": "معلومات المسابقة"', '"program-information": "معلومات البرنامج"'),
]


def process_file(filepath, replacements, dry_run=False):
    try:
        with open(filepath, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
    except Exception as e:
        print(f"  SKIP (read error): {filepath}: {e}", file=sys.stderr)
        return False

    original = content
    for old, new in replacements:
        content = content.replace(old, new)

    if content == original:
        return False

    if not dry_run:
        try:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
        except Exception as e:
            print(f"  ERROR (write): {filepath}: {e}", file=sys.stderr)
            return False

    print(f"  UPDATED: {filepath}")
    return True


def walk_and_process(root, extensions, replacements, excludes=None):
    excludes = excludes or []
    count = 0
    for dirpath, dirnames, filenames in os.walk(root):
        # Skip excluded directories
        dirnames[:] = [d for d in dirnames if d not in excludes]
        for filename in filenames:
            if any(filename.endswith(ext) for ext in extensions):
                filepath = os.path.join(dirpath, filename)
                if process_file(filepath, replacements):
                    count += 1
    return count


if __name__ == '__main__':
    base = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    backend = os.path.join(base, 'Backend')
    frontend = os.path.join(base, 'Frontend')

    print("=" * 60)
    print("Phase 1: Backend PHP files")
    print("=" * 60)

    # App code (PHP + Blade)
    php_dirs = [
        os.path.join(backend, 'app'),
        os.path.join(backend, 'routes'),
        os.path.join(backend, 'bootstrap'),
        os.path.join(backend, 'lang'),
        os.path.join(backend, 'resources'),
        os.path.join(backend, 'database', 'factories'),
        os.path.join(backend, 'database', 'seeders'),
    ]
    php_count = 0
    for d in php_dirs:
        if os.path.exists(d):
            php_count += walk_and_process(d, ['.php', '.blade.php'], PHP_REPLACEMENTS,
                                           excludes=['vendor', 'node_modules'])
    print(f"\n→ {php_count} PHP/Blade files updated\n")

    print("=" * 60)
    print("Phase 2: Frontend TypeScript/TSX files")
    print("=" * 60)

    ts_src = os.path.join(frontend, 'src')
    ts_count = 0
    if os.path.exists(ts_src):
        ts_count = walk_and_process(ts_src, ['.ts', '.tsx', '.js', '.jsx'], TS_REPLACEMENTS,
                                     excludes=['node_modules', '.next'])
    print(f"\n→ {ts_count} TS/TSX files updated\n")

    print("=" * 60)
    print("Phase 3: Translation JSON files")
    print("=" * 60)

    for lang in ['en.json', 'ar.json']:
        filepath = os.path.join(frontend, 'messages', lang)
        if os.path.exists(filepath):
            if process_file(filepath, JSON_KEY_REPLACEMENTS):
                print(f"  UPDATED: {filepath}")
            else:
                print(f"  NO CHANGE: {filepath}")

    print("\n✓ All replacements complete.")
