import React from 'react';
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer,
  RadialBarChart,
  RadialBar,
  Legend
} from 'recharts';
import { motion } from 'framer-motion';

interface DashboardChartsProps {
  attemptsData: any[];
  scoreDistribution: any[];
}

export function DashboardCharts({ attemptsData, scoreDistribution }: DashboardChartsProps) {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
      {/* Area Chart: Attempts trend */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="lg:col-span-8 p-6 rounded-2xl bg-card border border-border flex flex-col min-h-[400px]"
      >
        <div className="flex justify-between items-center mb-6">
          <div>
            <h3 className="text-lg font-bold font-display">Attempts over time</h3>
            <p className="text-sm text-muted-foreground">Candidate activity for the last 30 days</p>
          </div>
          <div className="flex gap-2 p-1 bg-muted rounded-lg">
            {['Day', 'Week', 'Month'].map((t) => (
              <button key={t} className="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md transition-all hover:bg-background hover:shadow-sm">
                {t}
              </button>
            ))}
          </div>
        </div>

        <div className="flex-1 min-h-0">
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={attemptsData}>
              <defs>
                <linearGradient id="colorAttempts" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="var(--color-verdant-500)" stopOpacity={0.3}/>
                  <stop offset="95%" stopColor="var(--color-verdant-500)" stopOpacity={0}/>
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--muted-foreground) / 0.1)" />
              <XAxis 
                dataKey="name" 
                axisLine={false} 
                tickLine={false} 
                tick={{ fontSize: 10, fill: 'hsl(var(--muted-foreground))' }}
                dy={10}
              />
              <YAxis 
                axisLine={false} 
                tickLine={false} 
                tick={{ fontSize: 10, fill: 'hsl(var(--muted-foreground))' }}
              />
              <Tooltip 
                contentStyle={{ 
                  backgroundColor: 'hsl(var(--card))', 
                  borderRadius: '12px', 
                  border: '1px solid hsl(var(--border))',
                  boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)'
                }}
                labelStyle={{ fontWeight: 'bold', marginBottom: '4px' }}
              />
              <Area 
                type="monotone" 
                dataKey="count" 
                stroke="var(--color-verdant-500)" 
                strokeWidth={3} 
                fillOpacity={1} 
                fill="url(#colorAttempts)" 
                animationDuration={2000}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>
      </motion.div>

      {/* Radial Chart: Score Distribution */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.2 }}
        className="lg:col-span-4 p-6 rounded-2xl bg-card border border-border flex flex-col min-h-[400px]"
      >
        <div className="mb-6">
          <h3 className="text-lg font-bold font-display">Score Distribution</h3>
          <p className="text-sm text-muted-foreground">Breakdown of grading tiers</p>
        </div>

        <div className="flex-1 min-h-0 relative">
          <ResponsiveContainer width="100%" height="100%">
            <RadialBarChart 
              innerRadius="30%" 
              outerRadius="100%" 
              data={scoreDistribution} 
              startAngle={180} 
              endAngle={0}
            >
              <RadialBar
                background
                dataKey="value"
                cornerRadius={10}
                animationDuration={1500}
              />
              <Tooltip 
                cursor={{ strokeDasharray: '3 3' }}
                contentStyle={{ 
                  backgroundColor: 'hsl(var(--card))', 
                  borderRadius: '12px', 
                  border: '1px solid hsl(var(--border))',
                  boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)'
                }}
              />
              <Legend 
                iconSize={10} 
                layout="horizontal" 
                verticalAlign="bottom" 
                wrapperStyle={{ fontSize: '10px', fontWeight: 'bold' }}
              />
            </RadialBarChart>
          </ResponsiveContainer>
        </div>
      </motion.div>
    </div>
  );
}
