"use client";

import { Collapse, CollapseProps } from "antd";
import { useTranslations, useLocale } from "next-intl";
import { usePathname } from "next/navigation";
import { Link } from "@/i18n/routing";
import ProgressBar from "./ProgressBar";
import { useState } from "react";

interface SidebarPage {
  key: string;
  label: string;
  completionPercentage?: number;
  href: string;
}

interface SidebarSection {
  key: string;
  label: string;
  completionPercentage: number;
  pages: SidebarPage[];
}

interface VaSidebarProps {
  sections: SidebarSection[];
  startupId: string;
  collapsed?: boolean;
}

export default function VaSidebar({
  sections,
  startupId,
  collapsed = false,
}: VaSidebarProps) {
  const t = useTranslations();
  const pathname = usePathname();
  const locale = useLocale();
  const [activeKeys, setActiveKeys] = useState<string[]>(
    sections.map((s) => s.key)
  );

  const items: CollapseProps["items"] = sections.map((section) => ({
    key: section.key,
    label: (
      <div className="flex items-center justify-between gap-2">
        <span className="font-medium">{section.label}</span>
        {!collapsed && (
          <ProgressBar
            percentage={section.completionPercentage}
            size="small"
            showLabel={false}
          />
        )}
      </div>
    ),
    children: (
      <div className="space-y-2">
        {section.pages.map((page) => {
          const isActive = pathname.includes(page.href);
          return (
            <Link key={page.key} href={page.href}>
              <div
                className={`p-2 rounded-lg transition-colors ${
                  isActive
                    ? "bg-primary-500 text-white"
                    : "text-gray-700 hover:bg-gray-100"
                }`}
              >
                <div className="flex items-center justify-between gap-2">
                  <span className="text-sm">{page.label}</span>
                  {page.completionPercentage && page.completionPercentage > 0 && (
                    <span className="text-xs font-medium">
                      {page.completionPercentage}%
                    </span>
                  )}
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    ),
  }));

  return (
    <div className={`transition-all duration-300 ${collapsed ? "w-[68px]" : "w-60"}`}>
      <Collapse
        items={items}
        activeKey={activeKeys}
        onChange={(keys) => setActiveKeys(keys as string[])}
        accordion={false}
      />
    </div>
  );
}
