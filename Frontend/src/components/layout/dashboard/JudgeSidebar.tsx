"use client";

import { Divider } from "antd";
import Image from "next/image";
import JudgeNavbar from "./JudgeNavbar";
import { useThemeStore } from "@/store/theme";

export default function JudgeSidebar() {
  const theme = useThemeStore((state) => state.theme)!;
  return (
    <div className="w-72 flex flex-col gap-y-8 h-full bg-card border-e border-[#F2F4F7]">
      <div>
        <div className="p-6">
          <Image
            src={theme.logo}
            alt="logo"
            width={100}
            height={100}
            loading="eager"
            className="dynamic-logo"
            priority
          />
        </div>

        
      </div>

      <JudgeNavbar />
    </div>
  );
}
