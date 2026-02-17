<?php

namespace App\Livewire;

use Livewire\Component;

class AdminChatbot extends Component
{
    public bool $isOpen = false;
    public string $userMessage = '';
    public array $messages = [];
    public int $competitionId;
    public string $activeTab = 'overview';

    public function mount(int $competitionId, string $activeTab = 'overview')
    {
        $this->competitionId = $competitionId;
        $this->activeTab = $activeTab;

        // Welcome message
        $this->messages[] = [
            'role' => 'assistant',
            'content' => "Hi! I'm your Program Setup Assistant. I can help you understand each section of this hub and guide you through setting up your program. Just ask me anything!",
        ];
    }

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->userMessage))) {
            return;
        }

        $question = trim($this->userMessage);
        $this->messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        $this->userMessage = '';

        // Generate knowledge-base response
        $response = $this->generateResponse($question);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $response,
        ];
    }

    protected function generateResponse(string $question): string
    {
        $q = strtolower($question);

        // Context-aware responses based on active tab and question keywords
        $knowledgeBase = $this->getKnowledgeBase();

        // Try to match question to knowledge base entries
        $bestMatch = null;
        $bestScore = 0;

        foreach ($knowledgeBase as $entry) {
            $score = 0;
            foreach ($entry['keywords'] as $keyword) {
                if (str_contains($q, strtolower($keyword))) {
                    $score += strlen($keyword); // Longer keyword matches = higher priority
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $entry;
            }
        }

        if ($bestMatch && $bestScore > 3) {
            return $bestMatch['answer'];
        }

        // Tab-specific default responses
        return $this->getTabContextResponse();
    }

    protected function getTabContextResponse(): string
    {
        return match ($this->activeTab) {
            'overview' => "You're on the **Overview** tab. Here you can edit the program's basic info — title, description, type, terms, and banner. Don't forget to toggle 'Published' when you're ready to go live!\n\nNeed help with something specific?",
            'stages' => "The **Stages & Tracks** tab shows your program's timeline stages and track categories. Stages define the phases of your program (registration, project submission, evaluation, etc.), and tracks organize participants into categories.\n\nTo edit stages, click 'Edit in Full View' which takes you to the detailed editor.",
            'registration' => "The **Registration** tab lets you configure how participants sign up. You can set:\n- Registration type (Individual, Team, or Both)\n- Age restrictions\n- Team size limits\n- Custom field labels\n- Scoring criteria for registration acceptance\n\nWhat would you like to configure?",
            'team' => "The **Team Settings** tab controls team formation rules:\n- Min/max team members\n- Track selection rules (can teams pick their track?)\n- Whether all members must share the same track\n- Auto-publish setting for new teams",
            'project' => "The **Project Submission** tab links a project form to this program and controls whether participants can change their track when submitting.\n\nMake sure you've already created a project-type form in the Forms section first!",
            'ai' => "The **AI Scoring** tab sets up automated scoring using AI. You'll need to:\n1. Select a form type and specific form\n2. Write an AI prompt that guides the scoring\n3. Set the total weight for AI scoring\n\nAfter saving, you can add assessment criteria and field mappings from the detailed AI Scoring page.",
            'evaluation' => "The **Evaluation** tab configures multi-stage evaluation. You can set up to 4 evaluation stages, each with:\n- An evaluation form\n- Track-specific or global application\n- Submission requirements (new or reuse previous)\n\nThis is great for progressive elimination rounds!",
            'regeval' => "The **Reg. Evaluation** tab creates evaluation forms specifically for screening registration submissions. Set up:\n- Form name and description\n- Scoring scale (1-5, 1-10, or 1-100)\n- Dimension labels\n\nAfter creating the form, you can add criteria from the detailed Evaluation Forms page.",
            'people' => "The **People** tab shows mentors and judges assigned to this program. To add or manage them, use the dedicated Mentors and Judges resources from the sidebar.",
            'content' => "The **Content** tab shows events and guidelines for this program. You can manage these from the Events and Guidelines resources in the sidebar.",
            default => "I'm here to help you set up your program! Ask me about any tab or feature, and I'll guide you through the process.",
        };
    }

    protected function getKnowledgeBase(): array
    {
        return [
            [
                'keywords' => ['how', 'setup', 'set up', 'create', 'new program', 'start', 'begin'],
                'answer' => "To set up a new program, follow these steps:\n\n1. **Overview** — Fill in the program title, type, description, and terms\n2. **Stages & Tracks** — Define your program stages (registration, evaluation, etc.) and create tracks/sub-tracks\n3. **Registration** — Configure registration settings (type, age limits, team size)\n4. **Team Settings** — If your program has teams, set team formation rules\n5. **Forms** — Create registration and project forms from the Forms section\n6. **AI Scoring** — Optionally set up AI-powered scoring\n7. **Evaluation** — Configure evaluation stages and criteria\n8. **Publish** — Toggle 'Published' in the Overview tab\n\nStart from the Overview tab and work your way through!",
            ],
            [
                'keywords' => ['registration type', 'individual', 'team', 'both', 'register'],
                'answer' => "There are 3 registration types:\n\n- **Individual** — Participants register alone, no team fields shown\n- **Team** — Participants must form teams, team fields are required\n- **Both** — Participants choose between individual or team registration\n\nWhen 'Both' is selected, you'll see extra options to customize the labels for the registration type selector.",
            ],
            [
                'keywords' => ['stage', 'stages', 'timeline', 'phase'],
                'answer' => "Stages define your program's timeline phases. Common stages include:\n\n- **Registration** — When participants sign up\n- **Team Formation** — When teams are built\n- **Project Submission** — When projects are submitted\n- **Evaluation** — When judges review submissions\n\nEach stage has start/end dates and can be linked to forms. You can have up to 7 stages per program. To edit stages, use the 'Edit in Full View' link on the Stages & Tracks tab.",
            ],
            [
                'keywords' => ['track', 'tracks', 'sub-track', 'category', 'categories'],
                'answer' => "Tracks organize participants into categories (like 'FinTech', 'HealthTech', 'EdTech'). Each track can have sub-tracks for more specific categorization.\n\nTracks affect:\n- Registration (participants pick their track)\n- Team formation (optionally require same track)\n- Evaluation (stage-specific track filtering)\n- Project submission (track/sub-track selection)",
            ],
            [
                'keywords' => ['ai', 'scoring', 'artificial', 'intelligence', 'automated'],
                'answer' => "AI Scoring automates the evaluation of form submissions. Here's how it works:\n\n1. **Select a form** — Choose which form type and specific form to score\n2. **Write a prompt** — Tell the AI what to evaluate (e.g., 'You are an expert on fintech innovation...')\n3. **Set total weight** — Define the maximum score (usually 100)\n4. **Add criteria** — After saving, add assessment criteria with individual weights\n5. **Map fields** — Map form fields to criteria so the AI knows what to score\n\nThe AI will then automatically score each submission based on your criteria!",
            ],
            [
                'keywords' => ['evaluation', 'judge', 'judging', 'review', 'score'],
                'answer' => "The evaluation system supports multi-stage judging:\n\n1. **Evaluation Stage Config** — Set up 1-4 evaluation stages\n2. **Evaluation Forms** — Create forms with criteria for judges\n3. **Registration Evaluation** — Separate evaluation for screening registrations\n\nEach stage can use different forms and apply to specific tracks or all tracks. Stages can require new submissions or reuse previous ones.",
            ],
            [
                'keywords' => ['form', 'forms', 'fields', 'builder'],
                'answer' => "Forms are created in the **Forms** section (sidebar). There are several form types:\n\n- **Registration** — For participant sign-up\n- **Project Submission** — For project uploads\n- **Evaluation** — For judge scoring\n\nEach form has a drag-and-drop field builder where you can add text fields, selects, file uploads, etc. Forms are linked to stages and can have steps for multi-page layouts.",
            ],
            [
                'keywords' => ['publish', 'live', 'visible', 'public'],
                'answer' => "To make your program live:\n\n1. Go to the **Overview** tab\n2. Check the **Published** checkbox\n3. Click **Save Overview**\n\nMake sure you've set up all stages, forms, and configurations before publishing! Published programs are visible on the public-facing site.",
            ],
            [
                'keywords' => ['archive', 'archived', 'old', 'disable'],
                'answer' => "Archived programs cannot be edited — only deleted or restored. If you see the archived banner, it means this program has been archived by an admin.\n\nTo restore it, go to the Programs List and use the Restore action. Archiving is useful for keeping historical programs without deleting them.",
            ],
            [
                'keywords' => ['mentor', 'mentors', 'mentoring'],
                'answer' => "Mentors are assigned to programs to guide participants. They can:\n- View assigned teams/participants\n- Provide feedback and guidance\n- Track progress through the mentorship dashboard\n\nTo manage mentors, use the **Mentors** resource in the sidebar. The People tab here shows a read-only overview of assigned mentors.",
            ],
            [
                'keywords' => ['approval', 'approve', 'workflow', 'request'],
                'answer' => "Some changes (like updating program details) may require approval from designated approvers. When an approval workflow is active:\n\n1. Your change is submitted as a request\n2. Approvers are notified\n3. Once approved, the change is applied automatically\n\nYou can check the status of your requests in **My Requests** from the sidebar.",
            ],
            [
                'keywords' => ['help', 'what can you', 'assist', 'guide'],
                'answer' => "I can help you with:\n\n- **Setting up a program** from scratch\n- **Understanding each tab** and what it configures\n- **Registration settings** — types, age limits, team sizes\n- **Forms & Fields** — how to create and link forms\n- **AI Scoring** — automated evaluation setup\n- **Evaluation stages** — multi-round judging\n- **Tracks & sub-tracks** — categorization\n- **Publishing** your program\n\nJust ask me about any feature or describe what you want to do!",
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin-chatbot');
    }
}
