"use client";
import { Spin, Breadcrumb, Layout, Button, Dropdown } from "antd";
import { useRouter } from "@/i18n/routing";
import { useParams } from "next/navigation";
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
    i18nKey: "foundation",
    label: "Foundation",
    pages: [
      { key: "overview", i18nKey: "overview", label: "Overview", href: "foundation/overview" },
      { key: "market-analysis", i18nKey: "marketAnalysis", label: "Market Analysis", href: "foundation/market-analysis" },
      { key: "financial-model", i18nKey: "financialModel", label: "Financial Model", href: "foundation/financial-model" },
    ],
  },
  {
    key: "strategic-frameworks",
    i18nKey: "strategicFrameworks",
    label: "Strategic Frameworks",
    pages: [
      { key: "swot", i18nKey: "swot", label: "SWOT Analysis", href: "strategic-frameworks/swot" },
      { key: "mvp-canvas", i18nKey: "mvpCanvas", label: "MVP Canvas", href: "strategic-frameworks/mvp-canvas" },
      { key: "bmc", i18nKey: "bmc", label: "Business Model Canvas", href: "strategic-frameworks/bmc" },
      { key: "business-plan", i18nKey: "businessPlan", label: "Business Plan", href: "strategic-frameworks/business-plan" },
      { key: "gtm-overview", i18nKey: "gtmOverview", label: "GTM Overview", href: "strategic-frameworks/gtm-overview" },
      { key: "pestel", i18nKey: "pestel", label: "PESTEL Analysis", href: "strategic-frameworks/pestel" },
    ],
  },
  {
    key: "path-to-mvp",
    i18nKey: "pathToMvp",
    label: "Path to MVP",
    pages: [
      { key: "features", i18nKey: "features", label: "Features", href: "path-to-mvp/features" },
      { key: "validation", i18nKey: "validation", label: "Validation", href: "path-to-mvp/validation" },
      { key: "timeline", i18nKey: "timeline", label: "Timeline", href: "path-to-mvp/timeline" },
      { key: "marketing", i18nKey: "marketing", label: "Marketing", href: "path-to-mvp/marketing" },
      { key: "budget", i18nKey: "budget", label: "Budget", href: "path-to-mvp/budget" },
      { key: "metrics", i18nKey: "metrics", label: "Metrics", href: "path-to-mvp/metrics" },
    ],
  },
  {
    key: "gtm-strategy",
    i18nKey: "gtmStrategy",
    label: "GTM Strategy",
    pages: [
      { key: "overview", i18nKey: "overview", label: "Overview", href: "gtm-strategy/overview" },
      { key: "customer-segments", i18nKey: "customerSegments", label: "Customer Segments", href: "gtm-strategy/customer-segments" },
      { key: "value-proposition", i18nKey: "valueProposition", label: "Value Proposition", href: "gtm-strategy/value-proposition" },
    ],
  },
  {
    key: "competitive-analysis",
    i18nKey: "competitiveAnalysis",
    label: "Competitive Analysis",
    pages: [
      { key: "competitors", i18nKey: "competitors", label: "Competitors", href: "competitive-analysis/competitors" },
      { key: "matrix", i18nKey: "matrix", label: "Competitor Matrix", href: "competitive-analysis/matrix" },
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
  });

  const { data: apiSections } = useQuery({
    queryKey: ["startup", startupId, "sections"],
    queryFn: () => startupApi.getSections(startupId),
    enabled: !!startup,
  });

  useEffect(() => {
    if (startup) {
      setCurrentStartup({
        id: startup.id,
        name: startup.name,
        logo: startup.logo,
        tagline: startup.tagline,
        description: startup.description,
        status: startup.status,
        completionPercentage: startup.completion_percentage ?? startup.completionPercentage ?? 0,
      });
    }
  }, [startup, setCurrentStartup]);

  if (isLoading) {
    return <Spin />;
  }

  if (!startup) {
    return <div>{t("notFound", "Not Found")}</div>;
  }

  const sectionsFromApi = apiSections || [];

  const sectionData = VA_SECTIONS.map((section) => {
    const apiSection = sectionsFromApi.find((s: any) => s.section_key === section.key.replace(/-/g, '_'));
    const apiPages = apiSection?.pages || [];
    const sectionCompletion = apiSection
      ? Math.round(Number(apiSection.completion_percentage || 0))
      : 0;

    return {
      key: section.key,
      label: t(`va.${section.i18nKey}`, section.label),
      completionPercentage: sectionCompletion,
      pages: section.pages.map((page) => {
        const apiPage = apiPages.find((p: any) => p.page_key === page.key.replace(/-/g, '_'));
        return {
          key: page.key,
          label: t(`va.${page.i18nKey}`, page.label),
          completionPercentage: apiPage ? Math.round(Number(apiPage.completion_percentage || 0)) : 0,
          href: `/participant-dashboard/startups/${startupId}/${page.href}`,
        };
      }),
    };
  });

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
