import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { motion, AnimatePresence, LayoutGroup } from 'framer-motion';
import { 
  LayoutDashboard, 
  Inbox, 
  BookOpen, 
  Database, 
  Mail, 
  Users, 
  UserSquare2, 
  ShieldCheck, 
  FileSearch, 
  BarChart3, 
  CheckCircle2, 
  History, 
  Settings, 
  Zap, 
  ChevronLeft, 
  ChevronRight, 
  Search,
  Plus,
  Bell,
  MessageSquare,
  ChevronDown,
  Sparkles,
  User,
  LogOut,
  Moon,
  Sun,
  Monitor
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useAuth } from '@/Hooks/useAuth';
import { usePermissions } from '@/Hooks/usePermissions';
import { Logo } from '@/components/verdant/brand/logo';

interface NavItem {
  label: string;
  href: string;
  icon: React.ElementType;
  badge?: string | number;
  permission?: string;
  group: string;
}

const NAV_GROUPS = [
  { id: 'today', label: 'Today' },
  { id: 'build', label: 'Build' },
  { id: 'people', label: 'People' },
  { id: 'insights', label: 'Insights' },
  { id: 'system', label: 'System' },
];

const NAV_ITEMS: NavItem[] = [
  { group: 'today', label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
  { group: 'today', label: 'Inbox', href: '/admin/inbox', icon: Inbox, badge: 3 },
  { group: 'build', label: 'Quizzes', href: '/admin/quizzes', icon: BookOpen, permission: 'quiz.view' },
  { group: 'build', label: 'Question Bank', href: '/admin/questions', icon: Database, permission: 'questionbank.view' },
  { group: 'build', label: 'Invitations', href: '/admin/invitations', icon: Mail, permission: 'invitation.view' },
  { group: 'people', label: 'Candidates', href: '/admin/candidates', icon: Users, permission: 'candidate.view' },
  { group: 'people', label: 'Users', href: '/admin/users', icon: UserSquare2, permission: 'users.view' },
  { group: 'people', label: 'Roles', href: '/admin/roles', icon: ShieldCheck, permission: 'roles.view' },
  { group: 'insights', label: 'Results', href: '/admin/results', icon: FileSearch, permission: 'results.view' },
  { group: 'insights', label: 'Analytics', href: '/admin/analytics', icon: BarChart3, permission: 'analytics.view' },
  { group: 'insights', label: 'RLHF Reviews', href: '/admin/rlhf-reviews', icon: CheckCircle2, permission: 'rlhf.view' },
  { group: 'system', label: 'Audit Log', href: '/admin/audit-log', icon: History, permission: 'system.auditLog' },
  { group: 'system', label: 'Settings', href: '/admin/settings', icon: Settings, permission: 'system.settings' },
  { group: 'system', label: 'Integrations', href: '/admin/integrations', icon: Zap, permission: 'system.integrations' },
];

export function GreenhouseSidebar({ 
  isCollapsed, 
  onToggle, 
  onOpenSearch 
}: { 
  isCollapsed: boolean; 
  onToggle: () => void;
  onOpenSearch: () => void;
}) {
  const { user } = useAuth();
  const { hasPermission } = usePermissions();
  const { url } = usePage();
  const [width, setWidth] = useState(280);
  const [isResizing, setIsResizing] = useState(false);

  // Resize logic
  useEffect(() => {
    const savedWidth = localStorage.getItem('verdant-sidebar-width');
    if (savedWidth) setWidth(parseInt(savedWidth));
  }, []);

  const startResizing = useCallback(() => setIsResizing(true), []);
  const stopResizing = useCallback(() => setIsResizing(false), []);

  const resize = useCallback((e: MouseEvent) => {
    if (!isResizing) return;
    const newWidth = e.clientX;
    if (newWidth >= 240 && newWidth <= 360) {
      setWidth(newWidth);
      localStorage.setItem('verdant-sidebar-width', newWidth.toString());
    }
  }, [isResizing]);

  useEffect(() => {
    window.addEventListener('mousemove', resize);
    window.addEventListener('mouseup', stopResizing);
    return () => {
      window.removeEventListener('mousemove', resize);
      window.removeEventListener('mouseup', stopResizing);
    };
  }, [resize, stopResizing]);

  const sidebarWidth = isCollapsed ? 72 : width;

  const filteredItems = NAV_ITEMS.filter(item => !item.permission || hasPermission(item.permission));

  const isActive = (href: string) => {
    if (href === '/dashboard') return url === '/dashboard';
    return url.startsWith(href);
  };

  return (
    <LayoutGroup>
      <motion.aside
        className={cn(
          "relative z-40 flex flex-col h-screen border-r border-border bg-card transition-colors duration-300",
          isResizing && "select-none"
        )}
        animate={{ width: sidebarWidth }}
        transition={{ type: "spring", stiffness: 300, damping: 30 }}
      >
        {/* Header: Logo & Workspace */}
        <div className="flex flex-col p-4 gap-4 overflow-hidden">
          <div className={cn("flex items-center", isCollapsed ? "justify-center" : "gap-3")}>
            <Logo size="sm" className="text-primary shrink-0" />
            {!isCollapsed && (
              <motion.span 
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                className="font-display text-xl font-bold tracking-tight"
              >
                Verdant
              </motion.span>
            )}
          </div>

          {!isCollapsed && (
            <div className="flex flex-col gap-2">
              <Button variant="outline" size="sm" className="justify-between px-2 bg-muted/30 border-muted-foreground/10 hover:bg-muted/50 h-9">
                <div className="flex items-center gap-2 overflow-hidden">
                  <div className="w-5 h-5 rounded bg-primary/20 flex items-center justify-center text-[10px] font-bold text-primary">M</div>
                  <span className="truncate text-xs font-medium">MenaDevs Org</span>
                </div>
                <ChevronDown className="w-3 h-3 text-muted-foreground" />
              </Button>

              <Button 
                variant="muted" 
                size="sm" 
                className="justify-start gap-2 h-9 px-3 bg-muted/40 text-muted-foreground hover:text-foreground"
                onClick={onOpenSearch}
              >
                <Search className="w-4 h-4" />
                <span className="text-xs">Search or jump to...</span>
                <span className="ml-auto text-[10px] bg-muted-foreground/10 px-1 rounded border border-muted-foreground/20">⌘K</span>
              </Button>
            </div>
          )}

          {isCollapsed && (
            <Button variant="ghost" size="icon" className="w-10 h-10 mx-auto" onClick={onOpenSearch}>
              <Search className="w-5 h-5" />
            </Button>
          )}
        </div>

        {/* Navigation */}
        <ScrollArea className="flex-1 px-3">
          <div className="flex flex-col gap-6 py-4">
            {NAV_GROUPS.map((group) => {
              const groupItems = filteredItems.filter(i => i.group === group.id);
              if (groupItems.length === 0) return null;

              return (
                <div key={group.id} className="flex flex-col gap-1">
                  {!isCollapsed && (
                    <h3 className="px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70">
                      {group.label}
                    </h3>
                  )}
                  {groupItems.map((item) => (
                    <NavItemView 
                      key={item.href} 
                      item={item} 
                      isCollapsed={isCollapsed} 
                      active={isActive(item.href)} 
                    />
                  ))}
                  {isCollapsed && <Separator className="my-2 bg-muted/20" />}
                </div>
              );
            })}
          </div>
        </ScrollArea>

        {/* Footer */}
        <div className="mt-auto p-4 border-t border-border bg-muted/5">
          <div className="flex flex-col gap-4">
            {!isCollapsed && (
              <div className="bg-primary/5 rounded-lg p-3 border border-primary/10">
                <div className="flex items-center gap-2 mb-1">
                  <Sparkles className="w-3 h-3 text-primary" />
                  <span className="text-[10px] font-bold uppercase tracking-tighter text-primary">What's New</span>
                </div>
                <p className="text-[11px] leading-relaxed text-muted-foreground">
                  RLHF Evaluation 2.0 is live! Check the latest quiz builder features.
                </p>
              </div>
            )}

            <div className={cn("flex items-center", isCollapsed ? "justify-center" : "justify-between")}>
              {isCollapsed ? (
                <UserWidgetCompact user={user} />
              ) : (
                <UserWidgetExpanded user={user} />
              )}
            </div>
          </div>
        </div>

        {/* Sidebar Toggle & Resize Handle */}
        {!isCollapsed && (
          <div
            className="absolute top-0 right-0 w-1 h-full cursor-col-resize hover:bg-primary/20 transition-colors"
            onMouseDown={startResizing}
          />
        )}
        <Button
          variant="ghost"
          size="icon"
          className="absolute -right-3 top-20 w-6 h-6 rounded-full border border-border bg-background shadow-sm hover:bg-muted"
          onClick={onToggle}
        >
          {isCollapsed ? <ChevronRight className="w-3 h-3" /> : <ChevronLeft className="w-3 h-3" />}
        </Button>
      </motion.aside>
    </LayoutGroup>
  );
}

function NavItemView({ item, isCollapsed, active }: { item: NavItem; isCollapsed: boolean; active: boolean }) {
  const Icon = item.icon;
  
  const content = (
    <Link
      href={item.href}
      className={cn(
        "group relative flex items-center rounded-md px-3 py-2 text-sm font-medium transition-all duration-200",
        active 
          ? "bg-primary/10 text-primary" 
          : "text-muted-foreground hover:bg-muted hover:text-foreground",
        isCollapsed && "justify-center px-0"
      )}
    >
      {active && (
        <motion.div
          layoutId="active-nav-indicator"
          className="absolute left-0 w-1 h-6 bg-primary rounded-r-full"
          transition={{ type: "spring", stiffness: 300, damping: 30 }}
        />
      )}
      
      <Icon className={cn("w-5 h-5 shrink-0", active ? "text-primary" : "text-muted-foreground group-hover:text-foreground")} />
      
      {!isCollapsed && (
        <motion.span 
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="ml-3"
        >
          {item.label}
        </motion.span>
      )}

      {!isCollapsed && item.badge && (
        <Badge variant="verdant" className="ml-auto px-1.5 h-4 text-[10px] font-bold">
          {item.badge}
        </Badge>
      )}
      
      {isCollapsed && item.badge && (
        <div className="absolute top-1 right-2 w-2 h-2 rounded-full bg-primary border-2 border-background" />
      )}
    </Link>
  );

  if (isCollapsed) {
    return (
      <TooltipProvider>
        <Tooltip delayDuration={0}>
          <TooltipTrigger asChild>{content}</TooltipTrigger>
          <TooltipContent side="right" className="font-medium">
            {item.label}
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  return content;
}

function UserWidgetCompact({ user }: { user: any }) {
  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" className="w-10 h-10 rounded-full bg-muted overflow-hidden border border-border">
          {user?.avatar ? <img src={user.avatar} className="w-full h-full object-cover" /> : <User className="w-5 h-5" />}
        </Button>
      </PopoverTrigger>
      <PopoverContent side="right" align="end" className="w-56 p-2">
        <UserMenuItems user={user} />
      </PopoverContent>
    </Popover>
  );
}

function UserWidgetExpanded({ user }: { user: any }) {
  return (
    <div className="flex items-center gap-3 w-full">
      <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center shrink-0 border border-border overflow-hidden">
        {user?.avatar ? <img src={user.avatar} className="w-full h-full object-cover" /> : <User className="w-5 h-5" />}
      </div>
      <div className="flex flex-col min-w-0 flex-1">
        <span className="text-sm font-bold truncate">{user?.name}</span>
        <span className="text-[10px] uppercase font-bold tracking-tight text-primary">{user?.roles?.[0] || 'User'}</span>
      </div>
      <Popover>
        <PopoverTrigger asChild>
          <Button variant="ghost" size="icon" className="w-8 h-8 shrink-0">
            <ChevronDown className="w-4 h-4 text-muted-foreground" />
          </Button>
        </PopoverTrigger>
        <PopoverContent align="end" className="w-56 p-2">
          <UserMenuItems user={user} />
        </PopoverContent>
      </Popover>
    </div>
  );
}

function UserMenuItems({ user }: { user: any }) {
  return (
    <div className="flex flex-col gap-1">
      <div className="px-2 py-1.5 mb-1">
        <p className="text-xs font-medium text-muted-foreground">Support & Feedback</p>
      </div>
      <Button variant="ghost" size="sm" className="justify-start gap-2 h-9">
        <Monitor className="w-4 h-4" />
        <span>System Status</span>
      </Button>
      <Button variant="ghost" size="sm" className="justify-start gap-2 h-9">
        <MessageSquare className="w-4 h-4" />
        <span>Documentation</span>
      </Button>
      <Separator className="my-1" />
      <div className="flex items-center gap-1 p-1 bg-muted/40 rounded-md">
        <Button variant="ghost" size="icon" className="h-7 w-7 rounded-sm bg-background shadow-sm"><Sun className="h-3.5 w-3.5" /></Button>
        <Button variant="ghost" size="icon" className="h-7 w-7 rounded-sm"><Moon className="h-3.5 w-3.5" /></Button>
        <Button variant="ghost" size="icon" className="h-7 w-7 rounded-sm"><Monitor className="h-3.5 w-3.5" /></Button>
      </div>
      <Separator className="my-1" />
      <Link href={route('logout')} method="post" as="button" className="flex items-center gap-2 px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10 rounded-md transition-colors w-full text-left font-medium">
        <LogOut className="w-4 h-4" />
        <span>Log out</span>
      </Link>
    </div>
  );
}
