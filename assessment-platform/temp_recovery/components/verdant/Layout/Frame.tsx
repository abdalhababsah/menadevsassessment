import { cn } from '@/lib/utils';
export function Frame({ children, elevation = 0, radius = 'md', padding = 'cozy', className, surface = 'base' }: { children: React.ReactNode; elevation?: number; radius?: string; padding?: string; className?: string; surface?: string }) {
  const radii:any = { none: 'rounded-none', sm: 'rounded-sm', md: 'rounded-md', lg: 'rounded-lg', xl: 'rounded-xl', '2xl': 'rounded-2xl' };
  const paddings:any = { none: 'p-0', tight: 'p-2', snug: 'p-4', cozy: 'p-6', comfortable: 'p-8', spacious: 'p-12' };
  const surfaces:any = { base: 'bg-surface-base', raised: 'bg-surface-raised', overlay: 'bg-surface-overlay', sunken: 'bg-surface-sunken' };
  const shadows:any = { 0: '', 1: 'shadow-sm', 2: 'shadow-md', 3: 'shadow-lg' };
  return <div className={cn(radii[radius], paddings[padding], surfaces[surface], shadows[elevation], 'ring-1 ring-stone-200/50 dark:ring-stone-800/50', className)}>{children}</div>;
}
