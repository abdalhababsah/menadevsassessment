import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { cn } from '@/lib/utils';
import { User, CheckCircle2, Zap, MessageSquare, ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface ActivityItem {
  id: number;
  description: string;
  causer?: { name: string; email: string };
  subject_type?: string;
  created_at: string;
  properties?: any;
}

export function ActivityFeed({ activities }: { activities: ActivityItem[] }) {
  return (
    <div className="flex flex-col h-full bg-card rounded-2xl border border-border overflow-hidden">
      <div className="p-6 border-b border-border flex justify-between items-center bg-muted/30">
        <div className="flex items-center gap-3">
          <h3 className="text-lg font-bold font-display">Live Activity</h3>
          <div className="flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 text-[10px] font-bold uppercase tracking-wider">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
            Live
          </div>
        </div>
        <Button variant="ghost" size="sm" asChild className="text-xs font-medium text-muted-foreground hover:text-foreground">
          <Link href="/admin/audit-log">
            View All Activity
          </Link>
        </Button>
      </div>

      <div className="flex-1 overflow-y-auto p-4 lg:p-6 custom-scrollbar">
        <div className="space-y-6 relative before:absolute before:inset-y-0 before:left-5 before:w-px before:bg-border/60">
          <AnimatePresence mode="popLayout">
            {activities.map((item, index) => (
              <motion.div
                key={item.id}
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: index * 0.05 }}
                className="relative flex gap-4 pl-1"
              >
                <div className="relative z-10 w-10 h-10 rounded-full border-2 border-background bg-muted flex items-center justify-center shrink-0 shadow-sm transition-transform hover:scale-110">
                  <ActivityIcon type={item.subject_type} />
                </div>

                <div className="flex-1 space-y-1 pt-0.5">
                  <p className="text-sm text-foreground/90 leading-tight">
                    <span className="font-bold text-foreground">
                      {item.causer?.name || 'System'}
                    </span>{' '}
                    {item.description}
                  </p>
                  <div className="flex items-center gap-2">
                    <span className="text-[10px] font-medium text-muted-foreground uppercase tracking-tight">
                      {item.created_at}
                    </span>
                    {item.properties?.score && (
                      <span className="text-[10px] font-bold text-verdant-600 bg-verdant-50 px-1.5 py-0.5 rounded-md border border-verdant-100">
                        Score {item.properties.score}%
                      </span>
                    )}
                  </div>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        </div>
      </div>
    </div>
  );
}

function ActivityIcon({ type }: { type?: string }) {
  if (!type) return <Zap className="w-4 h-4 text-amber-500" />;
  
  if (type.includes('QuizAttempt')) return <CheckCircle2 className="w-4 h-4 text-emerald-500" />;
  if (type.includes('Candidate')) return <User className="w-4 h-4 text-blue-500" />;
  if (type.includes('RlhfReview')) return <MessageSquare className="w-4 h-4 text-purple-500" />;
  if (type.includes('Suspicious')) return <ShieldAlert className="w-4 h-4 text-rose-500" />;
  
  return <Zap className="w-4 h-4 text-primary" />;
}
