import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  X, 
  MousePointer2, 
  CheckSquare, 
  Code2, 
  MessageSquareQuote,
  ArrowRight,
  Zap
} from 'lucide-react';
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogDescription 
} from '@/components/ui/dialog';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface QuestionTypeOption {
  id: string;
  name: string;
  description: string;
  examples: string[];
  icon: React.ElementType;
  href: string;
  color: string;
}

const QUESTION_TYPES: QuestionTypeOption[] = [
  {
    id: 'single_select',
    name: 'Single Select',
    description: 'Classic multiple choice with exactly one correct answer.',
    examples: ['Knowledge testing', 'Logic puzzles', 'Quick assessments'],
    icon: MousePointer2,
    href: '/admin/quizzes/create/single-select',
    color: 'verdant',
  },
  {
    id: 'multi_select',
    name: 'Multi Select',
    description: 'Multiple choice where candidates can pick one or more options.',
    examples: ['Skill matrices', 'Complex problem solving', 'Surveying'],
    icon: CheckSquare,
    href: '/admin/quizzes/create/multi-select',
    color: 'aurora',
  },
  {
    id: 'coding',
    name: 'Coding Challenge',
    description: 'Real-world coding problems with automated test cases.',
    examples: ['Algorithm design', 'Bug fixing', 'Backend development'],
    icon: Code2,
    href: '/admin/quizzes/create/coding',
    color: 'stone',
  },
  {
    id: 'rlhf',
    name: 'RLHF / Evaluation',
    description: 'Advanced Reinforcement Learning from Human Feedback logic.',
    examples: ['Model alignment', 'Human-in-the-loop training', 'Subjective grading'],
    icon: MessageSquareQuote,
    href: '/admin/quizzes/create/rlhf',
    color: 'citron',
  },
];

export function TypePickerModal({ 
  open, 
  onOpenChange 
}: { 
  open: boolean; 
  onOpenChange: (open: boolean) => void;
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl p-0 overflow-hidden bg-neutral-950 border-white/10 rounded-3xl!">
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="p-8 pb-4">
            <DialogHeader className="space-y-1">
              <div className="flex items-center gap-2 text-verdant-400 font-bold uppercase tracking-widest text-[10px]">
                <Zap className="w-3 h-3" />
                <span>New Question</span>
              </div>
              <DialogTitle className="text-3xl font-display font-bold text-white">Choose your canvas</DialogTitle>
              <DialogDescription className="text-white/50">
                Select the question type that best fits your evaluation goal.
              </DialogDescription>
            </DialogHeader>
          </div>

          {/* Grid */}
          <div className="p-8 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            {QUESTION_TYPES.map((type, index) => (
              <TypeCard key={type.id} type={type} index={index} />
            ))}
          </div>

          {/* Footer */}
          <div className="p-6 bg-white/5 border-t border-white/5 flex justify-between items-center text-xs text-white/40">
            <span>Need help choosing? Visit our <span className="text-verdant-400 hover:underline cursor-pointer">Question Guide</span>.</span>
            <button 
              onClick={() => onOpenChange(false)}
              className="px-4 py-2 rounded-full hover:bg-white/5 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

function TypeCard({ type, index }: { type: QuestionTypeOption; index: number }) {
  const Icon = type.icon;
  
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.1 }}
      whileHover={{ scale: 1.02 }}
      className="group relative"
    >
      <Link href={type.href} className="block">
        <div className={cn(
          "h-full p-6 rounded-2xl border bg-white/5 border-white/10 transition-all duration-300",
          "group-hover:border-verdant-500/50 group-hover:bg-verdant-500/5 group-hover:shadow-2xl group-hover:shadow-verdant-500/10"
        )}>
          <div className="flex justify-between items-start mb-6">
            <div className={cn(
              "w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-500 group-hover:rotate-12",
              type.color === 'verdant' && "bg-verdant-500/20 text-verdant-400",
              type.color === 'aurora' && "bg-indigo-500/20 text-indigo-400",
              type.color === 'stone' && "bg-neutral-500/20 text-neutral-400",
              type.color === 'citron' && "bg-yellow-500/20 text-yellow-400",
            )}>
              <Icon className="w-6 h-6" />
            </div>
            <ArrowRight className="w-5 h-5 text-white/20 group-hover:text-verdant-400 transition-all group-hover:translate-x-1" />
          </div>

          <h3 className="text-lg font-bold text-white mb-2 group-hover:text-verdant-400 transition-colors">
            {type.name}
          </h3>
          <p className="text-sm text-white/50 mb-6 leading-relaxed">
            {type.description}
          </p>

          <div className="space-y-2">
            <span className="text-[10px] font-bold uppercase tracking-wider text-white/30">Ideal for:</span>
            <div className="flex flex-wrap gap-2">
              {type.examples.map((ex) => (
                <span key={ex} className="text-[10px] bg-white/5 border border-white/10 px-2 py-0.5 rounded-full text-white/70">
                  {ex}
                </span>
              ))}
            </div>
          </div>
        </div>
      </Link>
    </motion.div>
  );
}
