<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ProjectEvaluationNote;
use Filament\Notifications\Notification;

class EvaluationComments extends Component
{
    public $evaluationId;
    public $comments;

    public function mount($evaluationId)
    {
        $this->evaluationId = $evaluationId;
        $this->loadComments();
    }

    public function loadComments()
    {
        $this->comments = ProjectEvaluationNote::with('admin')
            ->where('project_evaluation_id', $this->evaluationId)
            ->latest()
            ->get();
    }

    public function deleteComment($commentId)
    {
        $comment = ProjectEvaluationNote::find($commentId);

        if ($comment && $comment->canDelete()) {
            $comment->delete();

            Notification::make()
                ->title('Comment deleted successfully')
                ->success()
                ->send();

            $this->loadComments();
        }
    }

    public function render()
    {
        return view('livewire.evaluation-comments');
    }
}
