"use client";

import { Divider, Tooltip } from "antd";
import Image from "next/image";
import DashboardMenu from "./DashboardMenu";
import { useThemeModeStore, useThemeStore } from "@/store/theme";
import { useSidebarStore } from "@/store/sidebar";
import { Link } from "@/i18n/routing";
import { HiOutlineChevronDoubleLeft, HiOutlineChevronDoubleRight } from "react-icons/hi";

export default function DashboardSidebar({ type }: { type: string }) {
  const theme = useThemeStore((state) => state.theme)!;
  const mode = useThemeModeStore((state) => state.mode)!;
  const { collapsed, toggle } = useSidebarStore();

  return (
    <div
      className={`flex flex-col h-full bg-card border-e border-[#F2F4F7] dark:border-none transition-all duration-300 ${
        collapsed ? "w-[68px]" : "w-72"
      }`}
    >
      {/* Logo */}
      <div className={`p-4 ${collapsed ? "px-3 flex justify-center" : "p-6"}`}>
        <Link href={"/site"}>
          {collapsed ? (
            <Image
              src={mode === "dark" ? theme.white_logo : theme.logo}
              alt="logo"
              width={36}
              height={36}
              loading="eager"
              className="object-contain"
              priority
            />
          ) : (
            <Image
              src={mode === "dark" ? theme.white_logo : theme.logo}
              alt="logo"
              width={100}
              height={100}
              loading="eager"
              className="dynamic-logo"
              priority
            />
          )}
        </Link>
      </div>

      {/* Menu */}
      <div className="flex-1 overflow-hidden">
        <DashboardMenu type={type} collapsed={collapsed} />
      </div>

      {/* Collapse Toggle */}
      <div className={`p-3 border-t border-[#F2F4F7] dark:border-gray-700 ${collapsed ? "flex justify-center" : ""}`}>
        <Tooltip title={collapsed ? "Expand sidebar" : "Collapse sidebar"} placement="right">
          <button
            onClick={toggle}
            className="flex items-center justify-center gap-2 w-full p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors"
          >
            {collapsed ? (
              <HiOutlineChevronDoubleRight size={18} />
            ) : (
              <>
                <HiOutlineChevronDoubleLeft size={18} />
                <span className="text-xs font-medium">Collapse</span>
              </>
            )}
          </button>
        </Tooltip>
      </div>
    </div>
  );
}
