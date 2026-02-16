"use client";

import { Link, usePathname } from "@/i18n/routing";
import { Button } from "antd";
import { useTranslations } from "next-intl";
import Image from "next/image";
import LocaleSwitcher from "./LocaleSwitcher";
import { useThemeModeStore, useThemeStore } from "@/store/theme";
import { getUser } from "@/hooks/useTokenSaver";
import ThemeToggle from "./ThemeToggle";

type HeaderProps = {
  className?: string;
  type: string;
};

export default function Header({ className = "", type }: HeaderProps) {
  const t = useTranslations();
  const pathname = usePathname();
  const user = getUser();
  const userType = user?.user_type;
  const theme = useThemeStore((state) => state.theme)!;
  const mode = useThemeModeStore((state) => state.mode)!;
  const isRegisterPage = pathname.includes("register");
  const prefix =
    type === "judge" ? "/judge" : type === "mentor" ? "/mentor" : "";

  return (
    <header
      className={`z-40 sticky top-0 bg-card ${className ? className : ""}`}
    >
      <div className="container max-w-full flex justify-between w-full h-full items-center px-4 md:px-16 2xl:px-24 py-4 ">
        <div className="flex gap-4 items-center">
          <Link href={"/site"}>
            <Image
              src={mode === "dark" ? theme.white_logo : theme.logo}
              alt="logo"
              width={100}
              height={100}
              loading={"eager"}
              className="dynamic-logo"
              priority
            />
          </Link>
        </div>

        <div className="flex items-stretch gap-x-2">
          <ThemeToggle />
          <LocaleSwitcher />
          {!user && (
            <Link
              href={
                isRegisterPage || type === "site"
                  ? `${prefix}/login`
                  : `${prefix}/register`
              }
            >
              <Button type="primary">
                {isRegisterPage || type === "site" ? t("login") : t("sign-up")}
              </Button>
            </Link>
          )}
          {user && className === "site_header" && (
            <Link
              href={
                userType === "judge"
                  ? "/judge/judge-dashboard"
                  : userType === "mentor"
                  ? "/mentor/mentor-dashboard"
                  : "/participant-dashboard"
              }
            >
              <Button type="primary">{t("site.dashboard")}</Button>
            </Link>
          )}
        </div>
      </div>
    </header>
  );
}
