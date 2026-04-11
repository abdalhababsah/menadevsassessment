import { cn } from "@/lib/utils";
export function StatusDot({ status, pulse = false, size = 'md', className }: any) {
  const colors:any = { online: 'bg-verdant-500', offline: 'bg-stone-300 dark:bg-stone-600', busy: 'bg-destructive', away: 'bg-citron-500' };
  const sizes:any = { sm: 'h-2 w-2', md: 'h-3 w-3', lg: 'h-4 w-4' };
  return (
    <div className={cn('relative flex', sizes[size], className)}>
      {pulse && status === 'online' && <span className={cn("absolute inline-flex h-full w-full animate-ping rounded-full opacity-75", colors[status])} />}
      <span className={cn("relative inline-flex rounded-full", sizes[size], colors[status])} />
    </div>
  );
}
