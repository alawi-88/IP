"use client";

import { Divider } from "antd";
import Image from "next/image";
import DashboardMenu from "./DashboardMenu";
import { useThemeModeStore, useThemeStore } from "@/store/theme";
import { Link } from "@/i18n/routing";

export default function DashboardSidebar({ type }: { type: string }) {
  const theme = useThemeStore((state) => state.theme)!;
  const mode = useThemeModeStore((state) => state.mode)!;
  return (
    <div className="w-72 flex flex-col gap-y-8 h-full bg-card border-e border-[#F2F4F7] dark:border-none">
      <div>
        <div className="p-6">
          <Link href={"/site"}>
            <Image
              src={mode === "dark" ? theme.white_logo : theme.logo}
              alt="logo"
              width={100}
              height={100}
              loading="eager"
              className="dynamic-logo"
              priority
            />
          </Link>
        </div>
      </div>

      <DashboardMenu type={type} />
    </div>
  );
}
