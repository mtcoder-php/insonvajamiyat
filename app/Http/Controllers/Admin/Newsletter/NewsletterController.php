<?php

namespace App\Http\Controllers\Admin\Newsletter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Newsletter\NewsletterCampaignRequest;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\NewsletterCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Obuna (content.manage): statistika, xat yuborish, tarix, obunachilar ro'yxati,
 * CSV eksport va "yangi son haqida avtomatik xat" sozlamasi.
 */
class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterCampaignService $newsletter,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $subscribers = $this->newsletter->subscribers($filters);

        return Inertia::render('admin/newsletter/Index', [
            'tab' => $request->query('tab') === 'subscribers' ? 'subscribers' : 'compose',
            'stats' => $this->newsletter->stats(),
            'audiences' => $this->newsletter->audiences(),
            'issues' => $this->newsletter->issueOptions(),
            'history' => $this->newsletter->history(),
            'autoIssue' => $this->newsletter->autoIssueEnabled(),
            'filters' => $filters,
            'subscribers' => [
                'data' => $subscribers->getCollection()->map(fn (NewsletterSubscriber $s): array => [
                    'id' => $s->id,
                    'email' => $s->email,
                    'locale' => $s->locale,
                    'state' => $this->newsletter->state($s),
                    'createdAt' => $s->created_at?->toIso8601String(),
                    'confirmedAt' => $s->confirmed_at?->toIso8601String(),
                    'unsubscribedAt' => $s->unsubscribed_at?->toIso8601String(),
                ])->all(),
                'meta' => [
                    'current_page' => $subscribers->currentPage(),
                    'last_page' => $subscribers->lastPage(),
                    'per_page' => $subscribers->perPage(),
                    'total' => $subscribers->total(),
                    'from' => $subscribers->firstItem(),
                    'to' => $subscribers->lastItem(),
                    'links' => $subscribers->linkCollection()->all(),
                ],
            ],
        ]);
    }

    public function store(NewsletterCampaignRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $campaign = $this->newsletter->send([
            'audience' => $request->string('audience')->toString(),
            'subject' => $request->string('subject')->trim()->toString(),
            'body' => $request->string('body')->trim()->toString(),
            'button_label' => $request->filled('button_label') ? $request->string('button_label')->trim()->toString() : null,
            'button_url' => $request->filled('button_url') ? $request->string('button_url')->trim()->toString() : null,
            'journal_issue_id' => $request->filled('journal_issue_id') ? $request->integer('journal_issue_id') : null,
        ], $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Xat :count ta obunachiga yuborilmoqda.', ['count' => $campaign->recipients_count])]);

        return back();
    }

    public function settings(Request $request): RedirectResponse
    {
        $request->validate(['auto_issue' => ['required', 'boolean']]);

        /** @var User $user */
        $user = $request->user();
        $this->newsletter->setAutoIssue($request->boolean('auto_issue'), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => $request->boolean('auto_issue')
            ? __('Yangi son chop etilganda obunachilarga avtomatik xat yuboriladi.')
            : __("Yangi son haqida avtomatik xat o'chirildi.")]);

        return back();
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->newsletter->export($this->filters($request), $user);
    }

    public function destroy(Request $request, NewsletterSubscriber $subscriber): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->newsletter->delete($subscriber, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __("Obunachi o'chirildi.")]);

        return back();
    }

    /**
     * @return array{q: string|null, status: string|null}
     */
    private function filters(Request $request): array
    {
        $status = $request->query('status');

        return [
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : null,
            'status' => in_array($status, ['confirmed', 'pending', 'unsubscribed'], true) ? $status : null,
        ];
    }
}
