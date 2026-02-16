import { removeAccessToken } from "@/hooks/useTokenSaver";
import { Judge, Mentor, Participant } from "@/lib/interfaces";
import { create } from "zustand";

type UserType = "participant" | "judge" | "mentor";

type State = {
  participant?: Participant & { userType: "participant" };
  judge?: Judge & { userType: "judge" };
  mentor?: Mentor & { userType: "mentor" };
};

type Actions = {
  loginParticipant: (user: Participant) => void;
  loginJudge: (user: Judge) => void;
  loginMentor: (user: Mentor) => void;
  logout: () => void;
  getUser: () => State["participant"] | State["judge"] | State["mentor"] | null;
  isAuthenticated: () => boolean;
  getUserType: () => UserType | null;
};

const initialState: State = {};

export const useUserStore = create<State & Actions>((set, get) => ({
  ...initialState,

  loginParticipant: (user) =>
    set({ participant: { ...user, userType: "participant" } }),

  loginJudge: (user) => set({ judge: { ...user, userType: "judge" } }),

  loginMentor: (user) => set({ mentor: { ...user, userType: "mentor" } }),

  logout: () => {
    removeAccessToken();
    set(initialState);
  },

  getUser: () => get().participant || get().judge || get().mentor || null,

  isAuthenticated: () => !!(get().participant || get().judge || get().mentor),

  getUserType: () =>
    get().participant?.userType ||
    get().judge?.userType ||
    get().mentor?.userType ||
    null,
}));
