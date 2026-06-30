<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TitleSubmission;

class TitleSubmissionController extends Controller
{
    public function store(Request $request)
    {
        if (!auth()->user()->canLeadGroup()) {
            abort(403, 'Only group leaders can submit proposed titles.');
        }

        $request->validate([
            'title1' => 'required|string|max:255',
            'title2' => 'nullable|string|max:255',
            'title3' => 'nullable|string|max:255',
            'title4' => 'nullable|string|max:255',
            'title5' => 'nullable|string|max:255',
        ]);

        TitleSubmission::updateOrCreate(
            [
                'student_id' => auth()->id(),
            ],
            [
                'title1' => $request->title1,
                'title2' => $request->title2,
                'title3' => $request->title3,
                'title4' => $request->title4,
                'title5' => $request->title5,
            ]
        );

        return back()->with('success', 'Titles submitted successfully.');
    }
}
