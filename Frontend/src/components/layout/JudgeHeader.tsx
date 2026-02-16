"use client";

import { Link } from "@/i18n/routing";
import { Button } from "antd";
import { useTranslations } from "next-intl";
import Image from "next/image";
import LocaleSwitcher from "./LocaleSwitcher";
import { useUserStore } from "@/store/user";
import { useThemeStore } from "@/store/theme";

export default function Header() {
  const t = useTranslations();
  const user = useUserStore((state) => state.judge);
  const theme = useThemeStore((state) => state.theme)!;
  return (
    <header className="flex justify-between w-full items-center px-4 md:px-16 2xl:px-24 py-4 z-40 sticky top-0 bg-card">
      <Link href={"/judge/login"}>
        <Image
          src={theme.logo}
          alt="logo"
          width={100}
          height={100}
          loading="eager"
          className="dynamic-logo"
          priority
        />
      </Link>

      <div className="flex items-stretch gap-x-2">
        <LocaleSwitcher />
        {user == null && (
          <Link href={"/judge/login"}>
            <Button type="primary">{t("login")}</Button>
          </Link>
        )}
      </div>
    </header>
  );
}
