"use client";

import axiosInstance from "@/axios";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useLocale } from "next-intl";
import { useParams, useRouter } from "next/navigation";
import { useState, useMemo } from "react";
import { Spin, message, Tooltip } from "antd";
import { Link } from "@/i18n/routing";
import {
  FiArrowLeft,
  FiRefreshCw,
  FiArchive,
  FiEdit2,
  FiCopy,
  FiChevronLeft,
  FiChevronRight,
  FiAlertCircle,
  FiEye,
  FiEyeOff,
} from "react-icons/fi";
import { HiOutlineSparkles } from "react-icons/hi2";
import SectionRenderer from "@/components/startup-builder/SectionRenderer";
import SectionEditModal from "@/components/startup-builder/SectionEditModal";
import AiRegenerateModal from "@/components/startup-builder/AiRegenerateModal";

export default function VentureDetailPage() {
  const { id } = useParams();
  const locale = useLocale();
  const router = useRouter();
  const queryClient = useQueryClient();
  const [activeTabIndex, setActiveTabIndex] = useState(0);
  const [showHidden, setShowHidden] = useState(false);

  // Edit modal state
  const [editingSection, setEditingSection] = useState<any>(null);
  const [editModalOpen, setEditModalOpen] = useState(false);

  // AI regenerate modal state
  const [regeneratingSection, setRegeneratingSection] = useState<any>(null);
  const [aiModalOpen, setAiModalOpen] = useState(false);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["venture", id, locale],
    queryFn: async () => {
      const res = await axiosInstance.get(`/participants/ventures/${id}`);
      return res.data;
    },
    refetchOnWindowFocus: false,
  });

  const refreshMutation = useMutation({
    mutationFn: async () => {
      return axiosInstance.post(`/participants/ventures/${id}/retry-failed`);
    },
    onSuccess: () => {
      message.success("Retrying failed sections...");
      queryClient.invalidateQueries({ queryKey: ["venture", id] });
    },
  });

  const archiveMutation = useMutation({
    mutationFn: async () => {
      return axiosInstance.post(
        `/participants/ventures/${id}/toggle-archive`
      );
    },
    onSuccess: () => {
      message.success("Archive status updated");
      queryClient.invalidateQueries({ queryKey: ["venture", id] });
    },
  });

  const toggleVisibilityMutation = useMutation({
    mutationFn: async (sectionId: number) => {
      return axiosInstance.post(
        `/participants/ventures/${id}/sections/${sectionId}/toggle-visibility`
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["venture", id] });
    },
  });

  const venture = data?.data;
  const tabs = venture?.tabs || [];
  const activeTab = tabs[activeTabIndex];
  const allSections = activeTab?.sections || [];

  // Filter sections based on visibility
  const sections = showHidden
    ? allSections
    : allSections.filter((s: any) => s.is_visible !== false);

  const hiddenCount = allSections.filter(
    (s: any) => s.is_visible === false
  ).length;

  const failedCount = useMemo(() => {
    if (!tabs.length) return 0;
    return tabs.reduce(
      (acc: number, tab: any) =>
        acc +
        (tab.sections?.filter((s: any) => s.status === "failed").length || 0),
      0
    );
  }, [tabs]);

  const statusLabel =
    venture?.status === "completed" && failedCount === 0
      ? "Completed"
      : failedCount > 0
        ? "Partially Completed"
        : venture?.status === "generating"
          ? "Generating..."
          : venture?.status || "Draft";

  const statusClass =
    venture?.status === "completed" && failedCount === 0
      ? "bg-green-600 text-white"
      : failedCount > 0
        ? "bg-yellow-500 text-white"
        : venture?.status === "generating"
          ? "bg-blue-500 text-white"
          : "bg-gray-400 text-white";

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <Spin size="large" />
      </div>
    );
  }

  if (isError || !venture) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <p className="text-gray-500">Failed to load venture</p>
      </div>
    );
  }

  const copyContent = (section: any) => {
    const text = JSON.stringify(section.content, null, 2);
    navigator.clipboard.writeText(text);
    message.success("Content copied to clipboard");
  };

  const openEditModal = (section: any) => {
    setEditingSection(section);
    setEditModalOpen(true);
  };

  const openAiModal = (section: any) => {
    setRegeneratingSection(section);
    setAiModalOpen(true);
  };

  const handleToggleVisibility = (section: any) => {
    toggleVisibilityMutation.mutate(section.id);
    const willBeHidden = section.is_visible !== false;
    message.success(
      willBeHidden ? "Section hidden" : "Section is now visible"
    );
  };

  // Tab icon map based on slug
  const tabIcons: Record<string, string> = {
    dashboard: "📊",
    "strategic-frameworks": "🔮",
    "market-analysis": "📈",
    "financial-projections": "💰",
    "mvp-roadmap": "🚀",
    "path-to-mvp": "🚀",
    "risk-assessment": "⚠️",
    "go-to-market": "📣",
    "go-to-market-strategy": "📣",
    "competitive-analysis": "📊",
    overview: "📋",
    "business-model": "💼",
    "marketing-strategy": "📢",
    "operations-plan": "⚙️",
    "implementation-roadmap": "🗺️",
    "unique-selling-points": "💎",
    "customer-persona": "👤",
    finances: "💵",
  };

  return (
    <div className="flex flex-col gap-y-6 -m-4 lg:-m-10">
      {/* Hero Banner */}
      <div className="bg-gradient-to-br from-[#25935F] via-[#1f8753] to-[#196e44] px-6 lg:px-10 py-8 relative overflow-hidden">
        {/* Decorative shapes */}
        <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2" />
        <div className="absolute bottom-0 left-1/3 w-48 h-48 bg-white/5 rounded-full translate-y-1/2" />

        <div className="relative z-10">
          {/* Top bar */}
          <div className="flex items-center justify-between mb-4">
            <Link
              href="/participant-dashboard/startup-builder"
              className="flex items-center gap-1 text-white/80 hover:text-white text-sm transition-colors"
            >
              <FiArrowLeft size={16} />
              <span>Back</span>
            </Link>
            <div className="flex items-center gap-2">
              <button
                onClick={() => refreshMutation.mutate()}
                disabled={refreshMutation.isPending}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-sm transition-colors backdrop-blur-sm"
              >
                <FiRefreshCw
                  size={14}
                  className={refreshMutation.isPending ? "animate-spin" : ""}
                />
                Refresh
              </button>
              <button
                onClick={() => archiveMutation.mutate()}
                disabled={archiveMutation.isPending}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-sm transition-colors backdrop-blur-sm"
              >
                <FiArchive size={14} />
                Archive
              </button>
            </div>
          </div>

          {/* Title and viability */}
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h1 className="text-2xl lg:text-3xl font-bold text-white mb-2">
                {venture.title}
              </h1>
              <span
                className={`inline-block px-3 py-1 rounded-full text-xs font-medium ${statusClass}`}
              >
                {statusLabel}
              </span>
              <p className="text-white/70 text-sm mt-3 max-w-2xl">
                {venture.idea_prompt}
              </p>
            </div>

            {/* Viability Score Circle */}
            {venture.viability_score != null && (
              <div className="hidden md:flex flex-col items-center ml-6">
                <p className="text-white/60 text-xs uppercase tracking-wider mb-2">
                  Viability Score
                </p>
                <div className="relative w-24 h-24">
                  <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90">
                    <circle
                      cx="50"
                      cy="50"
                      r="42"
                      fill="none"
                      stroke="rgba(255,255,255,0.15)"
                      strokeWidth="8"
                    />
                    <circle
                      cx="50"
                      cy="50"
                      r="42"
                      fill="none"
                      stroke="white"
                      strokeWidth="8"
                      strokeLinecap="round"
                      strokeDasharray={`${(venture.viability_score / 100) * 264} 264`}
                    />
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <span className="text-white text-2xl font-bold">
                      {venture.viability_score}%
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Failed sections warning */}
      {failedCount > 0 && (
        <div className="mx-6 lg:mx-10 flex items-start gap-3 px-4 py-3 rounded-lg bg-blue-50 border border-blue-200">
          <FiAlertCircle className="text-blue-500 flex-shrink-0 mt-0.5" size={18} />
          <div>
            <p className="text-sm font-medium text-blue-800">
              {failedCount} section(s) failed to generate
            </p>
            <p className="text-xs text-blue-600 mt-0.5">
              You can retry failed sections by clicking the &apos;Retry&apos; button on each section card.
            </p>
          </div>
        </div>
      )}

      {/* Tab Title + Navigation */}
      <div className="px-6 lg:px-10">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl lg:text-2xl font-bold text-[#25935F]">
            {activeTab?.label_en || activeTab?.slug?.replace(/-/g, " ")}
          </h2>
          <div className="flex items-center gap-3 text-sm text-gray-500">
            {/* Show/hide hidden sections toggle */}
            {hiddenCount > 0 && (
              <button
                onClick={() => setShowHidden(!showHidden)}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
                  showHidden
                    ? "bg-gray-200 text-gray-700"
                    : "bg-gray-100 text-gray-500 hover:bg-gray-200"
                }`}
              >
                {showHidden ? <FiEye size={14} /> : <FiEyeOff size={14} />}
                {showHidden ? "Showing all" : `${hiddenCount} hidden`}
              </button>
            )}
            <button
              onClick={() => setActiveTabIndex(Math.max(0, activeTabIndex - 1))}
              disabled={activeTabIndex === 0}
              className="flex items-center gap-1 hover:text-[#25935F] disabled:opacity-30 transition-colors"
            >
              <FiChevronLeft size={16} />
              Previous section
            </button>
            <button
              onClick={() =>
                setActiveTabIndex(Math.min(tabs.length - 1, activeTabIndex + 1))
              }
              disabled={activeTabIndex === tabs.length - 1}
              className="flex items-center gap-1 hover:text-[#25935F] disabled:opacity-30 transition-colors"
            >
              Next section
              <FiChevronRight size={16} />
            </button>
          </div>
        </div>

        {/* Tab Pills */}
        <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
          {tabs.map((tab: any, index: number) => {
            const isActive = index === activeTabIndex;
            const hasFailedSections = tab.sections?.some(
              (s: any) => s.status === "failed"
            );
            const allCompleted = tab.sections?.every(
              (s: any) => s.status === "completed"
            );
            const dotColor = hasFailedSections
              ? "bg-red-400"
              : allCompleted
                ? "bg-green-400"
                : "bg-yellow-400";

            return (
              <button
                key={tab.id}
                onClick={() => setActiveTabIndex(index)}
                className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all ${
                  isActive
                    ? "bg-[#25935F] text-white shadow-sm"
                    : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                }`}
              >
                <span className="text-base">
                  {tabIcons[tab.slug] || "📄"}
                </span>
                {tab.label_en || tab.slug?.replace(/-/g, " ")}
                <span
                  className={`w-2 h-2 rounded-full ${isActive ? "bg-white" : dotColor}`}
                />
              </button>
            );
          })}
        </div>
      </div>

      {/* Sections */}
      <div className="flex flex-col gap-6 px-6 lg:px-10 pb-10">
        {sections.length === 0 && (
          <div className="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
            {hiddenCount > 0
              ? `All sections are hidden. Click "Show hidden" to reveal them.`
              : "No sections in this tab"}
          </div>
        )}
        {sections.map((section: any) => {
          const isFailed = section.status === "failed";
          const isCompleted = section.status === "completed";
          const isHidden = section.is_visible === false;

          return (
            <div
              key={section.id}
              className={`bg-white rounded-xl shadow-sm border overflow-hidden transition-all ${
                isHidden
                  ? "border-dashed border-gray-300 opacity-60"
                  : "border-gray-100"
              }`}
            >
              {/* Section Header */}
              <div className="flex items-center justify-between px-6 py-4 border-b border-gray-50">
                <div className="flex items-center gap-3">
                  <span className="text-xl">
                    {getSectionIcon(section.component_type, section.slug)}
                  </span>
                  <h3 className="text-base font-semibold text-gray-800">
                    {section.label_en ||
                      section.slug
                        ?.replace(/-/g, " ")
                        .replace(/_/g, " ")
                        .replace(/\b\w/g, (c: string) => c.toUpperCase())}
                  </h3>
                  {isCompleted && (
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                      <span className="w-1.5 h-1.5 rounded-full bg-green-500" />
                      Completed
                    </span>
                  )}
                  {isFailed && (
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                      <span className="w-1.5 h-1.5 rounded-full bg-red-500" />
                      Failed
                    </span>
                  )}
                  {section.status === "generating" && (
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                      <span className="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" />
                      Generating...
                    </span>
                  )}
                  {isHidden && (
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">
                      <FiEyeOff size={10} />
                      Hidden
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-1">
                  <Tooltip title="Edit Section">
                    <button
                      onClick={() => openEditModal(section)}
                      className="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-[#25935F] transition-colors"
                    >
                      <FiEdit2 size={16} />
                    </button>
                  </Tooltip>
                  <Tooltip title="Regenerate with AI">
                    <button
                      onClick={() => openAiModal(section)}
                      className="p-2 rounded-lg hover:bg-purple-50 text-gray-400 hover:text-purple-600 transition-colors"
                    >
                      <HiOutlineSparkles size={16} />
                    </button>
                  </Tooltip>
                  <Tooltip title="Copy Content">
                    <button
                      onClick={() => copyContent(section)}
                      className="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      <FiCopy size={16} />
                    </button>
                  </Tooltip>
                  <Tooltip title={isHidden ? "Show Section" : "Hide Section"}>
                    <button
                      onClick={() => handleToggleVisibility(section)}
                      className={`p-2 rounded-lg transition-colors ${
                        isHidden
                          ? "hover:bg-green-50 text-gray-400 hover:text-green-600"
                          : "hover:bg-gray-100 text-gray-400 hover:text-gray-600"
                      }`}
                    >
                      {isHidden ? (
                        <FiEye size={16} />
                      ) : (
                        <FiEyeOff size={16} />
                      )}
                    </button>
                  </Tooltip>
                </div>
              </div>

              {/* Section Content */}
              <div className="px-6 py-5">
                {isFailed ? (
                  <div className="flex items-start gap-3 px-4 py-3 rounded-lg bg-orange-50 border border-orange-200">
                    <FiAlertCircle
                      className="text-orange-500 flex-shrink-0 mt-0.5"
                      size={18}
                    />
                    <div>
                      <p className="text-sm font-medium text-orange-800">
                        This section failed to generate
                      </p>
                      <p className="text-xs text-orange-600 mt-0.5">
                        Click the retry button above to regenerate this section.
                      </p>
                    </div>
                  </div>
                ) : section.content ? (
                  <SectionRenderer
                    componentType={section.component_type}
                    content={section.content}
                    slug={section.slug}
                  />
                ) : (
                  <div className="text-gray-400 text-sm text-center py-4">
                    No content available
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Edit Modal */}
      {editingSection && (
        <SectionEditModal
          open={editModalOpen}
          onClose={() => {
            setEditModalOpen(false);
            setEditingSection(null);
          }}
          section={editingSection}
          ventureId={id as string}
          onSaved={() => {
            queryClient.invalidateQueries({ queryKey: ["venture", id] });
          }}
        />
      )}

      {/* AI Regenerate Modal */}
      {regeneratingSection && (
        <AiRegenerateModal
          open={aiModalOpen}
          onClose={() => {
            setAiModalOpen(false);
            setRegeneratingSection(null);
          }}
          section={regeneratingSection}
          ventureId={id as string}
          onRegenerated={() => {
            queryClient.invalidateQueries({ queryKey: ["venture", id] });
          }}
        />
      )}
    </div>
  );
}

function getSectionIcon(componentType: string, slug: string): string {
  const iconMap: Record<string, string> = {
    viability_score: "📊",
    text_content: "📝",
    stat_cards: "📈",
    swot_grid: "🎯",
    canvas_grid: "🧩",
    funnel_chart: "📊",
    pricing_table: "💰",
    pricing_cards: "💳",
    cost_table: "💵",
    line_chart: "📉",
    comparison_table: "📊",
    timeline: "📅",
    journey_timeline: "🗺️",
    risk_matrix: "⚠️",
    progress_bars: "📊",
    persona_cards: "👤",
    key_value: "🔑",
  };

  const slugMap: Record<string, string> = {
    about: "📋",
    "mission-vision": "🎯",
    problem: "❓",
    solution: "💡",
    "swot-analysis": "🎯",
    "pestel-analysis": "🌍",
    "porters-five-forces": "⚡",
    "go-no-go": "✅",
    "viability-score": "📊",
    "market-size": "📈",
    "industry-insight": "🔍",
    "ip-strategy": "🛡️",
    "key-differentiators": "⭐",
    "competitive-comparison": "📊",
    "mvp-definition": "🎯",
    "technical-architecture": "🏗️",
    "development-roadmap": "🗺️",
    "key-risks": "⚠️",
    "revenue-model": "💰",
    "financial-projections": "📈",
    "cost-structure": "💵",
    "funding-strategy": "🏦",
    "key-financial-metrics": "📊",
    "primary-persona": "👩",
    "secondary-persona": "👨",
    "buyer-journey": "🛒",
    "go-to-market-strategy": "📣",
    "launch-plan": "🚀",
    "key-partnerships": "🤝",
    "growth-channels": "📢",
    "usp-overview": "💎",
  };

  return slugMap[slug] || iconMap[componentType] || "📄";
}
