import React from 'react';
import useEmblaCarousel from 'embla-carousel-react';
import { motion } from 'framer-motion';
import { ChevronRight, ChevronLeft, Plus, Clock, Users, Trophy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface Quiz {
  id: number;
  title: string;
  status: string;
  attempts_count: number;
  avg_score: number;
  last_activity: string;
}

export function QuizCarousel({ quizzes }: { quizzes: Quiz[] }) {
  const [emblaRef, emblaApi] = useEmblaCarousel({ 
    align: 'start',
    containScroll: 'trimSnaps'
  });

  const scrollPrev = React.useCallback(() => emblaApi && emblaApi.scrollPrev(), [emblaApi]);
  const scrollNext = React.useCallback(() => emblaApi && emblaApi.scrollNext(), [emblaApi]);

  return (
    <section className="space-y-4 mb-8">
      <div className="flex justify-between items-end">
        <div>
          <h2 className="text-2xl font-bold font-display tracking-tight">Your Quizzes</h2>
          <p className="text-sm text-muted-foreground">Recent assessments and their performance</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="icon" className="rounded-full w-8 h-8" onClick={scrollPrev}>
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <Button variant="outline" size="icon" className="rounded-full w-8 h-8" onClick={scrollNext}>
            <ChevronRight className="w-4 h-4" />
          </Button>
        </div>
      </div>

      <div className="overflow-hidden" ref={emblaRef}>
        <div className="flex gap-6 py-4 px-1">
          {quizzes.map((quiz) => (
            <div key={quiz.id} className="flex-[0_0_280px] min-w-0">
              <motion.div
                whileHover={{ scale: 1.02, y: -4 }}
                className="group p-5 rounded-2xl bg-card border border-border shadow-sm hover:shadow-xl transition-all duration-300 h-full flex flex-col justify-between"
              >
                <div className="space-y-4">
                  <div className="flex justify-between items-start">
                    <div className="p-2 rounded-lg bg-primary/5 text-primary">
                      <Clock className="w-4 h-4" />
                    </div>
                    <div className={cn(
                      "text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full",
                      quiz.status === 'published' ? "bg-emerald-500/10 text-emerald-500" : "bg-amber-500/10 text-amber-500"
                    )}>
                      {quiz.status}
                    </div>
                  </div>
                  
                  <h3 className="font-bold text-lg leading-snug line-clamp-2 min-h-[56px]">{quiz.title}</h3>
                  
                  <div className="grid grid-cols-2 gap-4 pt-2">
                    <div className="space-y-1">
                      <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-tighter">Attempts</span>
                      <div className="flex items-center gap-1.5">
                        <Users className="w-3.5 h-3.5 text-primary/60" />
                        <span className="text-sm font-bold">{quiz.attempts_count}</span>
                      </div>
                    </div>
                    <div className="space-y-1">
                      <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-tighter">Avg. Score</span>
                      <div className="flex items-center gap-1.5">
                        <Trophy className="w-3.5 h-3.5 text-amber-500/60" />
                        <span className="text-sm font-bold">{quiz.avg_score}%</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="mt-6 pt-4 border-t border-border flex justify-between items-center text-[11px] text-muted-foreground font-medium">
                  <span>Last activity {quiz.last_activity}</span>
                  <Link href={`/admin/quizzes/${quiz.id}/builder`} className="text-primary hover:underline font-bold">Manage →</Link>
                </div>
              </motion.div>
            </div>
          ))}

          {/* Create New Card */}
          <div className="flex-[0_0_280px] min-w-0">
            <Link href="/admin/quizzes/create" className="block h-full">
              <div className="h-full rounded-2xl border-2 border-dashed border-border flex flex-col items-center justify-center p-8 text-muted-foreground hover:border-primary hover:text-primary transition-all group">
                <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-3 group-hover:bg-primary/10 transition-colors">
                  <Plus className="w-6 h-6" />
                </div>
                <span className="font-bold">Create new quiz</span>
              </div>
            </Link>
          </div>
        </div>
      </div>
    </section>
  );
}
