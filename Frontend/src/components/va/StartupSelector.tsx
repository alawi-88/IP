"use client";

import { Dropdown, Button, Avatar, Space, Empty } from "antd";
import type { MenuProps } from "antd";
import { useStartupStore, Startup } from "@/store/startup";
import { useTranslations } from "next-intl";
import { FiPlus } from "react-icons/fi";
import { useState } from "react";

interface StartupSelectorProps {
  onCreateNew?: () => void;
}

export default function StartupSelector({
  onCreateNew,
}: StartupSelectorProps) {
  const t = useTranslations();
  const { currentStartup, startups, switchStartup } = useStartupStore();
  const [open, setOpen] = useState(false);

  const items: MenuProps["items"] = [
    ...startups.map((startup) => ({
      key: startup.id,
      label: (
        <Space>
          {startup.logo && (
            <Avatar src={startup.logo} size="small" />
          )}
          <div className="flex flex-col gap-0">
            <span className="font-medium">{startup.name}</span>
            <span className="text-xs text-gray-500">
              {startup.completionPercentage ?? 0}% {t("va.complete", "complete")}
            </span>
          </div>
        </Space>
      ),
      onClick: () => {
        switchStartup(startup.id);
        setOpen(false);
      },
    })),
    { type: "divider" as const },
    {
      key: "create",
      label: (
        <Space>
          <FiPlus size={16} />
          {t("va.createNewStartup", "Create New Startup")}
        </Space>
      ),
      onClick: () => {
        onCreateNew?.();
        setOpen(false);
      },
    },
  ];

  if (!currentStartup && startups.length === 0) {
    return <Empty description={t("va.noStartups", "No startups yet")} />;
  }

  return (
    <Dropdown
      menu={{ items }}
      open={open}
      onOpenChange={setOpen}
      placement="bottomLeft"
    >
      <Button type="text" className="!p-0">
        {currentStartup ? (
          <Space>
            {currentStartup.logo && (
              <Avatar src={currentStartup.logo} size="small" />
            )}
            <div className="flex flex-col gap-0">
              <span className="font-medium text-sm">{currentStartup.name}</span>
              <span className="text-xs text-gray-500">
                {currentStartup.completionPercentage ?? 0}%
              </span>
            </div>
          </Space>
        ) : (
          t("va.selectStartup", "Select a startup")
        )}
      </Button>
    </Dropdown>
  );
}
