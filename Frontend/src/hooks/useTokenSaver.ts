import { AccessTokenPayload } from "@/lib/interfaces";
import Cookies from "js-cookie";
import jwt from "jsonwebtoken";

export const saveAccessToken = (accessToken: string) => {
  Cookies.set("accessToken", accessToken);
};

export const removeAccessToken = () => {
  Cookies.remove("accessToken");
};

export const getAccessToken = () => {
  return Cookies.get("accessToken");
};

export function getUser() {
  const accessToken = getAccessToken();
  if (!accessToken) return null;
  try {
    const decoded = jwt.decode(accessToken) as AccessTokenPayload | null;
    if (!decoded || !decoded.user_type) {
      removeAccessToken();
      return null;
    }

    return decoded;
  } catch (err) {
    removeAccessToken();
    console.error("Error decoding accessToken:", err);
    return null;
  }
}

export function getCookie(name: string): string | undefined {
  const match = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
  return match ? decodeURIComponent(match[2]) : undefined;
}
