import { motion } from "framer-motion";
import NumberFlow from "@number-flow/react";
import { cn } from "@/lib/utils";
import { Frame } from "../Layout/Frame";

export function MetricTile({ label, value, prefix, suffix, trend, icon, variant = 'default', className }: any) {
  return (
    <Frame elevation={1} className={cn('relative overflow-hidden group transition-all duration-300', className)}>
      <div className="flex justify-between items-start mb-4">
        <div>
          <p className="text-sm font-sans font-medium text-stone-500 dark:text-stone-400 uppercase tracking-widest">{label}</p>
          <div className="flex items-baseline gap-1 mt-1">
            {prefix && <span className="text-2xl font-display text-stone-400">{prefix}</span>}
            <span className="text-4xl font-display text-stone-900 dark:text-stone-50"><NumberFlow value={value} /></span>
            {suffix && <span className="text-lg font-sans text-stone-400">{suffix}</span>}
          </div>
        </div>
        {icon && <div className="p-2 rounded-lg bg-verdant-50 dark:bg-verdant-900/30 text-verdant-600 dark:text-verdant-400">{icon}</div>}
      </div>
    </Frame>
  );
}
