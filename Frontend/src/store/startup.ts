import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface Startup {
  id: string;
  name: string;
  logo: string | null;
  tagline?: string;
  description?: string;
  status: "draft" | "in-progress" | "submitted" | "approved";
  completionPercentage: number;
  createdAt?: string;
  updatedAt?: string;
}

type State = {
  currentStartup: Startup | null;
  startups: Startup[];
};

type Actions = {
  setCurrentStartup: (startup: Startup | null) => void;
  setStartups: (startups: Startup[]) => void;
  switchStartup: (startupId: string) => void;
  addStartup: (startup: Startup) => void;
  removeStartup: (startupId: string) => void;
  updateStartup: (startupId: string, updates: Partial<Startup>) => void;
};

const initialState: State = {
  currentStartup: null,
  startups: [],
};

export const useStartupStore = create<State & Actions>()(
  persist(
    (set, get) => ({
      ...initialState,

      setCurrentStartup: (startup) => set({ currentStartup: startup }),

      setStartups: (startups) => set({ startups }),

      switchStartup: (startupId) => {
        const startup = get().startups.find((s) => s.id === startupId);
        if (startup) {
          set({ currentStartup: startup });
        }
      },

      addStartup: (startup) => {
        set((state) => ({
          startups: [...state.startups, startup],
          currentStartup: startup,
        }));
      },

      removeStartup: (startupId) => {
        set((state) => {
          const filteredStartups = state.startups.filter(
            (s) => s.id !== startupId
          );
          const currentStartup =
            state.currentStartup?.id === startupId
              ? filteredStartups[0] || null
              : state.currentStartup;
          return {
            startups: filteredStartups,
            currentStartup,
          };
        });
      },

      updateStartup: (startupId, updates) => {
        set((state) => ({
          startups: state.startups.map((s) =>
            s.id === startupId ? { ...s, ...updates } : s
          ),
          currentStartup:
            state.currentStartup?.id === startupId
              ? { ...state.currentStartup, ...updates }
              : state.currentStartup,
        }));
      },
    }),
    {
      name: "startup-storage",
      partialize: (state) => ({
        currentStartup: state.currentStartup,
        startups: state.startups,
      }),
    }
  )
);
