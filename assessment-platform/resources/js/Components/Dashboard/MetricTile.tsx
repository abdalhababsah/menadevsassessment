import React from 'react';
import NumberFlow from '@number-flow/react';
import { motion } from 'framer-motion';
import { TrendingUp, TrendingDown, ArrowRight } from 'lucide-react';
import { LineChart, Line, ResponsiveContainer } from 'recharts';
import { cn } from '@/lib/utils';

interface MetricTileProps {
  label: string;
  value: number;
  icon: React.ElementType;
  trend?: number;
  sparkline?: number[];
  prefix?: string;
  suffix?: string;
  className?: string;
}

export function MetricTile({ 
  label, 
  value, 
  icon: Icon, 
  trend, 
  sparkline, 
  prefix, 
  suffix,
  className 
}: MetricTileProps) {
  const isPositive = trend && trend > 0;
  
  const chartData = sparkline?.map((val, i) => ({ value: val })) ?? [];

  return (
    <motion.div
      whileHover={{ y: -4, boxShadow: '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)' }}
      className={cn(
        "group relative p-6 rounded-2xl bg-card border border-border transition-all duration-300",
        className
      )}
    >
      <div className="flex justify-between items-start mb-4">
        <div className="p-2.5 rounded-xl bg-primary/5 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-colors duration-300">
          <Icon className="w-5 h-5" />
        </div>
        
        {trend !== undefined && (
          <div className={cn(
            "flex items-center gap-1 text-xs font-bold",
            isPositive ? "text-emerald-500" : "text-rose-500"
          )}>
            {isPositive ? <TrendingUp className="w-3 h-3" /> : <TrendingDown className="w-3 h-3" />}
            <span>{isPositive ? '+' : ''}{trend}%</span>
          </div>
        )}
      </div>

      <div className="space-y-1">
        <p className="text-sm font-medium text-muted-foreground">{label}</p>
        <div className="flex items-baseline gap-1">
          {prefix && <span className="text-xl font-bold text-muted-foreground">{prefix}</span>}
          <span className="text-3xl font-display font-bold tabular-nums">
            <NumberFlow value={value} />
          </span>
          {suffix && <span className="text-xl font-bold text-muted-foreground">{suffix}</span>}
        </div>
      </div>

      {sparkline && (
        <div className="h-10 mt-4 opacity-50 group-hover:opacity-100 transition-opacity">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={chartData}>
              <Line 
                type="monotone" 
                dataKey="value" 
                stroke="currentColor" 
                strokeWidth={2} 
                dot={false} 
                className="text-primary"
              />
            </LineChart>
          </ResponsiveContainer>
        </div>
      )}

      <div className="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity translate-x-2 group-hover:translate-x-0 transition-transform">
        <ArrowRight className="w-4 h-4 text-primary" />
      </div>
    </motion.div>
  );
}
