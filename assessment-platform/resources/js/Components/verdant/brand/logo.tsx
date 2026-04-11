import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
export function Logo({ className, size = 'md' }: any) {
  const sizes:any = { sm: 'h-6 w-6', md: 'h-10 w-10', lg: 'h-16 w-16', xl: 'h-24 w-24' };
  return (
    <motion.svg viewBox="0 0 100 100" fill="none" className={cn(sizes[size], className)} initial="initial" animate="animate">
      <motion.path d="M50 90C72.0914 90 90 72.0914 90 50C90 27.9086 72.0914 10 50 10C27.9086 10 10 27.9086 10 50C10 72.0914 27.9086 90 50 90Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" initial={{ pathLength: 0, opacity: 0 }} animate={{ pathLength: 1, opacity: 0.2 }} transition={{ duration: 1.5, ease: "easeInOut" }} />
      <motion.path d="M30 50L45 65L75 35" stroke="var(--verdant-500)" strokeWidth="8" strokeLinecap="round" strokeLinejoin="round" />
    </motion.svg>
  );
}
