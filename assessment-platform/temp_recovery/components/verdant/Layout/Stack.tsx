import { cn } from '@/lib/utils';
export function Stack({ children, gap = 'cozy', className, align = 'stretch' }: { children: React.ReactNode; gap?: any; className?: string; align?: string }) {
  const gapMap:any = { tight: 'gap-2', snug: 'gap-3', cozy: 'gap-4', comfortable: 'gap-6', spacious: 'gap-8', loose: 'gap-12' };
  return <div className={cn('flex flex-col', gapMap[gap] || 'gap-4', { 'items-start': align === 'start', 'items-center': align === 'center', 'items-stretch': align === 'stretch' }, className)}>{children}</div>;
}
