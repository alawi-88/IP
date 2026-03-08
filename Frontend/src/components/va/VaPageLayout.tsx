"use client";

import { Breadcrumb, Button, message, Spin } from "antd";
import { useTranslations, useLocale } from "next-intl";
import { useCallback, useEffect, useState } from "react";
import { useRouter } from "@/i18n/routing";
import { IoCheckmarkDone } from "react-icons/io5";

interface BreadcrumbItem {
  title: string;
  href?: string;
}

interface VaPageLayoutProps {
  title: string;
  breadcrumbs: BreadcrumbItem[];
  completionPercentage?: number;
  isCompleted?: boolean;
  onSave?: (data: any) => Promise<void>;
  onMarkComplete?: () => Promise<void>;
  children: React.ReactNode;
}

export default function VaPageLayout({
  title,
  breadcrumbs,
  completionPercentage = 0,
  isCompleted = false,
  onSave,
  onMarkComplete,
  children,
}: VaPageLayoutProps) {
  const t = useTranslations();
  const router = useRouter();
  const [saveStatus, setSaveStatus] = useState<"idle" | "saving" | "saved">(
    "idle"
  );
  const [isMarking, setIsMarking] = useState(false);
  const locale = useLocale();

  useEffect(() => {
    if (saveStatus === "saved") {
      const timer = setTimeout(() => setSaveStatus("idle"), 2000);
      return () => clearTimeout(timer);
    }
  }, [saveStatus]);

  const handleMarkComplete = useCallback(async () => {
    if (isCompleted) {
      message.warning(t("va.alreadyCompleted", "This page is already marked as complete"));
      return;
    }

    try {
      setIsMarking(true);
      await onMarkComplete?.();
      message.success(t("va.markedComplete", "Marked as complete!"));
      setSaveStatus("saved");
    } catch (error) {
      message.error(t("va.failedToMark", "Failed to mark as complete"));
      console.error(error);
    } finally {
      setIsMarking(false);
    }
  }, [isCompleted, onMarkComplete, t]);

  const breadcrumbItems = [
    {
      title: t("va.startups", "Startups"),
      onClick: () => router.push("/participant-dashboard/startups"),
    },
    ...breadcrumbs.map((item, index) => ({
      title: item.title,
      ...(item.href && {
        onClick: () => router.push(item.href),
      }),
    })),
  ];

  return (
    <div className="space-y-6">
      {/* Breadcrumbs */}
      <Breadcrumb items={breadcrumbItems} />

      {/* Page Header */}
      <div className="flex items-center justify-between gap-4">
        <div className="flex-1">
          <h1 className="text-3xl font-bold text-primary-900">{title}</h1>
          {completionPercentage > 0 && (
            <p className="text-sm text-gray-600 mt-2">
              {completionPercentage}% {t("va.complete", "complete")}
            </p>
          )}
        </div>

        <div className="flex items-center gap-3">
          {saveStatus === "saved" && (
            <span className="flex items-center gap-1 text-sm text-green-600">
              <IoCheckmarkDone size={16} />
              {t("va.autoSaved", "Auto-saved")}
            </span>
          )}
          {saveStatus === "saving" && (
            <span className="flex items-center gap-1 text-sm text-gray-600">
              <Spin size="small" />
              {t("saving", "Saving...")}
            </span>
          )}

          {!isCompleted && onMarkComplete && (
            <Button
              type="primary"
              loading={isMarking}
              onClick={handleMarkComplete}
              icon={<IoCheckmarkDone size={16} />}
            >
              {t("va.markComplete", "Mark as Complete")}
            </Button>
          )}
          {isCompleted && (
            <Button type="primary" disabled icon={<IoCheckmarkDone size={16} />}>
              {t("va.completed", "Completed")}
            </Button>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        {children}
      </div>
    </div>
  );
}
