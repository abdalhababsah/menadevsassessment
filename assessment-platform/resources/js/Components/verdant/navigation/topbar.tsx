import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { motion, AnimatePresence, useScroll, useTransform } from 'framer-motion';
import { 
  Bell, 
  Plus, 
  ChevronRight, 
  Search,
  Calendar,
  Eye,
  Settings,
  HelpCircle,
  Menu
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { useTopBar } from '@/Contexts/TopBarContext';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

export function TopBar({ onOpenMobileMenu, onOpenSearch }: { onOpenMobileMenu: () => void; onOpenSearch: () => void }) {
  const { url } = usePage();
  const { left, center, right, title, clearAll } = useTopBar();
  const { scrollY } = useScroll();
  
  // Adaptive effects: shrink height and opacity on scroll
  const height = useTransform(scrollY, [0, 50], [72, 60]);
  const backgroundColor = useTransform(
    scrollY, 
    [0, 50], 
    ["rgba(var(--background), 0)", "rgba(var(--background), 0.8)"]
  );
  const borderOpacity = useTransform(scrollY, [0, 50], [0, 1]);

  // Simplified Breadcrumbs
  const pathPart = url.split('?')[0];
  const pathSegments = pathPart.split('/').filter(Boolean);
  const breadcrumbs = pathSegments.map((segment, index) => ({
    label: segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' '),
    href: '/' + pathSegments.slice(0, index + 1).join('/'),
    isLast: index === pathSegments.length - 1
  }));

  return (
    <motion.header
      style={{ height, backgroundColor }}
      className={cn(
        "sticky top-0 z-30 flex items-center px-4 lg:px-8 backdrop-blur-md transition-shadow duration-300",
        "before:absolute before:inset-x-0 before:bottom-0 before:h-px before:bg-border/50 before:transition-opacity"
      )}
    >
      <div className="flex items-center gap-4 w-full">
        {/* Left Area: Mobile Menu & Breadcrumbs */}
        <div className="flex items-center gap-2 lg:gap-4 flex-1">
          <Button variant="ghost" size="icon" className="lg:hidden" onClick={onOpenMobileMenu}>
            <Menu className="w-5 h-5" />
          </Button>

          {left || (
            <div className="hidden sm:flex items-center gap-2 text-sm text-muted-foreground overflow-hidden">
              {breadcrumbs.map((crumb, idx) => (
                <React.Fragment key={crumb.href}>
                  {idx > 0 && <ChevronRight className="w-3.5 h-3.5 shrink-0 opacity-40" />}
                  <Link 
                    href={crumb.href} 
                    className={cn(
                      "truncate transition-colors hover:text-foreground",
                      crumb.isLast && "text-foreground font-bold"
                    )}
                  >
                    {crumb.label}
                  </Link>
                </React.Fragment>
              ))}
            </div>
          )}
        </div>

        {/* Center Area: Contextual actions */}
        <div className="hidden lg:flex flex-1 justify-center items-center">
          <AnimatePresence mode="wait">
            <motion.div
              key={url}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className="flex items-center gap-3"
            >
              {center || (
                <div className="flex items-center gap-2 px-3 py-1.5 bg-muted/40 rounded-full border border-border/50">
                  <Calendar className="w-3.5 h-3.5 text-primary" />
                  <span className="text-[11px] font-medium">Today is {new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                </div>
              )}
            </motion.div>
          </AnimatePresence>
        </div>

        {/* Right Area: Search, Quick Create, Notifications */}
        <div className="flex items-center gap-2 lg:gap-3 flex-1 justify-end">
          <Button variant="ghost" size="icon" className="h-9 w-9 text-muted-foreground lg:hidden" onClick={onOpenSearch}>
            <Search className="w-5 h-5" />
          </Button>

          {right || (
            <>
              <Popover>
                <PopoverTrigger asChild>
                  <Button variant="verdant" size="sm" className="hidden sm:flex gap-2 h-9 px-4 rounded-full shadow-glow-verdant">
                    <Plus className="w-4 h-4" />
                    <span>Quick Create</span>
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-56 p-2">
                  <div className="p-2 mb-1">
                    <p className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Shortcuts</p>
                  </div>
                  <div className="flex flex-col gap-1">
                    <Button variant="ghost" size="sm" className="justify-start gap-2 h-9">
                      <Plus className="w-3.5 h-3.5" />
                      <span>New Quiz</span>
                    </Button>
                    <Button variant="ghost" size="sm" className="justify-start gap-2 h-9">
                      <Plus className="w-3.5 h-3.5" />
                      <span>Create Question</span>
                    </Button>
                    <Separator className="my-1" />
                    <Button variant="ghost" size="sm" className="justify-start gap-2 h-9">
                      <Mail className="w-3.5 h-3.5" />
                      <span>Invite Candidate</span>
                    </Button>
                  </div>
                </PopoverContent>
              </Popover>

              <Button variant="ghost" size="icon" className="h-9 w-9 rounded-full relative">
                <Bell className="w-5 h-5 text-muted-foreground" />
                <span className="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full border-2 border-background" />
              </Button>

              <div className="h-6 w-px bg-border mx-1 hidden sm:block" />

              <Button variant="ghost" size="icon" className="h-9 w-9 rounded-full">
                <HelpCircle className="w-5 h-5 text-muted-foreground" />
              </Button>
            </>
          )}
        </div>
      </div>
    </motion.header>
  );
}

const Separator = ({ className }: { className?: string }) => <div className={cn("h-px bg-border", className)} />;
const Mail = ({ className }: { className?: string }) => (
  <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
  </svg>
);
