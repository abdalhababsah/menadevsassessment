import React from 'react';
import { motion } from 'framer-motion';
import { Sparkles, Users, Plus, Mail, BarChart3, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface AtriumHeroProps {
  firstName: string;
  onlineCount: number;
  summary: string;
}

export function AtriumHero({ firstName, onlineCount, summary }: AtriumHeroProps) {
  const hour = new Date().getHours();
  const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';

  return (
    <section className="relative w-full rounded-3xl overflow-hidden mb-8 shadow-2xl shadow-primary/10">
      {/* Mesh Gradient Background */}
      <div className="absolute inset-0 z-0 bg-neutral-900">
        <div className="absolute inset-0 opacity-40 bg-[radial-gradient(circle_at_20%_30%,var(--color-verdant-500)_0%,transparent_50%),radial-gradient(circle_at_80%_70%,var(--color-verdant-600)_0%,transparent_50%),radial-gradient(circle_at_50%_50%,var(--color-verdant-400)_0%,transparent_70%)] animate-pulse transition-all duration-[10s] ease-in-out" />
        <div className="absolute inset-0 backdrop-blur-[100px]" />
      </div>

      <div className="relative z-10 p-8 lg:p-12 text-white flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
        <div className="space-y-4 max-w-2xl">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 w-fit"
          >
            <Sparkles className="w-3 h-3 text-verdant-400" />
            <span className="text-[10px] font-bold uppercase tracking-widest text-white/80">Platform Overview</span>
          </motion.div>

          <motion.h1 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="text-4xl lg:text-5xl xl:text-6xl font-display font-bold leading-tight"
          >
            {greeting}, <span className="text-verdant-400">{firstName}</span>.
          </motion.h1>

          <motion.p 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
            className="text-lg text-white/70 max-w-xl leading-relaxed"
          >
            {summary}
          </motion.p>

          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="flex flex-wrap gap-3 pt-4"
          >
            <Button variant="verdant" asChild className="rounded-full px-6 h-10 shadow-glow-verdant cursor-pointer">
              <Link href="/admin/quizzes/create">
                <Plus className="w-4 h-4 mr-2" />
                Create Quiz
              </Link>
            </Button>
            <Button variant="outline" asChild className="rounded-full px-6 h-10 bg-white/5 border-white/20 hover:bg-white/10 text-white cursor-pointer">
              <Link href="/admin/quizzes">
                <Mail className="w-4 h-4 mr-2" />
                Invite Candidate
              </Link>
            </Button>
            <Button variant="outline" asChild className="rounded-full px-6 h-10 bg-white/5 border-white/20 hover:bg-white/10 text-white cursor-pointer">
              <Link href="/admin/results">
                <BarChart3 className="w-4 h-4 mr-2" />
                View Analytics
              </Link>
            </Button>
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ delay: 0.5 }}
          className="flex flex-col items-center lg:items-end gap-2"
        >
          <div className="px-6 py-4 rounded-2xl bg-white/5 backdrop-blur-xl border border-white/10 flex flex-col items-center lg:items-end gap-1">
            <span className="text-[10px] font-bold uppercase tracking-wider text-white/50">Real-time Presence</span>
            <div className="flex items-center gap-2">
              <div className="relative">
                <div className="w-2 h-2 bg-verdant-500 rounded-full" />
                <div className="absolute inset-0 w-2 h-2 bg-verdant-500 rounded-full animate-ping opacity-75" />
              </div>
              <span className="text-2xl font-bold font-display">{onlineCount}</span>
              <span className="text-sm text-white/70">candidates active</span>
            </div>
            <div className="flex -space-x-2 mt-2">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className="w-6 h-6 rounded-full border-2 border-neutral-900 bg-neutral-800 flex items-center justify-center text-[10px] font-bold">
                  {String.fromCharCode(64 + i)}
                </div>
              ))}
              <div className="w-6 h-6 rounded-full border-2 border-neutral-900 bg-verdant-900/50 flex items-center justify-center text-[8px] font-bold">
                +{onlineCount - 4}
              </div>
            </div>
          </div>
        </motion.div>
      </div>

      {/* Hero Parallax Elements (Visual decoration) */}
      <div className="absolute -bottom-20 -right-20 w-80 h-80 bg-verdant-500/20 rounded-full blur-[100px] pointer-events-none" />
      <div className="absolute -top-40 -left-40 w-80 h-80 bg-verdant-500/10 rounded-full blur-[100px] pointer-events-none" />
    </section>
  );
}
