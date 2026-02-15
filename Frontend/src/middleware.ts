import createMiddleware from "next-intl/middleware";
import { routing } from "./i18n/routing";
import { NextRequest } from "next/server";
import jwt from "jsonwebtoken";
import { AccessTokenPayload } from "./lib/interfaces";

const handleI18nRouting = createMiddleware(routing);

function getUser(request: NextRequest) {
  const accessToken = request.cookies.get("accessToken")?.value;
  if (!accessToken) return null;
  try {
    const decoded = jwt.decode(accessToken) as AccessTokenPayload | null;
    if (!decoded || !decoded.user_type) return null;

    return { role: decoded.user_type, isAuthenticated: true };
  } catch (err) {
    console.error("Error decoding accessToken:", err);
    return null;
  }
}

export default async function middleware(request: NextRequest) {
  const [, locale, ...segments] = request.nextUrl.pathname.split("/");
  const user = getUser(request);
  const isAuthenticated = user?.isAuthenticated;
  const role = user?.role;
  console.log(role);

  // Not Authenticated → Redirect to login page depending on the URL
  if (!isAuthenticated) {
    if (segments.includes("participant-dashboard")) {
      console.debug("Unauthenticated → redirecting to participant login");
      request.nextUrl.pathname = `/login`;
      return handleI18nRouting(request);
    }

    if (segments.includes("judge-dashboard")) {
      console.debug("Unauthenticated → redirecting to judge login");
      request.nextUrl.pathname = `/judge/login`;
      return handleI18nRouting(request);
    }

    if (segments.includes("mentor-dashboard")) {
      console.debug("Unauthenticated → redirecting to mentor login");
      request.nextUrl.pathname = `/mentor/login`;
      return handleI18nRouting(request);
    }

    if (segments.length === 0) {
      console.debug("No path → redirecting to login");
      request.nextUrl.pathname = `/login`;
      return handleI18nRouting(request);
    }
  }

  // Authenticated → Redirect to dashboard based on role
  if (isAuthenticated) {
    if (
      role === "participant" &&
      !segments.includes("participant-dashboard") &&
      !segments.includes("site")
    ) {
      console.debug("Authenticated participant → redirecting to dashboard");
      request.nextUrl.pathname = `/participant-dashboard`;
      return handleI18nRouting(request);
    }

    if (
      role === "judge" &&
      !segments.includes("judge-dashboard") &&
      !segments.includes("site")
    ) {
      console.debug("Authenticated judge → redirecting to dashboard");
      request.nextUrl.pathname = `/judge/judge-dashboard`;
      return handleI18nRouting(request);
    }

    if (
      role === "mentor" &&
      !segments.includes("mentor-dashboard") &&
      !segments.includes("site")
    ) {
      console.debug("Authenticated mentor → redirecting to dashboard");
      request.nextUrl.pathname = `/mentor/mentor-dashboard`;
      return handleI18nRouting(request);
    }
  }

  return handleI18nRouting(request);
}

export const config = {
  // Match only internationalized pathnames
  matcher: ["/((?!api|_next|_vercel|.*\\..*).*)"],
};
