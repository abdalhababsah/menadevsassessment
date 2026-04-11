import React, { useEffect, useState } from 'react';
import { 
  X, 
  History, 
  Eye, 
  Edit, 
  Copy, 
  Trash2, 
  ExternalLink,
  Info,
  Layers,
  CheckCircle2,
  Clock,
  User,
  ExternalLink as LinkIcon
} from 'lucide-react';
import { Drawer } from 'vaul';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import axios from 'axios';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

interface QuestionDetail {
    question: any;
    usages: { id: number; title: string; published_at: string }[];
}

export function QuestionDetailDrawer({ 
  questionId, 
  open, 
  onOpenChange 
}: { 
  questionId: number; 
  open: boolean; 
  onOpenChange: (open: boolean) => void;
}) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [data, setData] = useState<QuestionDetail | null>(null);

    useEffect(() => {
        if (open && questionId) {
            setLoading(true);
            setError(null);
            // Use window.axios to ensure CSRF and other defaults are applied
            const axiosInstance = (window as any).axios || axios;
            axiosInstance.get(route('admin.questions.api-show', questionId))
                .then((res: any) => {
                    setData(res.data);
                    setLoading(false);
                })
                .catch((err: any) => {
                    console.error('Failed to fetch question details', err);
                    setError('Failed to load question details. Please try again.');
                    setLoading(false);
                });
        }
    }, [open, questionId]);

    const typeConfig: Record<string, { label: string; color: string; bg: string }> = {
        single_select: { label: 'Single Select', color: 'text-verdant-700', bg: 'bg-verdant-50 border-verdant-100' },
        multi_select: { label: 'Multi Select', color: 'text-indigo-700', bg: 'bg-indigo-50 border-indigo-100' },
        coding: { label: 'Coding Challenge', color: 'text-stone-800', bg: 'bg-stone-100 border-stone-200' },
        rlhf: { label: 'RLHF / Evaluation', color: 'text-yellow-700', bg: 'bg-yellow-50 border-yellow-100' },
    };

    const config = data ? typeConfig[data.question.type] : null;

    return (
        <Drawer.Root direction="right" open={open} onOpenChange={onOpenChange}>
            <Drawer.Portal>
                <Drawer.Overlay className="fixed inset-0 bg-black/40 z-50 backdrop-blur-sm" />
                <Drawer.Content className="bg-card flex flex-col rounded-l-[32px] h-full w-full max-w-2xl fixed bottom-0 right-0 z-50 border-l border-border shadow-2xl overflow-hidden focus:outline-none">
                    {loading ? (
                        <div className="p-8 space-y-6">
                            <Drawer.Title className="sr-only">Loading Question Details</Drawer.Title>
                            <Drawer.Description className="sr-only">Please wait while we fetch the question data.</Drawer.Description>
                            <Skeleton className="h-8 w-48" />
                            <Skeleton className="h-32 w-full" />
                            <Skeleton className="h-64 w-full" />
                        </div>
                    ) : error ? (
                        <div className="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-4">
                            <div className="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center">
                                <X className="w-6 h-6" />
                            </div>
                            <div>
                                <Drawer.Title className="font-bold text-foreground">Error Loading Details</Drawer.Title>
                                <Drawer.Description className="text-sm text-muted-foreground mt-1">{error}</Drawer.Description>
                            </div>
                            <Button variant="outline" size="sm" onClick={() => { setData(null); setLoading(true); setError(null); /* trigger effect again */ }}>
                                Retry
                            </Button>
                        </div>
                    ) : data ? (
                        <div className="flex flex-col h-full">
                            {/* Header */}
                            <div className="p-8 pb-6 border-b border-border bg-white">
                                <div className="flex justify-between items-start mb-6">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline" className={cn("text-[10px] font-bold py-0.5", config?.bg)}>
                                                {config?.label}
                                            </Badge>
                                            <span className="text-xs text-muted-foreground">• v{data.question.version}</span>
                                        </div>
                                        <Drawer.Title className="text-2xl font-display font-bold text-foreground">Question Detail</Drawer.Title>
                                        <Drawer.Description className="sr-only">Viewing detailed information and usage statistics for this question.</Drawer.Description>
                                    </div>
                                    <button 
                                        onClick={() => onOpenChange(false)}
                                        className="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground hover:bg-muted/80 transition-colors"
                                    >
                                        <X className="w-5 h-5" />
                                    </button>
                                </div>

                                <div className="flex items-center gap-6 text-xs text-muted-foreground font-medium">
                                    <div className="flex items-center gap-2">
                                        <Clock className="w-3.5 h-3.5" />
                                        <span>Points: {data.question.points}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <History className="w-3.5 h-3.5" />
                                        <span>Used in {data.usages.length} Quiz{data.usages.length !== 1 ? 'zes' : ''}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <User className="w-3.5 h-3.5" />
                                        <span className="capitalize">{data.question.difficulty}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Scrollable Content */}
                            <div className="flex-1 overflow-y-auto bg-muted/10 p-8">
                                <Tabs defaultValue="overview" className="space-y-6">
                                    <TabsList className="bg-white border border-border p-1 rounded-xl w-full h-12">
                                        <TabsTrigger value="overview" className="flex-1 rounded-lg">Overview</TabsTrigger>
                                        <TabsTrigger value="preview" className="flex-1 rounded-lg">Preview</TabsTrigger>
                                        <TabsTrigger value="usage" className="flex-1 rounded-lg">Usage</TabsTrigger>
                                    </TabsList>

                                    <TabsContent value="overview" className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                        <div className="space-y-2">
                                            <label className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Question Stem</label>
                                            <div className="bg-white border border-border rounded-xl p-5 text-sm leading-relaxed text-foreground/90 prose prose-sm max-w-none shadow-sm">
                                                {data.question.stem}
                                            </div>
                                        </div>

                                        {data.question.instructions && (
                                            <div className="space-y-2">
                                                <label className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Instructions</label>
                                                <div className="bg-amber-50/50 border border-amber-100 rounded-xl p-5 text-sm italic text-amber-900 shadow-sm leading-relaxed">
                                                    {data.question.instructions}
                                                </div>
                                            </div>
                                        )}

                                        <div className="space-y-4">
                                            <label className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Meta Information</label>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div className="bg-white border border-border rounded-xl p-4 shadow-sm">
                                                    <span className="block text-[10px] font-bold text-muted-foreground uppercase mb-1">Time Limit</span>
                                                    <span className="font-medium">{data.question.time_limit_seconds ? `${data.question.time_limit_seconds}s` : 'Unbound'}</span>
                                                </div>
                                                <div className="bg-white border border-border rounded-xl p-4 shadow-sm">
                                                    <span className="block text-[10px] font-bold text-muted-foreground uppercase mb-1">Difficulty</span>
                                                    <span className="font-medium capitalize">{data.question.difficulty}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </TabsContent>

                                    <TabsContent value="preview" className="animate-in fade-in slide-in-from-bottom-2 duration-300">
                                        <div className="bg-white border-2 border-dashed border-border rounded-2xl p-8 text-center">
                                            <Eye className="w-8 h-8 text-muted-foreground/40 mx-auto mb-3" />
                                            <h3 className="text-sm font-bold">Candidate Side Preview</h3>
                                            <p className="text-xs text-muted-foreground mt-1 max-w-xs mx-auto">
                                                This reflects what candidates see during the assessment. 
                                                Interact with the components below.
                                            </p>
                                            
                                            <div className="mt-8 text-left border border-border rounded-xl overflow-hidden shadow-sm">
                                                <div className="bg-muted/50 p-4 border-b border-border text-[10px] font-bold flex justify-between items-center">
                                                    <span>RUNNING IN SIMULATION MODE</span>
                                                    <span className="text-verdant-600">LIVE PREVIEW</span>
                                                </div>
                                                <div className="p-6 bg-white min-h-[200px]">
                                                    <p className="font-medium mb-6">{data.question.stem}</p>
                                                    
                                                    {['single_select', 'multi_select'].includes(data.question.type) && (
                                                        <div className="space-y-3">
                                                            {data.question.options.map((opt: any, i: number) => (
                                                                <div key={i} className="flex items-center gap-3 p-4 border border-border rounded-xl hover:border-verdant-500 hover:bg-verdant-50/50 transition-all group cursor-pointer">
                                                                    <div className={cn(
                                                                        "w-5 h-5 rounded border border-border flex items-center justify-center transition-all",
                                                                        data.question.type === 'single_select' ? "rounded-full" : "rounded"
                                                                    )}>
                                                                        <div className="w-2.5 h-2.5 rounded-full bg-transparent group-hover:bg-verdant-500" />
                                                                    </div>
                                                                    <span className="text-sm">{opt.content}</span>
                                                                    {opt.is_correct && (
                                                                        <Badge variant="outline" className="ml-auto text-[8px] bg-emerald-50 text-emerald-600 border-emerald-100 font-bold uppercase py-0 px-1.5 h-4!">
                                                                            Correct Answer
                                                                        </Badge>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {data.question.type === 'coding' && (
                                                        <div className="bg-neutral-900 rounded-lg p-4 font-mono text-xs text-white">
                                                            <div className="flex justify-between items-center mb-4 text-neutral-500 border-b border-neutral-800 pb-2">
                                                                <span>Main.py</span>
                                                                <span>Python 3.10</span>
                                                            </div>
                                                            <pre className="text-verdant-400">
                                                                {data.question.coding_config?.starter_code || '# Start coding here...'}
                                                            </pre>
                                                        </div>
                                                    )}

                                                    {data.question.type === 'rlhf' && (
                                                        <div className="flex flex-col items-center justify-center py-12 text-center space-y-4">
                                                            <Layers className="w-10 h-10 text-verdant-500 mx-auto" />
                                                            <div>
                                                                <p className="font-bold">RLHF Protocol</p>
                                                                <p className="text-xs text-muted-foreground">Detailed logic view available in Full Builder</p>
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </TabsContent>

                                    <TabsContent value="usage" className="space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                        <div className="p-4 bg-muted/40 rounded-xl border border-border flex items-start gap-3">
                                            <Info className="w-4 h-4 text-muted-foreground mt-0.5" />
                                            <p className="text-xs text-muted-foreground leading-relaxed">
                                                This question is linked to {data.usages.length} active assessments. 
                                                Editing the stem will create a new version of the question.
                                            </p>
                                        </div>

                                        <div className="space-y-2">
                                            {data.usages.length === 0 ? (
                                                <div className="py-12 text-center border-2 border-dashed border-border rounded-xl text-muted-foreground text-xs">
                                                    No active usages found.
                                                </div>
                                            ) : (
                                                data.usages.map((quiz) => (
                                                    <div key={quiz.id} className="bg-white border border-border rounded-xl p-4 flex justify-between items-center group shadow-sm hover:border-verdant-300 transition-colors">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-8 h-8 rounded-lg bg-verdant-50 text-verdant-600 flex items-center justify-center font-bold text-xs uppercase">
                                                                QZ
                                                            </div>
                                                            <div>
                                                                <p className="text-sm font-bold text-foreground">{quiz.title}</p>
                                                                <p className="text-[10px] text-muted-foreground">Published {quiz.published_at}</p>
                                                            </div>
                                                        </div>
                                                        <Link 
                                                            href={route('admin.quizzes.edit', quiz.id)}
                                                            className="w-8 h-8 rounded-full hover:bg-muted flex items-center justify-center text-muted-foreground hover:text-foreground transition-all opacity-0 group-hover:opacity-100"
                                                        >
                                                            <ExternalLink className="w-4 h-4" />
                                                        </Link>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    </TabsContent>
                                </Tabs>
                            </div>

                            {/* Footer Actions */}
                            <div className="p-8 pb-10 border-t border-border bg-white flex items-center gap-3">
                                <Button 
                                    className="flex-1 rounded-xl h-12 shadow-lg shadow-verdant-500/10"
                                    onClick={() => router.visit(route('admin.questions.edit', data.question.id))}
                                >
                                    <Edit className="w-4 h-4 mr-2" />
                                    Edit Full Question
                                </Button>
                                <Button 
                                    variant="outline" 
                                    size="icon" 
                                    className="w-12 h-12 rounded-xl"
                                    onClick={() => {
                                        router.post(route('admin.questions.duplicate', data.question.id), {}, {
                                            onSuccess: () => onOpenChange(false)
                                        });
                                    }}
                                    title="Duplicate Question"
                                >
                                    <Copy className="w-4 h-4" />
                                </Button>

                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button 
                                            variant="outline" 
                                            size="icon" 
                                            className="w-12 h-12 rounded-xl text-rose-500 hover:bg-rose-50 hover:text-rose-600 border-rose-100"
                                            title="Delete Question"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This action cannot be undone. This will permanently delete the question
                                                "{data.question.stem}" from the library.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction 
                                                variant="destructive"
                                                onClick={() => {
                                                    router.delete(route('admin.questions.destroy', data.question.id), {
                                                        onSuccess: () => onOpenChange(false)
                                                    });
                                                }}
                                            >
                                                Delete Question
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </div>
                    ) : (
                        <div className="flex-1 flex flex-col items-center justify-center text-muted-foreground p-8 text-center">
                            <Layers className="w-12 h-12 mb-4 opacity-20" />
                            <p>Select a question to view full details and analysis.</p>
                        </div>
                    )}
                </Drawer.Content>
            </Drawer.Portal>
        </Drawer.Root>
    );
}
