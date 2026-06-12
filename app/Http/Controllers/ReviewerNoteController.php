<?php

namespace App\Http\Controllers;

use App\Models\Experiment;
use App\Models\ReviewerNote;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ReviewerNoteController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function store(Request $request, Experiment $experiment)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $note = ReviewerNote::create([
            'experiment_id' => $experiment->id,
            'user_id'       => auth()->id(),
            'note'          => $data['note'],
        ]);
        $this->audit->log('reviewer_note.created', $note);

        return back()->with('success', 'Catatan reviewer ditambahkan.');
    }

    public function destroy(ReviewerNote $note)
    {
        if (auth()->id() !== $note->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }
        $this->audit->log('reviewer_note.deleted', $note);
        $note->delete();
        return back()->with('success', 'Catatan dihapus.');
    }
}
