<?php

namespace App\Http\Controllers;

use App\Models\TitleSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SuggestedAIController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->canLeadGroup()) {
            abort(403, 'Only group leaders can analyze titles and request advisers.');
        }

        $activeRequest = auth()->user()
            ->adviserRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($activeRequest) {
            return redirect()
                ->route('advisers.title-submission')
                ->withErrors(['title1' => 'You already have an active adviser request. You can submit new titles after the request is rejected.']);
        }

        $validated = $request->validate([
            'title1' => 'required|string|max:255',
            'title2' => 'required|string|max:255',
            'title3' => 'required|string|max:255',
            'title4' => 'required|string|max:255',
            'title5' => 'required|string|max:255',
        ]);

        $submission = TitleSubmission::updateOrCreate(
            ['student_id' => auth()->id()],
            $validated
        );

        $titles = $submission->only(['title1', 'title2', 'title3', 'title4', 'title5']);
        $analysis = implode(' ', $titles);

        try {
            $response = Http::timeout(30)->post('http://127.0.0.1:8001/analyze', $titles);

            if ($response->successful()) {
                $analysis = $response->json('analysis') ?? $analysis;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $normalizedAnalysis = Str::lower(is_string($analysis) ? $analysis : implode(' ', $titles));

        $advisers = User::where('role', 'Teacher')
            ->where('status', 'Verified')
            ->with('expertise')
            ->get();

        $recommendedAdvisers = $advisers->map(function ($adviser) use ($normalizedAnalysis) {
            $score = 0;
            $matched = [];
            $expertise = $adviser->expertise;

            if ($expertise) {
                $fields = [
                    'Machine Learning' => [$expertise->machine_learning, ['machine learning', 'prediction', 'classification', 'recommendation', 'neural', 'model']],
                    'AI Integration' => [$expertise->ai_integration, ['ai', 'artificial intelligence', 'chatbot', 'automation', 'gemini', 'openai']],
                    'Cybersecurity' => [$expertise->cybersecurity, ['security', 'cybersecurity', 'threat', 'privacy', 'encryption', 'authentication']],
                    'IoT' => [$expertise->iot, ['iot', 'sensor', 'arduino', 'raspberry', 'embedded', 'device']],
                    'Cloud Computing' => [$expertise->cloud_computing, ['cloud', 'aws', 'azure', 'serverless', 'deployment']],
                    'Data Analytics' => [$expertise->data_analytics, ['analytics', 'dashboard', 'visualization', 'data mining', 'reporting']],
                    'Web Development' => [$expertise->web_development, ['web', 'website', 'laravel', 'react', 'portal', 'system']],
                    'Mobile Development' => [$expertise->mobile_development, ['mobile', 'android', 'ios', 'app', 'flutter']],
                    'Database Systems' => [$expertise->database_systems, ['database', 'sql', 'mysql', 'records', 'inventory', 'management system']],
                    'Networking' => [$expertise->networking, ['network', 'networking', 'lan', 'wireless', 'connectivity']],
                ];

                foreach ($fields as $name => [$enabled, $terms]) {
                    if (!$enabled) {
                        continue;
                    }

                    foreach ($terms as $term) {
                        if (str_contains($normalizedAnalysis, $term)) {
                            $score += 25;
                            $matched[] = $name;
                            break;
                        }
                    }
                }

                foreach ($expertise->custom_expertise ?? [] as $customExpertise) {
                    $customExpertise = trim($customExpertise);

                    if ($customExpertise === '') {
                        continue;
                    }

                    $customTerms = collect(preg_split('/\s+/', Str::lower($customExpertise)))
                        ->map(fn ($term) => trim($term, " ,.;:/\\|()[]{}"))
                        ->filter(fn ($term) => mb_strlen($term) > 2)
                        ->values();

                    $customMatched = str_contains($normalizedAnalysis, Str::lower($customExpertise))
                        || $customTerms->contains(fn ($term) => str_contains($normalizedAnalysis, $term));

                    if ($customMatched) {
                        $score += 20;
                        $matched[] = $customExpertise;
                    }
                }
            }

            $matched = array_values(array_unique($matched));

            $adviser->score = min($score, 100);
            $adviser->matched_expertise = $matched;
            $adviser->reason = match (count($matched)) {
                0 => 'General academic fit',
                1 => "Match in {$matched[0]}",
                default => 'Strong match in ' . implode(', ', $matched),
            };

            return $adviser;
        })
        ->sortByDesc('score')
        ->values();

        $currentRequests = auth()->user()
            ->adviserRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        return view('advisers.suggestedAI', [
            'recommendedAdvisers' => $recommendedAdvisers,
            'advisers' => $advisers,
            'currentRequests' => $currentRequests,
        ]);
    }
}
