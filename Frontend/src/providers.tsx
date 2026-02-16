// In Next.js, this file would be called: app/providers.tsx
"use client";

import { AntdRegistry } from "@ant-design/nextjs-registry";
// Since QueryClientProvider relies on useContext under the hood, we have to put 'use client' on top
import {
  isServer,
  QueryClient,
  QueryClientProvider,
} from "@tanstack/react-query";
import { ConfigProvider } from "antd";
import { form, createThemeConfig } from "./config/antd";
import { useLocale } from "next-intl";
import { useEffect } from "react";
import { useUserStore } from "./store/user";
import dayjs from "dayjs";
import "dayjs/locale/ar";
import arEG from "antd/lib/locale/ar_EG";
import updateLocale from "dayjs/plugin/updateLocale";
import { useThemeStore, useThemeModeStore } from "./store/theme";

function makeQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        // With SSR, we usually want to set some default staleTime
        // above 0 to avoid refetching immediately on the client
        staleTime: 60 * 1000,
      },
    },
  });
}

let browserQueryClient: QueryClient | undefined = undefined;

function getQueryClient() {
  if (isServer) {
    // Server: always make a new query client
    return makeQueryClient();
  } else {
    // Browser: make a new query client if we don't already have one
    // This is very important, so we don't re-make a new client if React
    // suspends during the initial render. This may not be needed if we
    // have a suspense boundary BELOW the creation of the query client
    if (!browserQueryClient) browserQueryClient = makeQueryClient();
    return browserQueryClient;
  }
}


export default function Providers({
  children,
  theme,
}: {
  children: React.ReactNode;
  theme: any;
}) {
  // NOTE: Avoid useState when initializing the query client if you don't
  //       have a suspense boundary between this and the code that may
  //       suspend because React will throw away the client on the initial
  //       render if it suspends and there is no boundary
  const queryClient = getQueryClient();
  const locale = useLocale();
  const isArabic = locale === "ar";
  const user = useUserStore((state) => state.participant);

  // save theme into store
  const storeTheme = useThemeStore((state) => state.theme)!;
  const setTheme = useThemeStore((state) => state.setTheme);
  
  // Get theme mode from persistent store
  const themeMode = useThemeModeStore((state) => state.mode);

  useEffect(() => {
    if (theme) {
      setTheme(theme);
    }
  }, [theme, setTheme]);

  // Apply dark mode class on initial load
  useEffect(() => {
    if (typeof window !== "undefined") {
      const root = document.documentElement;
      if (themeMode === "dark") {
        root.classList.add("dark");
      } else {
        root.classList.remove("dark");
      }
    }
  }, [themeMode , locale]);

  useEffect(() => {
    if (isArabic) {
      dayjs.extend(updateLocale);

      dayjs.updateLocale("ar", {
        meridiem: (hour: any, minute: any, isLowercase: any) => {
          return hour < 12 ? "ص" : "م";
        },
        meridiemParse: /ص|م/,
        isPM: (input: any) => input === "م",
      });
      dayjs.locale("ar");
    } else {
      dayjs.locale("en");
    }
  }, [locale]);

  useEffect(() => {
    // Invalidate all queries when locale changes
    queryClient.invalidateQueries();
  }, [locale, user, queryClient]);

  if (!storeTheme?.theme_status) return;

  const isDarkMode = themeMode === "dark";

  return (
    <QueryClientProvider client={queryClient}>
      <AntdRegistry>
        <ConfigProvider
          {...(isArabic ? { locale: arEG } : {})}
          wave={{ disabled: true }}
          form={form(locale)}
          theme={createThemeConfig(storeTheme, isDarkMode)}
        >
          <div
            className={`transition-opacity duration-200 ${
              !storeTheme?.theme_status ? "opacity-0" : "opacity-1"
            }`}
          >
            {children}
          </div>
        </ConfigProvider>
      </AntdRegistry>
    </QueryClientProvider>
  );
}
