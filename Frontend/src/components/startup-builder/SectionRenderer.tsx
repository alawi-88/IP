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
  const { unwrapped, detectedType } = unwrapContent(content, componentType);

  // Use the detected type from unwrapping if available, otherwise use componentType
  const effectiveType = detectedType || componentType;

  // Try content-based detection first (handles mismatched component_type vs actual data)
  const contentRenderer = getContentBasedRenderer(unwrapped, effectiveType);
  if (contentRenderer) return contentRenderer;

  // Fall back to component-type-based rendering
  switch (effectiveType) {
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

/**
 * Content-based renderer detection - inspects the actual data structure
 * to pick the correct renderer regardless of component_type mismatch.
 */
function getContentBasedRenderer(content: any, effectiveType: string) {
  if (!content || typeof content !== "object") return null;

  // Stat cards: has metrics array with label/value objects
  if (content.metrics && Array.isArray(content.metrics) && content.metrics[0]?.label) {
    return <StatCardsRenderer content={content} />;
  }

  // Key-value: has items array with key/value objects
  if (content.items && Array.isArray(content.items) && content.items[0]?.key && content.items[0]?.value) {
    return <KeyValueRenderer content={content} />;
  }

  // Timeline / journey: has stages array with title/actions
  if (content.stages && Array.isArray(content.stages) && content.stages[0]?.title) {
    return <TimelineRenderer content={content} />;
  }

  // SWOT: has strengths/weaknesses/opportunities/threats
  if (content.strengths || content.weaknesses || content.opportunities || content.threats) {
    return <SwotGridRenderer content={content} />;
  }

  // Risk matrix: has risks array with severity
  if (content.risks && Array.isArray(content.risks) && content.risks[0]?.severity) {
    return <RiskMatrixRenderer content={content} />;
  }

  // Funnel chart: has stages with value/percentage
  if (content.stages && Array.isArray(content.stages) && content.stages[0]?.value) {
    return <FunnelChartRenderer content={content} />;
  }

  // Comparison table: has headers and rows
  if (content.headers && content.rows) {
    return <ComparisonTableRenderer content={content} />;
  }

  // Persona: single persona object with name/role/goals
  if (content.name && content.role && (content.goals || content.pain_points)) {
    return <PersonaCardsRenderer content={content} />;
  }

  // Viability score: has score and breakdown
  if (content.score !== undefined && content.breakdown) {
    return <ViabilityScoreRenderer content={content} />;
  }

  // Pricing: has tiers or pricing_tiers
  if (content.tiers || content.pricing_tiers) {
    return <PricingCardsRenderer content={content} />;
  }

  // Funding: has rounds or funding_rounds
  if (content.rounds || content.funding_rounds) {
    return <FundingStrategyRenderer content={content} />;
  }

  // Development roadmap: has phases with timeline
  if (content.phases && Array.isArray(content.phases) && content.phases[0]?.timeline) {
    return <DevelopmentRoadmapRenderer content={content} />;
  }

  // Launch plan: has phases with tasks/activities
  if (content.phases && Array.isArray(content.phases) && (content.phases[0]?.tasks || content.phases[0]?.activities)) {
    return <LaunchPlanRenderer content={content} />;
  }

  // Milestones: has projections or revenue_milestones
  if (content.projections || content.revenue_milestones) {
    return <MilestonesRenderer content={content} />;
  }

  // Growth channels: has channels or growth_channels
  if (content.channels || content.growth_channels) {
    return <GrowthChannelsRenderer content={content} />;
  }

  // Tech architecture: has technology_stack or frontend/backend keys
  if (content.technology_stack || (content.frontend && content.backend)) {
    return <TechArchitectureRenderer content={content} />;
  }

  // PESTEL: has categories or political/economic keys
  if (content.categories || content.political) {
    return <PestelRenderer content={content} />;
  }

  // Differentiators: has differentiators array
  if (content.differentiators && Array.isArray(content.differentiators)) {
    return <DifferentiatorsRenderer content={content} />;
  }

  // MVP definition: has features or core_concept
  if (content.features && (content.core_concept || content.success_criteria || content.must_have_features)) {
    return <MvpDefinitionRenderer content={content} />;
  }

  // Partnerships: has partnerships array
  if (content.partnerships && Array.isArray(content.partnerships)) {
    return <PartnershipsRenderer content={content} />;
  }

  // Progress bars: has items with percentage
  if (content.items && Array.isArray(content.items) && content.items[0]?.percentage !== undefined) {
    return <ProgressBarsRenderer content={content} />;
  }

  return null;
}

/**
 * Unwrap nested content from seeder format.
 * Returns { unwrapped, detectedType } where detectedType is the key that was used for unwrapping.
 */
function unwrapContent(content: any, componentType: string): { unwrapped: any; detectedType: string | null } {
  if (!content || typeof content !== "object") return { unwrapped: content, detectedType: null };

  // Common nested keys from seeder - order matters (check componentType first)
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
    "persona_card",  // singular form
    "journey_timeline",
    "timeline",
    "pricing_cards",
    "pricing_table",
    "viability_score",
    "canvas_grid",
    "text_content",
    "cost_table",
    "line_chart",
  ];

  for (const key of typeKeys) {
    if (content[key] && typeof content[key] === "object") {
      return { unwrapped: content[key], detectedType: key === componentType ? null : key };
    }
  }

  return { unwrapped: content, detectedType: null };
}
