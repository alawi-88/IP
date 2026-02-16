// utils/logout.ts
import {
  getAccessToken,
  getCookie,
  removeAccessToken,
} from "@/hooks/useTokenSaver";
import { useUserStore } from "@/store/user";

export function logoutAndRedirect() {
  const accessToken = getAccessToken();
  if (!accessToken) {
    return;
  }
  const userType = useUserStore.getState().getUserType();
  removeAccessToken();
  useUserStore.getState().logout();
  if (typeof window !== "undefined") {
    const locale = getCookie("NEXT_LOCALE") || "en";
    const currentPath = window.location.pathname;
    const redirectPath = currentPath.includes("site")
      ? `/${locale}/site`
      : userType === "participant"
      ? `/${locale}/login`
      : userType === "judge"
      ? `/${locale}/judge/login`
      : userType === "mentor"
      ? `/${locale}/mentor/login`
      : `/${locale}/login`;

    window.location.assign(redirectPath);
  }
}
