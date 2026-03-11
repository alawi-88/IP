"use client";

import TextContentRenderer from "./renderers/TextContentRenderer";
import StatCardsRenderer from "./renderers/StatCardsRenderer";
import SwotGridRenderer from "./renderers/SwotGridRenderer";
import ComparisonTableRenderer from "./renderers/ComparisonTableRenderer";
import RiskMatrixRenderer from "./renderers/RiskMatrixRenderer";
import TimelineRenderer from "./renderers/TimelineRenderer";
import KeyValueRenderer from "./renderers/KeyValueRenderer";
import ProgressBarsRenderer from "./renderers/ProgressBarsRenderer";
import PersonaCardsRenderer from "./renderers/PersonaCardsRenderer";
import ViabilityScoreRenderer from "./renderers/ViabilityScoreRenderer";
import FunnelChartRenderer from "./renderers/FunnelChartRenderer";
import PricingCardsRenderer from "./renderers/PricingCardsRenderer";
import FundingStrategyRenderer from "./renderers/FundingStrategyRenderer";
import MilestonesRenderer from "./renderers/MilestonesRenderer";
import GrowthChannelsRenderer from "./renderers/GrowthChannelsRenderer";
import LaunchPlanRenderer from "./renderers/LaunchPlanRenderer";
import PartnershipsRenderer from "./renderers/PartnershipsRenderer";
import PestelRenderer from "./renderers/PestelRenderer";
import DifferentiatorsRenderer from "./renderers/DifferentiatorsRenderer";
import MvpDefinitionRenderer from "./renderers/MvpDefinitionRenderer";
import TechArchitectureRenderer from "./renderers/TechArchitectureRenderer";
import DevelopmentRoadmapRenderer from "./renderers/DevelopmentRoadmapRenderer";

interface SectionRendererProps {
  componentType: string;
  content: any;
  slug: string;
}

export default function SectionRenderer({
  componentType,
  content,
  slug,
}: SectionRendererProps) {
  // Unwrap nested content (seeder wraps data under component-type keys)
  const unwrapped = unwrapContent(content, componentType);

  // Use slug-based rendering for specific sections that need custom layouts
  const slugRenderer = getSlugRenderer(slug, unwrapped);
  if (slugRenderer) return slugRenderer;

  // Fall back to component-type-based rendering
  switch (componentType) {
    case "viability_score":
      return <ViabilityScoreRenderer content={unwrapped} />;
    case "text_content":
      return <TextContentRenderer content={unwrapped} />;
    case "stat_cards":
      return <StatCardsRenderer content={unwrapped} />;
    case "swot_grid":
      return <SwotGridRenderer content={unwrapped} />;
    case "canvas_grid":
      if (unwrapped.comparison_table || unwrapped.headers)
        return <ComparisonTableRenderer content={unwrapped} />;
      return <KeyValueRenderer content={unwrapped} />;
    case "funnel_chart":
      return <FunnelChartRenderer content={unwrapped} />;
    case "pricing_table":
    case "pricing_cards":
      return <PricingCardsRenderer content={unwrapped} />;
    case "cost_table":
      if (unwrapped.progress_bars || unwrapped.items)
        return <ProgressBarsRenderer content={unwrapped} />;
      return <ProgressBarsRenderer content={unwrapped} />;
    case "line_chart":
      return <StatCardsRenderer content={unwrapped} />;
    case "comparison_table":
      return <ComparisonTableRenderer content={unwrapped} />;
    case "timeline":
    case "journey_timeline":
      return <TimelineRenderer content={unwrapped} />;
    case "risk_matrix":
      return <RiskMatrixRenderer content={unwrapped} />;
    case "progress_bars":
      return <ProgressBarsRenderer content={unwrapped} />;
    case "persona_cards":
      return <PersonaCardsRenderer content={unwrapped} />;
    case "key_value":
      return <KeyValueRenderer content={unwrapped} />;
    default:
      return <TextContentRenderer content={unwrapped} />;
  }
}

function getSlugRenderer(slug: string, content: any) {
  switch (slug) {
    case "pestel-analysis":
      if (content.categories || content.political)
        return <PestelRenderer content={content} />;
      return null;
    case "key-differentiators":
      if (content.differentiators || content.items || Array.isArray(content))
        return <DifferentiatorsRenderer content={content} />;
      return null;
    case "mvp-definition":
      if (content.features || content.core_concept || content.must_have_features)
        return <MvpDefinitionRenderer content={content} />;
      return null;
    case "technical-architecture":
      if (content.technology_stack || content.frontend || content.backend)
        return <TechArchitectureRenderer content={content} />;
      return null;
    case "development-roadmap":
      if (content.phases || content.milestones)
        return <DevelopmentRoadmapRenderer content={content} />;
      return null;
    case "revenue-model":
      if (content.business_model || content.pricing_tiers)
        return <PricingCardsRenderer content={content} />;
      return null;
    case "financial-projections":
      if (content.projections || content.milestones || content.revenue_milestones)
        return <MilestonesRenderer content={content} />;
      return null;
    case "cost-structure":
      return <ProgressBarsRenderer content={content} />;
    case "funding-strategy":
      if (content.rounds || content.stages || content.funding_rounds)
        return <FundingStrategyRenderer content={content} />;
      return null;
    case "go-to-market-strategy":
      if (content.channels || content.growth_channels || content.strategy_summary)
        return <GrowthChannelsRenderer content={content} />;
      return null;
    case "launch-plan":
      if (content.phases)
        return <LaunchPlanRenderer content={content} />;
      return null;
    case "key-partnerships":
      if (content.partnerships || content.items || Array.isArray(content))
        return <PartnershipsRenderer content={content} />;
      return null;
    case "buyer-journey":
      return <TimelineRenderer content={content} />;
    default:
      return null;
  }
}

function unwrapContent(content: any, componentType: string): any {
  if (!content || typeof content !== "object") return content;

  // Common nested keys from seeder
  const typeKeys = [
    componentType,
    "stat_cards",
    "swot_grid",
    "comparison_table",
    "risk_matrix",
    "progress_bars",
    "key_value",
    "funnel_chart",
    "persona_cards",
    "journey_timeline",
    "timeline",
    "pricing_cards",
    "viability_score",
    "canvas_grid",
    "text_content",
  ];

  for (const key of typeKeys) {
    if (content[key] && typeof content[key] === "object") {
      return content[key];
    }
  }

  return content;
}
