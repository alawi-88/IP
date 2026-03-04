"use client";

import { Spin, Breadcrumb, Layout, Button, Dropdown } from "antd";
import { useRouter, useParams } from "@/i18n/routing";
import { useTranslations, useLocale } from "next-intl";
import { useStartupStore } from "@/store/startup";
import { useQuery } from "@tanstack/react-query";
import * as startupApi from "@/config/startup-api";
import VaSidebar from "@/components/va/VaSidebar";
import StartupSelector from "@/components/va/StartupSelector";
import { useState, useEffect } from "react";
import { FiChevronDown } from "react-icons/fi";
import type { MenuProps } from "antd";

const VA_SECTIONS = [
  {
    key: "foundation",
    label: "Foundation",
    pages: [
      { key: "overview", label: "Overview", href: "foundation/overview" },
      { key: "market-analysis", label: "Market Analysis", href: "foundation/market-analysis" },
      { key: "financial-model", label: "Financial Model", href: "foundation/financial-model" },
    ],
  },
  {
    key: "strategic-frameworks",
    label: "Strategic Frameworks",
    pages: [
      { key: "swot", label: "SWOT Analysis", href: "strategic-frameworks/swot" },
      { key: "mvp-canvas", label: "MVP Canvas", href: "strategic-frameworks/mvp-canvas" },
      { key: "bmc", label: "Business Model Canvas", href: "strategic-frameworks/bmc" },
      { key: "business-plan", label: "Business Plan", href: "strategic-frameworks/business-plan" },
      { key: "gtm-overview", label: "GTM Overview", href: "strategic-frameworks/gtm-overview" },
      { key: "pestel", label: "PESTEL", href: "strategic-frameworks/pestel" },
    ],
  },
  {
    key: "path-to-mvp",
    label: "Path to MVP",
    pages: [
      { key: "features", label: "Features", href: "path-to-mvp/features" },
      { key: "validation", label: "Validation", href: "path-to-mvp/validation" },
      { key: "timeline", label: "Timeline", href: "path-to-mvp/timeline" },
      { key: "marketing", label: "Marketing", href: "path-to-mvp/marketing" },
      { key: "budget", label: "Budget", href: "path-to-mvp/budget" },
      { key: "metrics", label: "Metrics", href: "path-to-mvp/metrics" },
    ],
  },
  {
    key: "gtm-strategy",
    label: "GTM Strategy",
    pages: [
      { key: "overview", label: "Overview", href: "gtm-strategy/overview" },
      { key: "customer-segments", label: "Customer Segments", href: "gtm-strategy/customer-segments" },
      { key: "value-proposition", label: "Value Proposition", href: "gtm-strategy/value-proposition" },
    ],
  },
  {
    key: "competitive-analysis",
    label: "Competitive Analysis",
    pages: [
      { key: "competitors", label: "Competitors", href: "competitive-analysis/competitors" },
      { key: "matrix", label: "Competitor Matrix", href: "competitive-analysis/matrix" },
    ],
  },
];

export default function StartupLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const t = useTranslations();
  const locale = useLocale() as "en" | "ar";
  const router = useRouter();
  const params = useParams();
  const startupId = params.startupId as string;
  const { setCurrentStartup, startups } = useStartupStore();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  const { data: startup, isLoading } = useQuery({
    queryKey: ["startup", startupId],
    queryFn: () => startupApi.getStartup(startupId),
    onSuccess: (data) => {
      setCurrentStartup({
        id: data.id,
        name: data.name,
        logo: data.logo,
        tagline: data.tagline,
        description: data.description,
        status: data.status,
        completionPercentage: data.completionPercentage,
      });
    },
  });

  if (isLoading) {
    return <Spin />;
  }

  if (!startup) {
    return <div>{t("notFound", "Not Found")}</div>;
  }

  const sectionData = VA_SECTIONS.map((section) => ({
    key: section.key,
    label: t(`va.${section.key}`, section.label),
    completionPercentage: 0,
    pages: section.pages.map((page) => ({
      key: page.key,
      label: t(`va.${page.key}`, page.label),
      completionPercentage: 0,
      href: `/participant-dashboard/startups/${startupId}/${page.href}`,
    })),
  }));

  return (
    <Layout className="h-full bg-transparent">
      {/* Header with Startup Selector */}
      <Layout.Header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            type="text"
            onClick={() => router.push("/participant-dashboard/startups")}
          >
            {t("va.startups", "Startups")}
          </Button>
          <span>/</span>
          <StartupSelector
            onCreateNew={() =>
              router.push("/participant-dashboard/startups")
            }
          />
        </div>
      </Layout.Header>

      <Layout className="h-full bg-transparent">
        {/* Sidebar */}
        <Layout.Sider
          className="bg-white border-e border-gray-200"
          width={sidebarCollapsed ? 80 : 280}
          collapsible
          collapsed={sidebarCollapsed}
          onCollapse={setSidebarCollapsed}
          trigger={null}
        >
          <div className="p-4">
            <VaSidebar
              sections={sectionData}
              startupId={startupId}
              collapsed={sidebarCollapsed}
            />
          </div>
        </Layout.Sider>

        {/* Main Content */}
        <Layout.Content className="overflow-y-auto p-6">
          {children}
        </Layout.Content>
      </Layout>
    </Layout>
  );
}
