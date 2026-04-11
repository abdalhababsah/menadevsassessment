import React, { useState, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { 
  ArrowLeft, 
  Check, 
  ChevronRight, 
  ChevronLeft, 
  Plus, 
  Trash2, 
  GripVertical, 
  Settings2,
  Cpu,
  MousePointer2,
  FormInput,
  Files,
  Eye,
  Save,
  Info,
  Layers,
  Sparkles,
  X
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Slider } from '@/components/ui/slider';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { 
  Accordion, 
  AccordionContent, 
  AccordionItem, 
  AccordionTrigger 
} from '@/components/ui/accordion';
import { cn } from '@/lib/utils';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  DndContext, 
  closestCenter, 
  KeyboardSensor, 
  PointerSensor, 
  useSensor, 
  useSensors 
} from '@dnd-kit/core';
import { 
  arrayMove, 
  SortableContext, 
  sortableKeyboardCoordinates, 
  verticalListSortingStrategy, 
  useSortable 
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

const STEPS = [
  { id: 'basics', title: 'Basics', icon: Info },
  { id: 'generation', title: 'Generation', icon: Cpu },
  { id: 'criteria', title: 'Criteria', icon: Layers },
  { id: 'forms', title: 'Forms', icon: FormInput },
  { id: 'guidelines', title: 'Guidelines', icon: Files },
  { id: 'preview', title: 'Preview', icon: Eye },
];

const MODELS = [
  { id: 'gpt-4o', name: 'GPT-4o', provider: 'OpenAI', description: 'Advanced reasoning and creativity.' },
  { id: 'claude-3-5-sonnet', name: 'Claude 3.5 Sonnet', provider: 'Anthropic', description: 'Best-in-class coding and nuance.' },
  { id: 'gemini-1.5-pro', name: 'Gemini 1.5 Pro', provider: 'Google', description: 'Massive context and multi-modal.' },
  { id: 'llama-3-70b', name: 'Llama 3 70B', provider: 'Meta', description: 'Open-weights high performance.' },
];

export default function Rlhf({ tags, initialData, quiz_id }: any) {
  const [currentStep, setCurrentStep] = useState(0);
  const { data, setData, post, put, processing, errors } = useForm({
    stem: initialData?.stem || '',
    instructions: initialData?.instructions || '',
    difficulty: initialData?.difficulty || 'medium',
    points: initialData?.points || 20,
    tags: initialData?.tags || [],
    quiz_section_id: initialData?.quiz_section_id || null,
    
    // Config
    number_of_turns: initialData?.rlhf_config?.number_of_turns || 2,
    candidate_input_mode: initialData?.rlhf_config?.candidate_input_mode || 'text',
    model_a: initialData?.rlhf_config?.model_a || 'gpt-4o',
    model_b: initialData?.rlhf_config?.model_b || 'claude-3-5-sonnet',
    generation_params: initialData?.rlhf_config?.generation_params || { temperature: 0.7, max_tokens: 1024 },
    guidelines_markdown: initialData?.rlhf_config?.guidelines_markdown || '',
    
    // Criteria
    criteria: initialData?.rlhf_criteria || [
      { name: 'Helpfulness', description: 'Is the response helpful?', scale_type: 'likert_5', position: 0 }
    ],
    
    // Forms
    form_fields: initialData?.rlhf_form_fields || [],
  });

  const nextStep = () => setCurrentStep(Math.min(currentStep + 1, STEPS.length - 1));
  const prevStep = () => setCurrentStep(Math.max(currentStep - 1, 0));

  const wordCount = useMemo(() => data.stem.trim().split(/\s+/).filter(Boolean).length, [data.stem]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (initialData?.id) {
        put(route('admin.questions.update.rlhf', initialData.id));
    } else {
        post(route('admin.questions.store.rlhf'));
    }
  };

  return (
    <AdminLayout>
      <Head title="RLHF Builder | The Library" />

      <div className="flex flex-col h-full -m-8">
        {/* Top Sticky Header */}
        <header className="sticky top-0 z-30 bg-white border-b border-border p-6 shadow-sm">
          <div className="max-w-7xl mx-auto flex justify-between items-center gap-6">
            <div className="flex items-center gap-4">
              <Link href={route('admin.questions.index')}>
                <Button variant="ghost" size="icon" className="rounded-full">
                  <ArrowLeft className="w-5 h-5" />
                </Button>
              </Link>
              <div>
                <h1 className="text-xl font-display font-bold">RLHF Protocol Builder</h1>
                <div className="flex items-center gap-2 mt-0.5">
                  <span className="text-[10px] bg-yellow-500/10 text-yellow-700 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Advanced</span>
                  <span className="text-xs text-muted-foreground italic">Draft autosaved locally</span>
                </div>
              </div>
            </div>

            <div className="flex-1 max-w-2xl px-8">
              <div className="flex justify-between relative">
                {STEPS.map((step, idx) => {
                  const Icon = step.icon;
                  const active = idx === currentStep;
                  const completed = idx < currentStep;
                  return (
                    <button 
                        key={step.id} 
                        onClick={() => setCurrentStep(idx)}
                        className="flex flex-col items-center group relative z-10"
                    >
                        <div className={cn(
                            "w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300",
                            active ? "bg-foreground border-foreground text-background scale-110 shadow-lg" : 
                            completed ? "bg-verdant-500 border-verdant-500 text-white" :
                            "bg-white border-border text-muted-foreground hover:border-input"
                        )}>
                            {completed ? <Check className="w-5 h-5" /> : <Icon className="w-5 h-5" />}
                        </div>
                        <span className={cn(
                            "text-[10px] mt-2 font-bold uppercase tracking-widest transition-opacity duration-300",
                            active ? "opacity-100" : "opacity-40 group-hover:opacity-100"
                        )}>
                            {step.title}
                        </span>
                    </button>
                  );
                })}
                <div className="absolute top-5 left-0 w-full h-0.5 bg-muted -z-0" />
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button variant="outline" onClick={() => router.get(route('admin.questions.index'))}>Cancel</Button>
              <Button variant="verdant" disabled={processing} onClick={handleSubmit}>
                <Save className="w-4 h-4 mr-2" />
                Finalize Logic
              </Button>
            </div>
          </div>
        </header>

        {/* Scrollable Form Content */}
        <main className="flex-1 overflow-y-auto bg-muted/20 p-8 pb-32">
          <div className="max-w-5xl mx-auto">
            <AnimatePresence mode="wait">
              <motion.div
                key={STEPS[currentStep].id}
                initial={{ opacity: 0, x: 10 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -10 }}
                className="space-y-8"
              >
                {/* STEP 1: BASICS */}
                {currentStep === 0 && (
                  <div className="space-y-8 animate-in fade-in duration-500">
                    <section className="bg-white border border-border rounded-2xl p-8 shadow-sm">
                      <div className="flex items-start justify-between mb-8">
                        <div className="space-y-1">
                          <h2 className="text-xl font-bold font-display">Core Narrative</h2>
                          <p className="text-sm text-muted-foreground">Define the scenario or prompt the candidate needs to evaluate.</p>
                        </div>
                        <div className="text-right">
                          <div className={cn(
                            "text-[10px] font-bold px-2 py-1 rounded border inline-block",
                            wordCount > 50 ? "bg-verdant-50 text-verdant-600 border-verdant-100" : "bg-muted text-muted-foreground"
                          )}>
                            {wordCount} WORDS
                          </div>
                        </div>
                      </div>

                      <div className="space-y-6">
                        <div className="space-y-3">
                          <Label className="text-xs font-bold uppercase tracking-widest opacity-50">Base Stem / Prompt</Label>
                          <Textarea 
                            placeholder="Type the question stem or initial system prompt here..."
                            className="text-lg font-medium min-h-[180px] rounded-xl focus:ring-verdant-500 focus:border-verdant-500 border-border bg-muted/10 p-6"
                            value={data.stem}
                            onChange={(e) => setData('stem', e.target.value)}
                          />
                        </div>

                        <div className="space-y-3">
                          <Label className="text-xs font-bold uppercase tracking-widest opacity-50">Private Instructions (Admin only)</Label>
                          <Textarea 
                            placeholder="Internal grading guidelines or setup context..."
                            className="text-sm rounded-xl"
                            value={data.instructions}
                            onChange={(e) => setData('instructions', e.target.value)}
                          />
                        </div>
                      </div>
                    </section>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div className="bg-white border border-border rounded-2xl p-6 shadow-sm">
                        <Label className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Difficulty</Label>
                        <div className="flex gap-2 mt-3">
                          {['easy', 'medium', 'hard'].map((d) => (
                            <button 
                                key={d}
                                onClick={() => setData('difficulty', d)}
                                className={cn(
                                    "flex-1 py-2 px-3 rounded-lg text-xs font-bold border transition-all",
                                    data.difficulty === d ? "bg-foreground text-background border-foreground" : "bg-background border-border text-muted-foreground hover:border-input"
                                )}
                            >
                                {d.toUpperCase()}
                            </button>
                          ))}
                        </div>
                      </div>

                      <div className="bg-white border border-border rounded-2xl p-6 shadow-sm">
                          <Label className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Point Magnitude</Label>
                          <div className="mt-3">
                            <Input 
                                type="number" 
                                value={data.points} 
                                onChange={(e) => setData('points', parseInt(e.target.value))}
                                className="h-10 text-lg font-bold rounded-xl" 
                            />
                          </div>
                      </div>

                      <div className="bg-white border border-border rounded-2xl p-6 shadow-sm">
                          <Label className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Tags</Label>
                          <div className="mt-3 flex flex-wrap gap-2">
                            {/* Tags would go here, skipping logic for brevity in this step */}
                            <Button variant="outline" size="sm" className="rounded-lg h-10 w-full border-dashed">
                                <Plus className="w-4 h-4 mr-2" />
                                Add Tags
                            </Button>
                          </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* STEP 2: GENERATION */}
                {currentStep === 1 && (
                  <div className="space-y-8 animate-in fade-in duration-500">
                    <section className="bg-white border border-border rounded-2xl p-8 shadow-sm">
                        <div className="flex items-center gap-3 mb-8">
                            <div className="w-10 h-10 rounded-xl bg-verdant-500 text-white flex items-center justify-center">
                                <Plus className="w-6 h-6" />
                            </div>
                            <div>
                                <h2 className="text-xl font-bold font-display">Generation Protocol</h2>
                                <p className="text-sm text-muted-foreground">Configure how model responses are created and presented.</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div className="space-y-8">
                                <div className="space-y-6">
                                    <div className="flex justify-between items-center">
                                        <Label className="font-bold">Number of Turns</Label>
                                        <Badge variant="outline" className="bg-muted">{data.number_of_turns} Turns</Badge>
                                    </div>
                                    <Slider 
                                        value={[data.number_of_turns]} 
                                        min={1} 
                                        max={10} 
                                        step={1}
                                        onValueChange={([val]) => setData('number_of_turns', val)}
                                        className="py-4"
                                    />
                                    <p className="text-xs text-muted-foreground italic">Total conversation depth per candidate attempt.</p>
                                </div>

                                <div className="space-y-4">
                                    <Label className="font-bold">Candidate Input Mode</Label>
                                    <div className="grid grid-cols-2 gap-3">
                                        <button 
                                            onClick={() => setData('candidate_input_mode', 'text')}
                                            className={cn(
                                                "p-4 rounded-xl border text-left transition-all",
                                                data.candidate_input_mode === 'text' ? "border-verdant-500 bg-verdant-50/50" : "border-border hover:border-input"
                                            )}
                                        >
                                            <div className="font-bold text-sm mb-1">Standard Text</div>
                                            <div className="text-[10px] text-muted-foreground">Markdown supported chat inputs.</div>
                                        </button>
                                        <button 
                                            onClick={() => setData('candidate_input_mode', 'fixed')}
                                            className={cn(
                                                "p-4 rounded-xl border text-left transition-all",
                                                data.candidate_input_mode === 'fixed' ? "border-verdant-500 bg-verdant-50/50" : "border-border hover:border-input"
                                            )}
                                        >
                                            <div className="font-bold text-sm mb-1">Fixed Prompt</div>
                                            <div className="text-[10px] text-muted-foreground">Pre-defined prompts only (SXS).</div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-6 bg-muted/10 rounded-2xl p-6 border border-border">
                                <h3 className="text-sm font-bold flex items-center gap-2">
                                    <Settings2 className="w-4 h-4" />
                                    Model Parameters
                                </h3>
                                <Accordion type="single" collapsible className="w-full">
                                    <AccordionItem value="temp" className="border-none">
                                        <AccordionTrigger className="hover:no-underline py-3 px-0">
                                            <div className="flex justify-between w-full pr-4 text-xs font-medium">
                                                <span>Temperature</span>
                                                <span className="text-verdant-600">{data.generation_params.temperature}</span>
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pt-2">
                                            <Slider 
                                                value={[data.generation_params.temperature]} 
                                                min={0} 
                                                max={2} 
                                                step={0.1}
                                                onValueChange={([val]) => setData('generation_params', { ...data.generation_params, temperature: val })}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>
                                    <AccordionItem value="tokens" className="border-none">
                                        <AccordionTrigger className="hover:no-underline py-3 px-0">
                                            <div className="flex justify-between w-full pr-4 text-xs font-medium">
                                                <span>Max Tokens</span>
                                                <span className="text-verdant-600">{data.generation_params.max_tokens}</span>
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="pt-2">
                                            <Slider 
                                                value={[data.generation_params.max_tokens]} 
                                                min={64} 
                                                max={4096} 
                                                step={64}
                                                onValueChange={([val]) => setData('generation_params', { ...data.generation_params, max_tokens: val })}
                                            />
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </div>
                        </div>
                    </section>

                    <section className="space-y-6">
                        <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Model Selection (Side-by-Side)</Label>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {MODELS.map((model) => {
                                const isA = data.model_a === model.id;
                                const isB = data.model_b === model.id;
                                return (
                                    <div 
                                        key={model.id}
                                        className={cn(
                                            "p-5 rounded-2xl border transition-all cursor-pointer",
                                            (isA || isB) ? "border-verdant-500 bg-verdant-50" : "bg-white border-border hover:border-input"
                                        )}
                                        onClick={() => {
                                            if (isA) setData('model_a', '');
                                            else if (isB) setData('model_b', '');
                                            else if (!data.model_a) setData('model_a', model.id);
                                            else if (!data.model_b) setData('model_b', model.id);
                                        }}
                                    >
                                        <div className="flex justify-between items-start mb-4">
                                            <div className="w-8 h-8 flex items-center justify-center rounded-lg bg-muted">
                                                <Cpu className="w-4 h-4 text-muted-foreground" />
                                            </div>
                                            <div className="flex gap-1">
                                                {isA && <Badge className="bg-verdant-500 text-white">MODEL A</Badge>}
                                                {isB && <Badge className="bg-indigo-500 text-white">MODEL B</Badge>}
                                            </div>
                                        </div>
                                        <h4 className="font-bold text-sm">{model.name}</h4>
                                        <p className="text-[10px] text-muted-foreground mt-1">{model.description}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                  </div>
                )}

                {/* STEP 3: CRITERIA */}
                {currentStep === 2 && (
                  <div className="space-y-8 animate-in fade-in duration-500 flex flex-col items-center">
                    <CriteriaBuilder data={data} setData={setData} />
                  </div>
                )}

                {/* STEP 4: FORMS */}
                {currentStep === 3 && (
                  <div className="space-y-8 animate-in fade-in duration-500">
                    <section className="bg-white border border-border rounded-2xl overflow-hidden shadow-sm">
                        <Tabs defaultValue="pre" className="w-full">
                            <div className="bg-muted/30 border-b border-border p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <h3 className="font-bold text-lg">Interaction Forms</h3>
                                    <p className="text-xs text-muted-foreground">Gather qualitative data from candidates at specific stages.</p>
                                </div>
                                <TabsList className="bg-white border border-border p-1 rounded-xl h-10">
                                    <TabsTrigger value="pre" className="rounded-lg text-xs font-bold uppercase tracking-widest px-4">Pre-Prompt</TabsTrigger>
                                    <TabsTrigger value="post" className="rounded-lg text-xs font-bold uppercase tracking-widest px-4">Post-Prompt</TabsTrigger>
                                    <TabsTrigger value="rewrite" className="rounded-lg text-xs font-bold uppercase tracking-widest px-4">Post-Rewrite</TabsTrigger>
                                </TabsList>
                            </div>
                            
                            {['pre', 'post', 'rewrite'].map((stage) => (
                                <TabsContent key={stage} value={stage} className="p-8 mt-0 min-h-[400px]">
                                    <div className="max-w-3xl mx-auto space-y-6">
                                        <div className="flex justify-between items-center mb-6">
                                            <div>
                                                <h4 className="font-bold uppercase text-[11px] tracking-widest">Construct Workflow: {stage.toUpperCase()}</h4>
                                                <p className="text-xs text-muted-foreground mt-1">Add input fields for this specific stage.</p>
                                            </div>
                                            <Button variant="outline" size="sm" className="rounded-lg font-bold">
                                                <Plus className="w-4 h-4 mr-2" />
                                                Add Field
                                            </Button>
                                        </div>
                                        
                                        <div className="py-12 text-center border-2 border-dashed border-border rounded-2xl text-muted-foreground/50">
                                            <FormInput className="w-10 h-10 mx-auto mb-4 opacity-20" />
                                            <p className="text-sm font-medium">No fields defined for this stage</p>
                                            <p className="text-[10px] uppercase tracking-wider mt-1 underline cursor-pointer hover:text-foreground">Import Template</p>
                                        </div>
                                    </div>
                                </TabsContent>
                            ))}
                        </Tabs>
                    </section>
                  </div>
                )}

                {/* STEP 5: GUIDELINES */}
                {currentStep === 4 && (
                  <div className="space-y-6 animate-in fade-in duration-500">
                    <div className="bg-white border border-border rounded-2xl p-8 shadow-sm">
                        <div className="flex justify-between items-center mb-8">
                            <div>
                                <h2 className="text-xl font-bold font-display">Evaluation Guidelines</h2>
                                <p className="text-sm text-muted-foreground">The "Golden Rulebook" shown to candidates on the assessment detail rail.</p>
                            </div>
                            <Sparkles className="w-6 h-6 text-verdant-600" />
                        </div>
                        <Textarea 
                            placeholder="# Evaluation Standard\n\nExplain exactly what makes a 'good' or 'bad' response..."
                            className="min-h-[450px] font-mono text-sm rounded-xl bg-muted/5 p-8"
                            value={data.guidelines_markdown}
                            onChange={(e) => setData('guidelines_markdown', e.target.value)}
                        />
                    </div>
                  </div>
                )}

                {/* STEP 6: PREVIEW */}
                {currentStep === 5 && (
                  <div className="space-y-8 animate-in fade-in duration-500">
                     <div className="bg-black text-white p-1 rounded-2xl border-4 border-black shadow-2xl overflow-hidden">
                        <div className="bg-neutral-900 px-6 py-3 border-b border-white/10 flex justify-between items-center">
                            <span className="text-[10px] font-bold tracking-[0.2em]">CANDIDATE SIMULATION MODE</span>
                            <div className="flex gap-1.5">
                                <div className="w-2.5 h-2.5 rounded-full bg-rose-500" />
                                <div className="w-2.5 h-2.5 rounded-full bg-amber-500" />
                                <div className="w-2.5 h-2.5 rounded-full bg-emerald-500" />
                            </div>
                        </div>
                        <div className="bg-card min-h-[600px] text-foreground p-12">
                            <div className="max-w-3xl mx-auto space-y-8">
                                <header className="space-y-2">
                                    <h1 className="text-3xl font-display font-bold">{data.stem || 'Untitled Protocol'}</h1>
                                    <p className="text-muted-foreground italic text-sm">Follow the instructions carefully evaluate AI model results.</p>
                                </header>
                                <div className="p-8 bg-muted rounded-2xl border border-border">
                                    <div className="flex items-center gap-2 mb-4 text-[11px] font-bold text-verdant-600">
                                        <Plus className="w-3 h-3" />
                                        <span>INPUT TASK</span>
                                    </div>
                                    <p className="text-lg font-medium">Candidate will see their interaction area here...</p>
                                </div>
                                <div className="grid grid-cols-2 gap-6 pt-12 border-t border-border">
                                    <div className="p-6 bg-white border border-border rounded-xl shadow-sm space-y-3">
                                        <Badge className="bg-verdant-500">MODEL A</Badge>
                                        <div className="h-4 w-full bg-muted rounded-full animate-pulse" />
                                        <div className="h-4 w-3/4 bg-muted rounded-full animate-pulse" />
                                    </div>
                                    <div className="p-6 bg-white border border-border rounded-xl shadow-sm space-y-3">
                                        <Badge className="bg-indigo-500">MODEL B</Badge>
                                        <div className="h-4 w-full bg-muted rounded-full animate-pulse" />
                                        <div className="h-4 w-2/3 bg-muted rounded-full animate-pulse" />
                                    </div>
                                </div>
                            </div>
                        </div>
                     </div>
                  </div>
                )}
              </motion.div>
            </AnimatePresence>
          </div>
        </main>

        {/* Floating Bottom Nav */}
        <footer className="fixed bottom-8 left-1/2 -translate-x-1/2 z-40 bg-white/80 backdrop-blur-xl border border-border px-4 py-3 rounded-full shadow-2xl flex items-center gap-2">
          <Button 
            variant="ghost" 
            size="sm" 
            className="rounded-full h-10 px-6"
            onClick={prevStep}
            disabled={currentStep === 0}
          >
            <ChevronLeft className="w-4 h-4 mr-2" />
            Back
          </Button>
          <div className="w-px h-6 bg-border mx-2" />
          <span className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground px-4">
            Step {currentStep + 1} of {STEPS.length}
          </span>
          <div className="w-px h-6 bg-border mx-2" />
          <Button 
            variant="verdant" 
            size="sm" 
            className="rounded-full h-10 px-8"
            onClick={nextStep}
            disabled={currentStep === STEPS.length - 1}
          >
            Continue
            <ChevronRight className="w-4 h-4 ml-2" />
          </Button>
        </footer>
      </div>
    </AdminLayout>
  );
}

function CriteriaBuilder({ data, setData }: any) {
  const [editingCriterion, setEditingCriterion] = useState<number | null>(null);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  function handleDragEnd(event: any) {
    const { active, over } = event;
    if (active.id !== over.id) {
      const oldIndex = data.criteria.findIndex((c: any) => c.name === active.id);
      const newIndex = data.criteria.findIndex((c: any) => c.name === over.id);
      setData('criteria', arrayMove(data.criteria, oldIndex, newIndex));
    }
  }

  const addCriterion = () => {
    setData('criteria', [
      ...data.criteria,
      { name: `New Criterion ${data.criteria.length + 1}`, description: '', scale_type: 'likert_5', position: data.criteria.length }
    ]);
  };

  const removeCriterion = (index: number) => {
    setData('criteria', data.criteria.filter((_: any, i: number) => i !== index));
    setEditingCriterion(null);
  };

  return (
    <div className="w-full grid grid-cols-12 gap-8 items-start">
        <div className="col-span-12 lg:col-span-7 space-y-6">
            <div className="flex justify-between items-center mb-4">
                <div>
                    <h2 className="text-xl font-bold font-display">Evaluation Criteria</h2>
                    <p className="text-xs text-muted-foreground mt-1">Determine what candidates should grade model responses against.</p>
                </div>
                <Button onClick={addCriterion} variant="verdant" size="sm" className="rounded-xl">
                    <Plus className="w-4 h-4 mr-2" />
                    Add Criterion
                </Button>
            </div>

            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                <SortableContext items={data.criteria.map((c: any) => c.name)} strategy={verticalListSortingStrategy}>
                    <div className="space-y-3">
                        {data.criteria.map((criterion: any, index: number) => (
                            <SortableCriterionRow 
                                key={criterion.name} 
                                criterion={criterion} 
                                index={index}
                                active={editingCriterion === index}
                                onEdit={() => setEditingCriterion(index)}
                                onRemove={() => removeCriterion(index)}
                            />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>
        </div>

        <AnimatePresence>
            {editingCriterion !== null && (
                <motion.div 
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    exit={{ opacity: 0, x: 20 }}
                    className="col-span-12 lg:col-span-5 bg-white border border-border rounded-2xl p-8 shadow-xl sticky top-32"
                >
                    <div className="flex justify-between items-center mb-8">
                        <h3 className="font-bold">Edit Criterion</h3>
                        <Button variant="ghost" size="icon" onClick={() => setEditingCriterion(null)}>
                            <X className="w-4 h-4" />
                        </Button>
                    </div>

                    <div className="space-y-6">
                        <div className="space-y-2">
                            <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Criterion Name</Label>
                            <Input 
                                value={data.criteria[editingCriterion].name} 
                                onChange={(e) => {
                                    const next = [...data.criteria];
                                    next[editingCriterion].name = e.target.value;
                                    setData('criteria', next);
                                }}
                                className="rounded-xl font-bold"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Description / Guide</Label>
                            <Textarea 
                                value={data.criteria[editingCriterion].description} 
                                onChange={(e) => {
                                    const next = [...data.criteria];
                                    next[editingCriterion].description = e.target.value;
                                    setData('criteria', next);
                                }}
                                className="rounded-xl min-h-[100px] text-xs"
                                placeholder="Explain how to score this..."
                            />
                        </div>

                        <div className="space-y-4">
                            <Label className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Scale Architecture</Label>
                            <div className="grid grid-cols-1 gap-2">
                                {[
                                    { id: 'likert_5', name: '5-Point Likert', desc: '1-5 scale (Strongly Disagree to Strongly Agree)' },
                                    { id: 'binary', name: 'Binary (Yes/No)', desc: 'Simple pass/fail evaluation' },
                                    { id: 'sxs', name: 'SXS Preferred', desc: 'Direct selection of Model A vs Model B' }
                                ].map((type) => (
                                    <button 
                                        key={type.id}
                                        onClick={() => {
                                            const next = [...data.criteria];
                                            next[editingCriterion].scale_type = type.id;
                                            setData('criteria', next);
                                        }}
                                        className={cn(
                                            "p-4 rounded-xl border text-left transition-all",
                                            data.criteria[editingCriterion].scale_type === type.id ? "border-verdant-500 bg-verdant-50" : "border-border hover:border-input"
                                        )}
                                    >
                                        <div className="font-bold text-xs">{type.name}</div>
                                        <div className="text-[10px] text-muted-foreground">{type.desc}</div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    </div>
  );
}

function SortableCriterionRow({ criterion, index, active, onEdit, onRemove }: any) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging
  } = useSortable({ id: criterion.name });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: isDragging ? 50 : 0
  };

  return (
    <div 
        ref={setNodeRef} 
        style={style} 
        className={cn(
            "group flex items-center gap-4 p-4 rounded-xl border transition-all cursor-pointer",
            active ? "border-verdant-500 bg-white ring-2 ring-verdant-500/10" : "bg-white border-border hover:border-input",
            isDragging && "opacity-50"
        )}
        onClick={onEdit}
    >
        <div {...attributes} {...listeners} className="text-muted-foreground cursor-grab active:cursor-grabbing hover:text-foreground transition-colors">
            <GripVertical className="w-5 h-5" />
        </div>
        <div className="flex-1">
            <h4 className="font-bold text-sm tracking-tight">{criterion.name}</h4>
            <p className="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mt-0.5">{criterion.scale_type.replace('_', ' ')}</p>
        </div>
        <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <Button variant="ghost" size="icon-sm" onClick={(e) => { e.stopPropagation(); onRemove(); }} className="text-rose-500 hover:text-rose-600">
                <Trash2 className="w-4 h-4" />
            </Button>
        </div>
    </div>
  );
}
