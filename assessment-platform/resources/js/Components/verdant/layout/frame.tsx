import { cn } from '@/lib/utils';
export function Frame({ children, radius = 'md', padding = 'cozy', className, surface = 'base' }: any) {
  const radii:any = { none: 'rounded-none', sm: 'rounded-sm', md: 'rounded-md', lg: 'rounded-lg', xl: 'rounded-xl' };
  const paddings:any = { none: 'p-0', tight: 'p-2', snug: 'p-4', cozy: 'p-6', comfortable: 'p-8' };
  return <div className={cn(radii[radius], paddings[padding], 'bg-white border text-stone-900', className)}>{children}</div>;
}
