import { cn } from '@/lib/utils';
export function Wordmark({ className, size = 'md' }: { className?: string; size?: 'sm' | 'md' | 'lg' | 'xl' }) {
  const sizes = { sm: 'text-lg', md: 'text-2xl', lg: 'text-4xl', xl: 'text-6xl' };
  return <span className={cn('font-display tracking-tight text-stone-900 dark:text-stone-50', sizes[size], className)}>verdant</span>;
}
