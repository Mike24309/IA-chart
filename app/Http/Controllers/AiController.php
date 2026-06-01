<?php

namespace App\Http\Controllers;

use App\Models\NotificationIa;
use App\Services\AiAuditService;
use App\Services\AiSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

// Ce contrôleur pilote l'interface admin-only du module IA.
class AiController extends Controller
{
    public function __construct(
        private readonly AiAuditService $aiAuditService,
        private readonly AiSettingsService $aiSettingsService
    ) {
    }

    public function index(): View
    {
        $settings = $this->aiSettingsService->current();
        $notifications = NotificationIa::with('log')->latest('notifiee_le')->take(24)->get()
            ->filter(fn ($notification) => data_get($notification->log?->charge_sortie, 'analysis_origin') === 'ia')
            ->take(10)
            ->values();

        return view('ai.index', compact('settings', 'notifications'));
    }

    public function analyze(Request $request): JsonResponse
    {
        $analysisPeriod = $request->string('analysis_period')->toString();

        try {
            $analysis = $this->aiAuditService->analyzeLocal(
                $request->user(),
                $analysisPeriod
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($analysis);
    }

    public function interpret(Request $request): JsonResponse
    {
        $analysisPeriod = $request->string('analysis_period')->toString();

        try {
            $analysis = $this->aiAuditService->summarize(
                $request->user(),
                $request->boolean('force'),
                $analysisPeriod
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($analysis);
    }

    public function interpretPage(Request $request): RedirectResponse
    {
        $analysisPeriod = $request->string('analysis_period')->toString();

        try {
            $analysis = $this->aiAuditService->summarize(
                $request->user(),
                $request->boolean('force'),
                $analysisPeriod
            );

            return back()
                ->with('ai_summary_payload', $analysis)
                ->with('ai_sidepanel_open', true);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['ai_summary' => $exception->getMessage()])
                ->with('ai_sidepanel_open', true);
        }
    }

    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $payload = $this->aiAuditService->answerQuestion($request->user(), $data['question']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($payload);
    }

    public function report(Request $request)
    {
        $analysisPeriod = $request->string('analysis_period')->toString();

        try {
            $context = $this->aiAuditService->buildReportContext($request->user(), $analysisPeriod);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return Pdf::loadView('ai.report-pdf', $context)
            ->download('rapport-ia-' . now()->format('Ymd-His') . '.pdf');
    }

    public function notificationsRead(): RedirectResponse
    {
        NotificationIa::query()->update(['est_lue' => true]);

        return back()->with('success', 'Notifications IA marquees comme lues.');
    }
}
