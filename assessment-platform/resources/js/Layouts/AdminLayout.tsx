import { PropsWithChildren, useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { GreenhouseSidebar } from '@/components/verdant/navigation/greenhouse';
import { TopBar } from '@/components/verdant/navigation/topbar';
import { CommandPalette } from '@/components/verdant/navigation/commandpalette';
import { TopBarProvider } from '@/Contexts/TopBarContext';
import { useKeyboardShortcuts } from '@/Hooks/useKeyboardShortcuts';
import { Toaster } from '@/components/ui/sonner';
import FlashNotifications from '@/components/FlashNotifications';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

export default function AdminLayout({ children }: PropsWithChildren) {
  const { url } = usePage();
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);
  const [isCommandPaletteOpen, setIsCommandPaletteOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Load sidebar state from localStorage
  useEffect(() => {
    const saved = localStorage.getItem('verdant-sidebar-collapsed');
    if (saved) setIsSidebarCollapsed(saved === 'true');
  }, []);

  const toggleSidebar = () => {
    const newState = !isSidebarCollapsed;
    setIsSidebarCollapsed(newState);
    localStorage.setItem('verdant-sidebar-collapsed', newState.toString());
  };

  // Keyboard shortcuts integration
  useKeyboardShortcuts({
    onToggleSidebar: toggleSidebar,
    onOpenCommandPalette: () => setIsCommandPaletteOpen(true)
  });

  return (
    <TopBarProvider>
      <div className="flex h-screen bg-background overflow-hidden selection:bg-primary/20 selection:text-primary">
        {/* Greenhouse Sidebar (Desktop) */}
        <div className="hidden lg:block h-full shrink-0">
          <GreenhouseSidebar 
            isCollapsed={isSidebarCollapsed} 
            onToggle={toggleSidebar}
            onOpenSearch={() => setIsCommandPaletteOpen(true)}
          />
        </div>

        {/* Mobile Sidebar (Drawer) */}
        <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
          <SheetContent side="left" className="p-0 border-r-0 w-[280px]">
            <GreenhouseSidebar 
              isCollapsed={false} 
              onToggle={() => setIsMobileMenuOpen(false)}
              onOpenSearch={() => {
                setIsMobileMenuOpen(false);
                setIsCommandPaletteOpen(true);
              }}
            />
          </SheetContent>
        </Sheet>

        {/* Main Content Area */}
        <main className="flex-1 flex flex-col min-w-0 h-full relative overflow-hidden">
          <TopBar 
            onOpenMobileMenu={() => setIsMobileMenuOpen(true)} 
            onOpenSearch={() => setIsCommandPaletteOpen(true)}
          />
          
          <div className="flex-1 overflow-y-auto overflow-x-hidden">
            <AnimatePresence mode="wait">
              <motion.div
                key={url}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
                transition={{ duration: 0.2 }}
                className="p-4 lg:p-8 max-w-[1600px] mx-auto w-full"
              >
                {children}
              </motion.div>
            </AnimatePresence>
          </div>
        </main>

        {/* Global Components */}
        <CommandPalette 
          open={isCommandPaletteOpen} 
          onOpenChange={setIsCommandPaletteOpen} 
        />
        <Toaster position="bottom-right" theme="light" />
        <FlashNotifications />
      </div>
    </TopBarProvider>
  );
}
