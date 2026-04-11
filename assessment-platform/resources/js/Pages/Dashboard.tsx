import React from 'react';
import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import AdminLayout from '@/Layouts/AdminLayout';
import { useAuth } from '@/Hooks/useAuth';
import { AtriumHero } from '@/components/Dashboard/AtriumHero';
import { MetricTile } from '@/components/Dashboard/MetricTile';
import { ActivityFeed } from '@/components/Dashboard/ActivityFeed';
import { DashboardCharts } from '@/components/Dashboard/DashboardCharts';
import { QuizCarousel } from '@/components/Dashboard/QuizCarousel';
import { TaskPanel, Leaderboard } from '@/components/Dashboard/TaskPanel';
import { Badge } from '@/components/ui/badge';
import { 
  BookOpen, 
  Users, 
  CheckCircle, 
  TrendingUp, 
  ArrowUpRight,
  Newspaper,
  Zap,
  Sparkles
} from 'lucide-react';

interface DashboardProps {
  data: {
    kpis: any;
    recent_activity: any[];
    top_performers: any[];
    pending_tasks: any;
    quizzes: any[];
    charts: any;
    online_candidates_count: number;
  };
}

const container = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.1
    }
  }
};

const item = {
  hidden: { opacity: 0, y: 20 },
  show: { opacity: 1, y: 0 }
};

export default function Dashboard({ data }: DashboardProps) {
  const { user } = useAuth();
  
  const pendingCount = data.pending_tasks.rlhf_reviews + data.pending_tasks.suspicious_attempts;
  const statusSummary = pendingCount > 0 
    ? `You have ${data.pending_tasks.rlhf_reviews} pending RLHF reviews and ${data.pending_tasks.suspicious_attempts} suspicious events to verify.` 
    : "Everything is up to date. Beautiful day to create.";

  return (
    <AdminLayout>
      <Head title="The Atrium" />

      <motion.div 
        variants={container}
        initial="hidden"
        animate="show"
        className="space-y-8"
      >
        {/* Hero Band */}
        <motion.div variants={item}>
          <AtriumHero 
            firstName={user?.name?.split(' ')[0] || 'User'} 
            onlineCount={data.online_candidates_count}
            summary={statusSummary}
          />
        </motion.div>

        {/* KPIs & Activity Layer */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <motion.div variants={item}>
              <MetricTile 
                label="Active Quizzes" 
                value={data.kpis.active_quizzes.value} 
                icon={BookOpen} 
                sparkline={data.kpis.active_quizzes.sparkline}
              />
            </motion.div>
            <motion.div variants={item}>
              <MetricTile 
                label="Total Candidates" 
                value={data.kpis.total_candidates.value} 
                icon={Users} 
                trend={data.kpis.total_candidates.trend}
              />
            </motion.div>
            <motion.div variants={item}>
              <MetricTile 
                label="Attempts This Week" 
                value={data.kpis.attempts_this_week.value} 
                icon={TrendingUp} 
                suffix=""
              />
            </motion.div>
            <motion.div variants={item}>
              <MetricTile 
                label="Completion Rate" 
                value={data.kpis.completion_rate.value} 
                icon={CheckCircle} 
                suffix="%"
              />
            </motion.div>
          </div>

          <motion.div variants={item} className="lg:col-span-6 h-full">
            <ActivityFeed activities={data.recent_activity} />
          </motion.div>
        </div>

        {/* Charts Row */}
        <motion.div variants={item}>
          <DashboardCharts 
            attemptsData={data.charts.attempts_over_time} 
            scoreDistribution={data.charts.score_distribution} 
          />
        </motion.div>

        {/* Quizzes Spotlight */}
        <motion.div variants={item}>
          <QuizCarousel quizzes={data.quizzes} />
        </motion.div>

        {/* Tasks & Leaderboard Layer */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <motion.div variants={item} className="lg:col-span-6">
            <TaskPanel tasks={data.pending_tasks} />
          </motion.div>
          <motion.div variants={item} className="lg:col-span-6">
            <Leaderboard performers={data.top_performers} />
          </motion.div>
        </div>

        {/* Footer Band: What's New */}
        <motion.section variants={item} className="pt-8 border-t border-border">
          <div className="flex items-center gap-2 mb-6">
            <Newspaper className="w-5 h-5 text-primary" />
            <h2 className="text-xl font-bold font-display tracking-tight">What's New</h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <ChangelogCard 
              tag="Update" 
              title="RLHF Evaluation 2.0" 
              description="Improved side-by-side rating interface with custom criteria support."
              icon={Sparkles}
            />
            <ChangelogCard 
              tag="Feature" 
              title="Advanced Proctoring" 
              description="New anti-cheat measures including dual-camera support and tab-blur detection."
              icon={ShieldAlert}
            />
            <ChangelogCard 
              tag="Integration" 
              title="Greenhouse Analytics" 
              description="Seamlessly export candidate results to your main Greenhouse workspace."
              icon={Zap}
            />
          </div>
        </motion.section>
      </motion.div>
    </AdminLayout>
  );
}

function ChangelogCard({ tag, title, description, icon: Icon }: any) {
  return (
    <div className="p-6 rounded-2xl bg-muted/30 border border-border/50 hover:bg-muted/50 transition-colors group cursor-pointer">
      <div className="flex justify-between items-start mb-4">
        <Badge variant="outline" className="text-[10px] font-bold uppercase tracking-widest bg-background">{tag}</Badge>
        <Icon className="w-4 h-4 text-primary opacity-40 group-hover:opacity-100 transition-opacity" />
      </div>
      <h4 className="font-bold mb-2 group-hover:text-primary transition-colors">{title}</h4>
      <p className="text-xs text-muted-foreground leading-relaxed">{description}</p>
      <div className="mt-4 flex items-center gap-1 text-[10px] font-bold text-primary uppercase tracking-tighter opacity-0 group-hover:opacity-100 transition-opacity">
        Read More <ArrowUpRight className="w-3 h-3" />
      </div>
    </div>
  );
}

const ShieldAlert = ({ className }: { className?: string }) => (
  <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m12 8 4 4"/><path d="m16 8-4 4"/>
  </svg>
);
