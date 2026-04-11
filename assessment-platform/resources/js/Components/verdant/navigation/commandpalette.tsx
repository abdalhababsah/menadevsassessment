import React, { useEffect, useState } from 'react';
import { 
  Command, 
  CommandDialog, 
  CommandEmpty, 
  CommandGroup, 
  CommandInput, 
  CommandItem, 
  CommandList, 
  CommandSeparator, 
  CommandShortcut 
} from '@/components/ui/command';
import { 
  LayoutDashboard, 
  BookOpen, 
  Users, 
  Settings, 
  FileSearch, 
  UserSquare2, 
  Plus, 
  Mail,
  Zap,
  HelpCircle,
  Moon,
  Sun,
  Monitor
} from 'lucide-react';
import { router } from '@inertiajs/react';

export function CommandPalette({ 
  open, 
  onOpenChange 
}: { 
  open: boolean; 
  onOpenChange: (open: boolean) => void;
}) {
  const [search, setSearch] = useState('');

  // Close palette on navigation
  useEffect(() => {
    return () => onOpenChange(false);
  }, [onOpenChange]);

  const runCommand = (command: () => void) => {
    onOpenChange(false);
    command();
  };

  return (
    <CommandDialog open={open} onOpenChange={onOpenChange}>
      <CommandInput 
        placeholder="Type a command or search..." 
        value={search}
        onValueChange={setSearch}
      />
      <CommandList className="max-h-[450px]">
        <CommandEmpty>No results found.</CommandEmpty>
        
        <CommandGroup heading="Recent">
          <CommandItem onSelect={() => runCommand(() => router.visit('/dashboard'))}>
            <LayoutDashboard className="mr-2 h-4 w-4" />
            <span>Dashboard</span>
            <CommandShortcut>G D</CommandShortcut>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.visit('/admin/quizzes'))}>
            <BookOpen className="mr-2 h-4 w-4" />
            <span>Quizzes</span>
            <CommandShortcut>G Q</CommandShortcut>
          </CommandItem>
        </CommandGroup>

        <CommandGroup heading="Quick Actions">
          <CommandItem onSelect={() => runCommand(() => console.log('New Quiz'))}>
            <Plus className="mr-2 h-4 w-4" />
            <span>New Quiz</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => console.log('Invite Candidate'))}>
            <Mail className="mr-2 h-4 w-4" />
            <span>Invite Candidate</span>
          </CommandItem>
        </CommandGroup>

        <CommandSeparator />

        <CommandGroup heading="Navigation">
          <CommandItem onSelect={() => runCommand(() => router.visit('/admin/candidates'))}>
            <Users className="mr-2 h-4 w-4" />
            <span>Candidates</span>
            <CommandShortcut>G C</CommandShortcut>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.visit('/admin/results'))}>
            <FileSearch className="mr-2 h-4 w-4" />
            <span>Results</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.visit('/admin/users'))}>
            <UserSquare2 className="mr-2 h-4 w-4" />
            <span>Team Members</span>
          </CommandItem>
        </CommandGroup>

        <CommandSeparator />

        <CommandGroup heading="System">
          <CommandItem onSelect={() => runCommand(() => router.visit('/admin/settings'))}>
            <Settings className="mr-2 h-4 w-4" />
            <span>Settings</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.visit('/admin/integrations'))}>
            <Zap className="mr-2 h-4 w-4" />
            <span>Integrations</span>
          </CommandItem>
          <CommandItem>
            <HelpCircle className="mr-2 h-4 w-4" />
            <span>Help & Documentation</span>
          </CommandItem>
        </CommandGroup>

        <CommandSeparator />

        <CommandGroup heading="Appearance">
          <CommandItem onSelect={() => runCommand(() => console.log('Light Mode'))}>
            <Sun className="mr-2 h-4 w-4" />
            <span>Light Mode</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => console.log('Dark Mode'))}>
            <Moon className="mr-2 h-4 w-4" />
            <span>Dark Mode</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => console.log('System Theme'))}>
            <Monitor className="mr-2 h-4 w-4" />
            <span>System Preference</span>
          </CommandItem>
        </CommandGroup>
      </CommandList>
    </CommandDialog>
  );
}

// Simple Button and Mail replacement since lucide names might vary if I'm not careful,
// but the project uses lucide-react so I'm safe with Lucide imports.
const Button = ({ children }: { children: React.ReactNode }) => <span>{children}</span>;
