import React from 'react';
import { motion } from 'framer-motion';
import { 
  AlertCircle, 
  Code, 
  ShieldAlert, 
  ChevronDown, 
  ChevronRight,
  ExternalLink,
  Medal,
  TrendingUp,
  User
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import ConfettiExplosion from 'react-confetti-boom';

interface Task {
  count: number;
  label: string;
  icon: React.ElementType;
  items?: string[];
}

export function TaskPanel({ tasks }: { tasks: any }) {
  const [openSection, setOpenSection] = React.useState<string | null>('rlhf');

  const taskGroups = [
    { id: 'rlhf', label: 'RLHF Reviews', count: tasks.rlhf_reviews, icon: AlertCircle, color: 'text-amber-500' },
    { id: 'coding', label: 'Coding Verification', count: tasks.coding_verifications, icon: Code, color: 'text-blue-500' },
    { id: 'suspicious', label: 'Suspicious Events', count: tasks.suspicious_attempts, icon: ShieldAlert, color: 'text-rose-500' },
  ];

  return (
    <div className="bg-card rounded-2xl border border-border overflow-hidden flex flex-col h-full">
      <div className="p-6 border-b border-border bg-muted/30">
        <h3 className="text-lg font-bold font-display">Needs your attention</h3>
        <p className="text-sm text-muted-foreground">Critical tasks and manual reviews</p>
      </div>

      <div className="p-4 space-y-2">
        {taskGroups.map((group) => (
          <Collapsible
            key={group.id}
            open={openSection === group.id}
            onOpenChange={() => setOpenSection(openSection === group.id ? null : group.id)}
            className="rounded-xl border border-border overflow-hidden"
          >
            <CollapsibleTrigger asChild>
              <button className="flex items-center justify-between w-full p-4 hover:bg-muted/50 transition-colors text-left">
                <div className="flex items-center gap-3">
                  <div className={group.color}>
                    <group.icon className="w-5 h-5" />
                  </div>
                  <span className="font-bold text-sm tracking-tight">{group.label}</span>
                </div>
                <div className="flex items-center gap-3">
                  <Badge variant="destructive" className="h-5 px-1.5 font-bold">{group.count}</Badge>
                  {openSection === group.id ? <ChevronDown className="w-4 h-4 text-muted-foreground" /> : <ChevronRight className="w-4 h-4 text-muted-foreground" />}
                </div>
              </button>
            </CollapsibleTrigger>
            <CollapsibleContent className="px-4 pb-4">
              <div className="space-y-2 pt-2">
                {group.count > 0 ? (
                  <>
                    <p className="text-[11px] text-muted-foreground italic mb-3">Top 3 urgent items:</p>
                    <div className="space-y-2">
                      {[1, 2, 3].slice(0, group.count).map((i) => (
                        <div key={i} className="flex items-center justify-between p-2 rounded-lg bg-muted/30 border border-border/50">
                          <span className="text-xs font-medium">Review Item #{i+1024}</span>
                          <Button variant="ghost" size="icon" className="w-6 h-6"><ExternalLink className="w-3 h-3" /></Button>
                        </div>
                      ))}
                    </div>
                  </>
                ) : (
                  <div className="py-4 text-center">
                    <p className="text-xs text-muted-foreground">All caught up! ✨</p>
                  </div>
                )}
              </div>
            </CollapsibleContent>
          </Collapsible>
        ))}
      </div>
    </div>
  );
}

export function Leaderboard({ performers }: { performers: any[] }) {
  const [isExploding, setIsExploding] = React.useState(false);

  React.useEffect(() => {
    const timer = setTimeout(() => setIsExploding(true), 1000);
    return () => clearTimeout(timer);
  }, []);

  return (
    <div className="bg-card rounded-2xl border border-border overflow-hidden flex flex-col h-full relative">
      {isExploding && (
        <div className="absolute top-0 left-1/2 -translate-x-1/2 pointer-events-none z-50">
          <ConfettiExplosion mode="boom" particleCount={50} colors={['#22c55e', '#10b981', '#ffffff']} />
        </div>
      )}

      <div className="p-6 border-b border-border bg-muted/30 flex justify-between items-center">
        <div>
          <h3 className="text-lg font-bold font-display">Leaderboard</h3>
          <p className="text-sm text-muted-foreground">Top performers this week</p>
        </div>
        <Trophy className="w-8 h-8 text-amber-500 opacity-20" />
      </div>

      <div className="p-4 space-y-1">
        {performers.length > 0 ? (
          performers.map((p, idx) => (
            <motion.div
              key={p.candidate_email}
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: 0.5 + idx * 0.1 }}
              className={cn(
                "flex items-center gap-4 p-3 rounded-xl transition-all hover:bg-muted/50 group",
                idx === 0 && "bg-verdant-50/50 border border-verdant-100"
              )}
            >
              <div className="w-8 flex justify-center shrink-0">
                <RankBadge rank={idx + 1} />
              </div>

              <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center border border-border shrink-0 overflow-hidden">
                <User className="w-5 h-5 text-muted-foreground" />
              </div>

              <div className="flex-1 min-w-0">
                <p className="text-sm font-bold truncate tracking-tight">{p.candidate_name}</p>
                <p className="text-[10px] text-muted-foreground truncate">{p.quiz_title}</p>
              </div>

              <div className="text-right">
                <p className="text-sm font-bold text-primary">{p.score}%</p>
                <div className="w-16 h-1 mt-1 bg-muted rounded-full overflow-hidden">
                  <motion.div 
                    initial={{ width: 0 }}
                    animate={{ width: `${p.score}%` }}
                    transition={{ delay: 1, duration: 1 }}
                    className="h-full bg-primary" 
                  />
                </div>
              </div>
            </motion.div>
          ))
        ) : (
          <div className="py-20 text-center">
             <p className="text-sm text-muted-foreground">No data available for this week.</p>
          </div>
        )}
      </div>
    </div>
  );
}

function RankBadge({ rank }: { rank: number }) {
  if (rank === 1) return <Medal className="w-5 h-5 text-amber-500" />;
  if (rank === 2) return <Medal className="w-5 h-5 text-slate-400" />;
  if (rank === 3) return <Medal className="w-5 h-5 text-amber-700" />;
  return <span className="text-xs font-bold text-muted-foreground">#{rank}</span>;
}

const Trophy = ({ className }: { className?: string }) => (
  <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 22V18"/><path d="M14 22V18"/><path d="M18 4H6v7a6 6 0 0 0 12 0V4Z"/>
  </svg>
);
