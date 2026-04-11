import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';

interface TopBarState {
  left?: ReactNode;
  center?: ReactNode;
  right?: ReactNode;
  title?: string;
}

interface TopBarContextType extends TopBarState {
  setLeft: (content: ReactNode) => void;
  setCenter: (content: ReactNode) => void;
  setRight: (content: ReactNode) => void;
  setTitle: (title: string) => void;
  clearAll: () => void;
}

const TopBarContext = createContext<TopBarContextType | undefined>(undefined);

export function TopBarProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<TopBarState>({});

  const setLeft = useCallback((content: ReactNode) => setState((prev) => ({ ...prev, left: content })), []);
  const setCenter = useCallback((content: ReactNode) => setState((prev) => ({ ...prev, center: content })), []);
  const setRight = useCallback((content: ReactNode) => setState((prev) => ({ ...prev, right: content })), []);
  const setTitle = useCallback((title: string) => setState((prev) => ({ ...prev, title })), []);
  const clearAll = useCallback(() => setState({}), []);

  return (
    <TopBarContext.Provider value={{ ...state, setLeft, setCenter, setRight, setTitle, clearAll }}>
      {children}
    </TopBarContext.Provider>
  );
}

export function useTopBar() {
  const context = useContext(TopBarContext);
  if (!context) {
    throw new Error('useTopBar must be used within a TopBarProvider');
  }
  return context;
}
