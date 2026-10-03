import type { SimpleMeta } from './reports';

/* ------------------------------------------------------------------
 * AI Studio — App\Http\Controllers\Admin\Ai\AiStudioController,
 * App\Services\Ai\AiStudioPresenter
 * ------------------------------------------------------------------ */

export type AiTab =
    | 'proofreader'
    | 'translator'
    | 'analytics'
    | 'history'
    | 'settings';

export type AiRequestTypeValue = 'spell_check' | 'translation' | 'analysis';

export type AiRequestStatusValue =
    | 'queued'
    | 'processing'
    | 'completed'
    | 'failed';

export type AiOption = { value: string; label: string };

export type AiRequestItem = {
    uuid: string;
    type: AiRequestTypeValue;
    typeLabel: string;
    studio: string;
    status: AiRequestStatusValue;
    statusLabel: string;
    languages: string;
    title: string;
    chars: number;
    tokens: number;
    progress: number;
    score: number | null;
    createdAt: string | null;
    url: string;
    user: string | null;
};

export type ProofIssueType =
    | 'spelling'
    | 'grammar'
    | 'punctuation'
    | 'style'
    | 'terminology';

export type ProofDecision = 'pending' | 'accepted' | 'rejected';

export type ProofIssue = {
    id: string;
    original: string;
    suggestion: string;
    type: ProofIssueType;
    reason: string;
    offset: number;
    length: number;
    status: ProofDecision;
};

export type ProofreadResult = {
    segments: { text: string; issue: string | null }[];
    issues: ProofIssue[];
    counts: { errors: number; suggestions: number; total: number };
    score: number | null;
    summary: string;
    savedAt: string | null;
    finalText: string | null;
    saveUrl: string;
};

export type TranslationVersionItem = {
    version: number;
    content: string;
    isAi: boolean;
    author: string;
    createdAt: string | null;
    downloadUrl: string;
};

export type TranslationResult = {
    uuid: string;
    title: string;
    source: string;
    target: string;
    truncated: boolean;
    versions: TranslationVersionItem[];
    saveUrl: string;
};

export type AnalysisMetricKey =
    | 'academic_style'
    | 'clarity'
    | 'structure'
    | 'terminology'
    | 'coherence';

export type AnalysisResult = {
    score: number | null;
    metrics: Record<AnalysisMetricKey, number | null>;
    strengths: string[];
    weaknesses: string[];
    recommendations: string[];
    summary: string;
    analysed_chars: number;
};

export type AiRequestDetail = AiRequestItem & {
    input: string;
    error: string | null;
    chunksTotal: number;
    chunksCompleted: number;
    finished: boolean;
    own: boolean;
    proofread?: ProofreadResult;
    translation?: TranslationResult | null;
    analysis?: AnalysisResult | null;
};

export type AiBudget = {
    limit: number;
    used: number;
    remaining: number | null;
    personal: boolean;
    resetsAt: string;
};

export type AiStats = {
    from: string;
    to: string;
    total: number;
    completed: number;
    failed: number;
    completedPct: number;
    failedPct: number;
    tokens: number;
    avgSeconds: number;
};

export type AiPromptTemplate = {
    key: string;
    name: string;
    type: AiRequestTypeValue;
    studio: string;
    systemPrompt: string;
    userPromptTemplate: string | null;
    model: string | null;
    temperature: number;
    maxTokens: number;
    isActive: boolean;
    updatedAt: string | null;
    updatedBy: string | null;
    updateUrl: string;
    resetUrl: string;
};

export type AiUsageRow = {
    id: number;
    name: string;
    email: string;
    staff: boolean;
    used: number;
    requests: number;
    limit: number;
    personalLimit: number | null;
    percent: number | null;
    updateUrl: string;
};

export type AiSettingsData = {
    values: {
        enabled: boolean;
        model: string | null;
        maskedKey: string | null;
        keySource: 'database' | 'env' | 'none';
        authorLimit: number;
        staffLimit: number;
        maxInputChars: number;
    };
    prompts: AiPromptTemplate[];
    usage: AiUsageRow[];
    search: string;
    urls: { update: string; forgetKey: string };
};

export type AiStudioPageProps = {
    tab: AiTab;
    filters: { type: AiRequestTypeValue | null; scope: 'own' | 'all' };
    ready: boolean;
    canManage: boolean;
    languages: AiOption[];
    checks: AiOption[];
    maxChars: number;
    budget: AiBudget;
    current: AiRequestDetail | null;
    history: { data: AiRequestItem[]; meta: SimpleMeta };
    stats: AiStats;
    activity: AiRequestItem[];
    settings: AiSettingsData | null;
    urls: { store: string; index: string };
};
