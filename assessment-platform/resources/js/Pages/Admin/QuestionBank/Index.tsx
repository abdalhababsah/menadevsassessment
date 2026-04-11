import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState, FormEventHandler, useRef, useMemo } from 'react';
import { usePermissions } from '@/Hooks/usePermissions';
import { 
  Library, 
  Search, 
  Filter, 
  LayoutGrid, 
  List, 
  AlignJustify, 
  Plus, 
  MoreHorizontal, 
  Calendar,
  User as UserIcon,
  Tag as TagIcon,
  MousePointer2,
  CheckSquare,
  Code2,
  MessageSquareQuote,
  ArrowUpDown,
  ChevronDown,
  X,
  History,
  Eye,
  Trash2,
  Copy,
  Edit,
  ExternalLink
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  Popover, 
  PopoverContent, 
  PopoverTrigger 
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { motion, AnimatePresence } from 'framer-motion';
import { TypePickerModal } from '@/components/questionbank/TypePickerModal';
import { QuestionDetailDrawer } from '@/components/questionbank/QuestionDetailDrawer';

interface QuestionRow {
    id: number;
    stem: string;
    full_stem: string;
    type: string;
    type_label: string;
    difficulty: string;
    difficulty_label: string;
    points: string;
    tags: { id: number; name: string }[];
    creator: { id: number; name: string; avatar?: string } | null;
    created_at: string;
    usages_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestions {
    data: QuestionRow[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Stats {
    total: number;
    by_type: {
        single_select: number;
        multi_select: number;
        coding: number;
        rlhf: number;
    };
}

interface Filters {
    type?: string;
    difficulty?: string;
    creator?: string;
    tags?: string;
    q?: string;
    created_after?: string;
    created_before?: string;
}

interface Props {
    questions: PaginatedQuestions;
    tags: { id: number; name: string }[];
    creators: { id: number; name: string; avatar?: string }[];
    stats: Stats;
    filters: Filters;
    sort: string;
}

const VIEW_MODES = [
  { value: 'gallery', icon: LayoutGrid, label: 'Gallery' },
  { value: 'table', icon: List, label: 'Table' },
  { value: 'compact', icon: AlignJustify, label: 'Compact' },
];

const DIFFICULTIES = [
    { value: 'easy', label: 'Easy', color: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' },
    { value: 'medium', label: 'Medium', color: 'bg-amber-500/10 text-amber-500 border-amber-500/20' },
    { value: 'hard', label: 'Hard', color: 'bg-rose-500/10 text-rose-500 border-rose-500/20' },
];

const TYPE_CONFIG: Record<string, { icon: any; color: string; bg: string }> = {
    single_select: { icon: MousePointer2, color: 'text-verdant-500', bg: 'bg-verdant-500' },
    multi_select: { icon: CheckSquare, color: 'text-indigo-500', bg: 'bg-indigo-500' },
    coding: { icon: Code2, color: 'text-stone-700', bg: 'bg-neutral-800' },
    rlhf: { icon: MessageSquareQuote, color: 'text-yellow-600', bg: 'bg-yellow-500' },
};

export default function Index({ questions, tags, creators, stats, filters, sort }: Props) {
    const { hasPermission } = usePermissions();
    const [viewMode, setViewMode] = useState<'gallery' | 'table' | 'compact'>('gallery');
    const [searchTerm, setSearchTerm] = useState((filters.q as string) ?? '');
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedQuestion, setSelectedQuestion] = useState<QuestionRow | null>(null);
    const [isFiltersVisible, setIsFiltersVisible] = useState(true);

    const selectedTagIds = useMemo(() => 
        filters.tags ? filters.tags.split(',').map(Number) : []
    , [filters.tags]);

    const buildQuery = (overrides: Partial<Filters & { sort: string; per_page: number }>) => {
        const next = {
            'filter[type]': filters.type ?? '',
            'filter[difficulty]': filters.difficulty ?? '',
            'filter[creator]': filters.creator ?? '',
            'filter[tags]': filters.tags ?? '',
            'filter[q]': filters.q ?? '',
            'filter[created_after]': filters.created_after ?? '',
            'filter[created_before]': filters.created_before ?? '',
            sort,
            ...Object.fromEntries(
                Object.entries(overrides).map(([k, v]) =>
                    k === 'sort' ? ['sort', v] : [`filter[${k}]`, v ?? '']
                )
            ),
        };

        return Object.fromEntries(Object.entries(next).filter(([, v]) => v !== '' && v !== undefined));
    };

    const applyFilter = (key: keyof Filters | 'sort', value: any) => {
        router.get(route('admin.questions.index'), buildQuery({ [key]: value }), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const toggleTag = (tagId: number) => {
        const next = selectedTagIds.includes(tagId)
            ? selectedTagIds.filter((id) => id !== tagId)
            : [...selectedTagIds, tagId];
        applyFilter('tags', next.join(',') || undefined);
    };

    const clearFilters = () => {
        router.get(route('admin.questions.index'));
    };

    return (
        <AdminLayout>
            <Head title="The Library | Question Bank" />

            <div className="flex flex-col h-full relative">
                {/* Top Header / Hero Strip */}
                <div className="bg-white border border-border rounded-2xl p-8 mb-8 shadow-sm">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <div className="flex items-center gap-2 text-verdant-600 font-bold uppercase tracking-widest text-[10px] mb-1">
                                <Library className="w-3 h-3" />
                                <span>Question Bank</span>
                            </div>
                            <h1 className="text-3xl font-display font-bold text-foreground">The Library</h1>
                            <p className="text-sm text-muted-foreground mt-1">Your reusable knowledge collection and assessment assets.</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                className="hidden md:flex"
                                onClick={() => router.get(route('admin.questions.export'))}
                            >
                                <Copy className="w-4 h-4 mr-2" />
                                Export JSON
                            </Button>
                            <Button 
                                variant="verdant" 
                                size="default" 
                                className="rounded-full shadow-lg shadow-verdant-500/20"
                                onClick={() => setIsCreateModalOpen(true)}
                            >
                                <Plus className="w-4 h-4 mr-2" />
                                Create Question
                            </Button>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="bg-muted/50 border-input font-medium py-1 px-3">
                            {stats.total} Questions total
                        </Badge>
                        <div className="w-px h-4 bg-border mx-1" />
                        <Badge variant="outline" className="bg-verdant-50 border-verdant-100 text-verdant-700 font-medium py-1 px-3">
                            {stats.by_type.single_select} Single Select
                        </Badge>
                        <Badge variant="outline" className="bg-indigo-50 border-indigo-100 text-indigo-700 font-medium py-1 px-3">
                            {stats.by_type.multi_select} Multi Select
                        </Badge>
                        <Badge variant="outline" className="bg-stone-100 border-stone-200 text-stone-800 font-medium py-1 px-3">
                            {stats.by_type.coding} Coding
                        </Badge>
                        <Badge variant="outline" className="bg-yellow-50 border-yellow-100 text-yellow-700 font-medium py-1 px-3">
                            {stats.by_type.rlhf} RLHF
                        </Badge>
                    </div>
                </div>

                <div className="flex-1 flex overflow-hidden">
                    {/* Filters Sidebar */}
                    <AnimatePresence mode="wait">
                        {isFiltersVisible && (
                            <motion.aside
                                initial={{ width: 0, opacity: 0 }}
                                animate={{ width: 280, opacity: 1 }}
                                exit={{ width: 0, opacity: 0 }}
                                className="bg-white border border-border rounded-2xl overflow-y-auto hidden lg:block shadow-sm"
                            >
                                <div className="p-6 space-y-8">
                                    <div className="space-y-4">
                                        <div className="flex justify-between items-center">
                                            <h3 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Search Collection</h3>
                                            <button onClick={clearFilters} className="text-[10px] font-bold text-verdant-600 hover:text-verdant-700 transition-colors">RESET</button>
                                        </div>
                                        <div className="relative">
                                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                            <Input 
                                                placeholder="Search stems..." 
                                                className="pl-9 h-10 rounded-xl"
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                onKeyDown={(e) => e.key === 'Enter' && applyFilter('q', searchTerm)}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Question Type</h3>
                                        <div className="grid grid-cols-1 gap-2">
                                            {['single_select', 'multi_select', 'coding', 'rlhf'].map((type) => {
                                                const config = TYPE_CONFIG[type];
                                                const Icon = config.icon;
                                                const active = filters.type === type;
                                                return (
                                                    <button 
                                                        key={type}
                                                        onClick={() => applyFilter('type', active ? undefined : type)}
                                                        className={cn(
                                                            "flex items-center gap-3 p-3 rounded-xl border text-sm transition-all text-left",
                                                            active 
                                                                ? "bg-verdant-50 border-verdant-200 text-verdant-700 font-bold" 
                                                                : "bg-background border-border hover:border-input text-muted-foreground"
                                                        )}
                                                    >
                                                        <div className={cn("w-8 h-8 rounded-lg flex items-center justify-center", active ? "bg-verdant-500 text-white" : "bg-muted text-muted-foreground")}>
                                                            <Icon className="w-4 h-4" />
                                                        </div>
                                                        <span className="capitalize">{type.replace('_', ' ')}</span>
                                                        {active && <div className="ml-auto w-1.5 h-1.5 rounded-full bg-verdant-500" />}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Difficulty</h3>
                                        <div className="flex flex-wrap gap-2">
                                            {DIFFICULTIES.map((d) => (
                                                <button 
                                                    key={d.value}
                                                    onClick={() => applyFilter('difficulty', filters.difficulty === d.value ? undefined : d.value)}
                                                    className={cn(
                                                        "px-4 py-1.5 rounded-full border text-xs transition-all",
                                                        filters.difficulty === d.value 
                                                            ? "bg-foreground text-background font-bold border-foreground" 
                                                            : "bg-background border-border text-muted-foreground hover:border-input"
                                                    )}
                                                >
                                                    {d.label}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="space-y-4 text-xs">
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Tags & Keywords</h3>
                                        <div className="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto no-scrollbar">
                                            {tags.map((tag) => (
                                                <button 
                                                    key={tag.id}
                                                    onClick={() => toggleTag(tag.id)}
                                                    className={cn(
                                                        "px-2.5 py-1 rounded-md border transition-all",
                                                        selectedTagIds.includes(tag.id)
                                                            ? "bg-verdant-50 border-verdant-200 text-verdant-700 font-bold"
                                                            : "bg-muted/50 border-transparent text-muted-foreground hover:bg-muted"
                                                    )}
                                                >
                                                    {tag.name}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Creator</h3>
                                        <div className="space-y-2">
                                            {creators.slice(0, 5).map((creator) => (
                                                <button 
                                                    key={creator.id}
                                                    onClick={() => applyFilter('creator', String(filters.creator) === String(creator.id) ? undefined : creator.id)}
                                                    className={cn(
                                                        "flex items-center gap-2 w-full p-2 rounded-lg transition-all text-left",
                                                        String(filters.creator) === String(creator.id) ? "bg-muted shadow-sm" : "hover:bg-muted/50"
                                                    )}
                                                >
                                                    <Avatar className="w-6 h-6 border border-border">
                                                        <AvatarImage src={creator.avatar} />
                                                        <AvatarFallback className="text-[8px]">{creator.name.charAt(0)}</AvatarFallback>
                                                    </Avatar>
                                                    <span className="text-sm font-medium">{creator.name}</span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </motion.aside>
                        )}
                    </AnimatePresence>

                    {/* Main Content Area */}
                    <main className="flex-1 overflow-y-auto pl-8 pr-0 pt-0 min-w-0">
                        <div className="flex justify-between items-center mb-6">
                            <div className="flex items-center gap-4">
                                <Button 
                                    variant="outline" 
                                    size="icon" 
                                    className="rounded-xl lg:hidden"
                                    onClick={() => setIsFiltersVisible(!isFiltersVisible)}
                                >
                                    <Filter className="w-4 h-4" />
                                </Button>
                                <Tabs value={viewMode} onValueChange={(v: any) => setViewMode(v)}>
                                    <TabsList className="bg-white border border-border p-1 rounded-xl h-10">
                                        {VIEW_MODES.map((mode) => (
                                            <TabsTrigger 
                                                key={mode.value} 
                                                value={mode.value}
                                                className="rounded-lg px-3 data-[state=active]:bg-muted data-[state=active]:shadow-none"
                                            >
                                                <mode.icon className="w-4 h-4 mr-2" />
                                                <span className="hidden sm:inline">{mode.label}</span>
                                            </TabsTrigger>
                                        ))}
                                    </TabsList>
                                </Tabs>
                            </div>

                            <div className="flex items-center gap-2">
                                <span className="text-xs font-medium text-muted-foreground mr-2">Sort by</span>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="sm" className="rounded-xl border-input bg-white h-9">
                                            <ArrowUpDown className="w-3 h-3 mr-2" />
                                            {sort.includes('created_at') ? (sort.startsWith('-') ? 'Newest First' : 'Oldest First') : sort === 'difficulty' ? 'Difficulty' : 'Most Used'}
                                            <ChevronDown className="w-3 h-3 ml-2 text-muted-foreground" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="rounded-xl p-2 w-48">
                                        <DropdownMenuItem onClick={() => applyFilter('sort', '-created_at')} className="rounded-lg">Newest First</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => applyFilter('sort', 'created_at')} className="rounded-lg">Oldest First</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => applyFilter('sort', '-most_used')} className="rounded-lg">Most Used</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => applyFilter('sort', 'difficulty')} className="rounded-lg">Difficulty</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>

                        <AnimatePresence mode="wait">
                            {viewMode === 'gallery' ? (
                                <motion.div 
                                    initial={{ opacity: 0, y: 10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    exit={{ opacity: 0, y: -10 }}
                                    className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
                                >
                                    {questions.data.map((question) => (
                                        <QuestionCard 
                                            key={question.id} 
                                            question={question} 
                                            onClick={() => setSelectedQuestion(question)}
                                        />
                                    ))}
                                </motion.div>
                            ) : (
                                <motion.div
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 1 }}
                                    exit={{ opacity: 0 }}
                                    className="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
                                >
                                    <table className="w-full text-left border-collapse">
                                        <thead className="bg-muted/50 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border">
                                            <tr>
                                                <th className="px-6 py-4">Stem</th>
                                                <th className="px-6 py-4">Type</th>
                                                <th className="px-6 py-4">Difficulty</th>
                                                <th className="px-6 py-4">Usage</th>
                                                <th className="px-6 py-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border">
                                            {questions.data.map((q) => (
                                                <tr key={q.id} className={cn(
                                                    "group hover:bg-muted/30 transition-colors cursor-pointer",
                                                    viewMode === 'compact' ? "text-xs" : "text-sm"
                                                )} onClick={() => setSelectedQuestion(q)}>
                                                    <td className="px-6 py-4 font-medium max-w-md truncate">{q.stem}</td>
                                                    <td className="px-6 py-4">
                                                        <Badge variant="outline" className={cn("text-[10px] font-bold h-7", TYPE_CONFIG[q.type]?.color)}>
                                                            {q.type_label}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <span className="capitalize text-muted-foreground">{q.difficulty}</span>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <span className="text-muted-foreground font-medium">{q.usages_count} quizzes</span>
                                                    </td>
                                                    <td className="px-6 py-4 text-right">
                                                        <Button variant="ghost" size="icon-sm" className="opacity-0 group-hover:opacity-100">
                                                            <MoreHorizontal className="w-4 h-4" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Pagination */}
                        <div className="mt-8 flex justify-between items-center bg-white p-4 rounded-2xl border border-border">
                            <span className="text-xs text-muted-foreground font-medium">
                                Showing <span className="text-foreground">{questions.data.length}</span> of <span className="text-foreground">{questions.total}</span> questions
                            </span>
                            <div className="flex gap-2">
                                {questions.links.map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={link.active ? 'verdant' : 'outline'}
                                        size="icon-sm"
                                        disabled={!link.url}
                                        className={cn("w-8 h-8", !link.url && "opacity-50")}
                                        onClick={() => link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    </main>
                </div>
            </div>

            <TypePickerModal 
                open={isCreateModalOpen} 
                onOpenChange={setIsCreateModalOpen} 
            />

            {selectedQuestion && (
                <QuestionDetailDrawer 
                    questionId={selectedQuestion.id}
                    open={!!selectedQuestion}
                    onOpenChange={(open: boolean) => !open && setSelectedQuestion(null)}
                />
            )}
        </AdminLayout>
    );
}

function QuestionCard({ question, onClick }: { question: QuestionRow; onClick: () => void }) {
    const config = TYPE_CONFIG[question.type];
    const Icon = config.icon;
    const diff = DIFFICULTIES.find(d => d.value === question.difficulty);

    return (
        <motion.div
            layout
            whileHover={{ y: -4 }}
            className="group relative bg-card rounded-2xl border border-border shadow-sm overflow-hidden transition-all duration-300 hover:border-verdant-500/50 hover:shadow-xl hover:shadow-verdant-500/5 cursor-pointer"
            onClick={onClick}
        >
            {/* Top Stripe */}
            <div className={cn("h-1.5 w-full", config.bg)} />
            
            <div className="p-5">
                <div className="flex justify-between items-start mb-4">
                    <div className="flex items-center gap-2">
                        <div className={cn("w-8 h-8 rounded-lg flex items-center justify-center bg-muted", config.color)}>
                            <Icon className="w-4 h-4" />
                        </div>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{question.type_label}</span>
                    </div>
                    <Badge variant="outline" className={cn("text-[9px] font-bold border rounded-full px-2", diff?.color)}>
                        {question.difficulty_label}
                    </Badge>
                </div>

                <p className="text-sm font-medium text-foreground leading-relaxed line-clamp-3 mb-6 min-h-[4.5rem]">
                    {question.stem}
                </p>

                <div className="flex flex-wrap gap-1.5 mb-6">
                    {question.tags.map(tag => (
                        <span key={tag.id} className="text-[9px] font-bold px-2 py-0.5 rounded-md bg-muted text-muted-foreground border border-border">
                            {tag.name}
                        </span>
                    ))}
                    {question.tags.length === 0 && <span className="text-[9px] text-muted-foreground/40 italic">No tags</span>}
                </div>

                <div className="flex items-center justify-between pt-4 border-t border-border mt-auto">
                    <div className="flex items-center gap-2">
                        <Avatar className="w-6 h-6 border border-white">
                            <AvatarImage src={question.creator?.avatar} />
                            <AvatarFallback className="text-[8px] bg-muted">{question.creator?.name.charAt(0)}</AvatarFallback>
                        </Avatar>
                        <span className="text-[10px] font-medium text-muted-foreground">{question.creator?.name}</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-black/40 group-hover:text-verdant-600 transition-colors">
                        <History className="w-3 h-3" />
                        <span className="text-[10px] font-bold uppercase tracking-wider">{question.usages_count} Used</span>
                    </div>
                </div>
            </div>

            {/* Hover Actions */}
            <div className="absolute top-12 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col gap-1 translate-x-2 group-hover:translate-x-0 transition-transform">
                <Button variant="outline" size="icon-sm" className="w-8 h-8 rounded-lg bg-white/90 backdrop-blur" onClick={(e) => { e.stopPropagation(); router.visit(route('admin.questions.edit', question.id)); }}>
                    <Edit className="w-3.5 h-3.5" />
                </Button>
                <Button variant="outline" size="icon-sm" className="w-8 h-8 rounded-lg bg-white/90 backdrop-blur">
                    <Copy className="w-3.5 h-3.5" />
                </Button>
            </div>
        </motion.div>
    );
}
