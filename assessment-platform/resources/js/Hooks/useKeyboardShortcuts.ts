import { useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';

interface ShortcutOptions {
  onToggleSidebar?: () => void;
  onOpenCommandPalette?: () => void;
}

export function useKeyboardShortcuts({ onToggleSidebar, onOpenCommandPalette }: ShortcutOptions = {}) {
  const handleKeyDown = useCallback(
    (event: KeyboardEvent) => {
      // Don't trigger if user is typing in an input/textarea
      const target = event.target as HTMLElement;
      const isInput =
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.isContentEditable;

      if (isInput) return;

      const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
      const modifier = isMac ? event.metaKey : event.ctrlKey;

      // ⌘K or Ctrl+K - Command Palette
      if (modifier && event.key === 'k') {
        event.preventDefault();
        onOpenCommandPalette?.();
      }

      // ⌘B or Ctrl+B - Sidebar
      if (modifier && event.key === 'b') {
        event.preventDefault();
        onToggleSidebar?.();
      }

      // ⌘/ - Help/Shortcuts (to be implemented)
      if (modifier && event.key === '/') {
        event.preventDefault();
        // showShortcutsModal();
      }

      // Sequence: g then [key]
      // This is a bit simpler: we check if 'g' was pressed recently
      // But for now, let's just handle them as combined keys or separate logic
      // VIM style sequences usually require a state machines.
      // For this implementation, we'll focus on the primary ones first.
    },
    [onToggleSidebar, onOpenCommandPalette]
  );

  useEffect(() => {
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleKeyDown]);

  // Handle sequences like 'gd'
  useEffect(() => {
    let lastKey = '';
    let lastTime = 0;

    const handleSequence = (event: KeyboardEvent) => {
      const target = event.target as HTMLElement;
      if (
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.isContentEditable
      )
        return;

      const now = Date.now();
      const key = event.key.toLowerCase();

      if (lastKey === 'g' && now - lastTime < 1000) {
        if (key === 'd') {
          event.preventDefault();
          router.visit(route('dashboard'));
        } else if (key === 'q') {
          event.preventDefault();
          router.visit(route('admin.quizzes.index'));
        } else if (key === 'c') {
          event.preventDefault();
          router.visit(route('admin.candidates.index'));
        }
        lastKey = '';
      } else {
        lastKey = key;
        lastTime = now;
      }
    };

    window.addEventListener('keydown', handleSequence);
    return () => window.removeEventListener('keydown', handleSequence);
  }, []);
}
