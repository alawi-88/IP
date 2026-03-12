"use client";

import React from "react";
import TextContentRenderer from "./TextContentRenderer";
import StatCardsRenderer from "./StatCardsRenderer";
import SwotGridRenderer from "./SwotGridRenderer";
import PricingCardsRenderer from "./PricingCardsRenderer";
import ComparisonTableRenderer from "./ComparisonTableRenderer";
import PersonaCardRenderer from "./PersonaCardRenderer";
import JourneyTimelineRenderer from "./JourneyTimelineRenderer";
import ProgressBarsRenderer from "./ProgressBarsRenderer";
import ViabilityScoreRenderer from "./ViabilityScoreRenderer";
import KeyValueRenderer from "./KeyValueRenderer";

interface SectionRendererProps {
  content: any;
  componentType: string;
}

/**
 * Known component-type keys that AI may wrap content under.
 * If content is an object with a single key matching one of these,
 * we unwrap it to get the actual data.
 */
const KNOWN_WRAPPER_KEYS = new Set([
  "stat_cards",
  "text_content",
  "swot_grid",
  "key_value",
  "progress_bars",
  "risk_matrix",
  "risk_assessment",
  "comparison_table",
  "cost_table",
  "financial_projections",
  "viability_score",
  "pestel",
  "canvas_grid",
  "timeline",
  "journey_timeline",
  "development_roadmap",
  "launch_plan",
  "milestones",
  "mvp_roadmap",
  "funnel_chart",
  "line_chart",
  "persona_card",
  "persona_cards",
  "pricing_cards",
  "funding_strategy",
  "growth_channels",
  "partnerships",
  "differentiators",
  "tech_architecture",
  "mvp_definition",
  "competitive_analysis",
  "market_analysis",
  "go_to_market",
  "strategic_frameworks",
  "dashboard",
]);

/**
 * Unwrap content that the AI may have wrapped under a component-type key.
 * For example: { stat_cards: [...] } → [...] or { text_content: { content: "..." } } → { content: "..." }
 *
 * Returns { data, detectedType } where detectedType is the wrapper key if found,
 * allowing the renderer to use the correct component type when AI mismatches occur.
 */
function unwrapContent(
  content: any,
  componentType: string
): { data: any; detectedType: string | null } {
  if (!content || typeof content !== "object" || Array.isArray(content)) {
    return { data: content, detectedType: null };
  }

  const keys = Object.keys(content);

  // If content has a single key matching a known wrapper, unwrap and return detected type
  if (keys.length === 1 && KNOWN_WRAPPER_KEYS.has(keys[0])) {
    return { data: content[keys[0]], detectedType: keys[0] };
  }

  // If content has a key matching the exact componentType
  if (content[componentType] !== undefined) {
    return { data: content[componentType], detectedType: componentType };
  }

  return { data: content, detectedType: null };
}

/**
 * Detect the best renderer based on the actual data shape.
 * This handles cases where AI generates content that doesn't match
 * the admin-configured componentType.
 */
function detectRendererType(data: any): string | null {
  if (!data || typeof data !== "object") return null;

  // Array of items with label/value → stat_cards or progress_bars
  const arr = Array.isArray(data) ? data : null;

  // Check for stat card patterns: array of objects with value + (label|title|name)
  if (arr && arr.length > 0 && arr[0] && ("value" in arr[0] || "price" in arr[0])) {
    if ("price" in arr[0] && "features" in arr[0]) return "pricing_cards";
    return "stat_cards";
  }

  // Check for progress bars: has items/bars/breakdown array with percentage/score/value
  if (data.items || data.bars || data.breakdown || data.stages || data.costs) {
    const items =
      data.items || data.bars || data.breakdown || data.stages || data.costs;
    if (Array.isArray(items) && items.length > 0) {
      const first = items[0];
      if (
        first &&
        ("percentage" in first || "score" in first || "value" in first)
      ) {
        return "progress_bars";
      }
    }
  }

  // Check for comparison table: has headers + rows
  if (data.headers && data.rows) return "comparison_table";

  // Check for pricing cards: has tiers array
  if (data.tiers && Array.isArray(data.tiers)) return "pricing_cards";

  // Check for stat cards: has metrics array
  if (data.metrics && Array.isArray(data.metrics)) return "stat_cards";

  // Check for SWOT grid
  if (data.strengths || data.weaknesses || data.opportunities || data.threats)
    return "swot_grid";

  // Check for timeline: has stages/phases/milestones
  if (data.phases || data.milestones) return "journey_timeline";

  // Check for persona: has name + (role|age|goals)
  if (data.name && (data.role || data.age || data.goals)) return "persona_card";

  // Check for text content with sections
  if (data.content && typeof data.content === "string") return "text_content";
  if (data.sections && Array.isArray(data.sections)) return "text_content";

  // Check for key-value: has items array with key/value
  if (data.items && Array.isArray(data.items) && data.items[0]?.key)
    return "key_value";

  return null;
}

/**
 * SectionRenderer
 * Dynamic renderer that dispatches to specific renderer components based on admin-configured componentType.
 * Maps all 26+ admin component types to the best-fit renderer.
 * Falls back to TextContentRenderer for unknown types.
 *
 * Automatically unwraps content that AI wraps under component-type keys.
 */
export default function SectionRenderer({
  content,
  componentType,
}: SectionRendererProps) {
  // Handle empty content gracefully
  if (!content) {
    return (
      <div
        className="text-gray-400 dark:text-gray-500 text-sm italic py-8"
        style={{ color: "var(--dga-gray-400, #9CA3AF)" }}
      >
        No content generated yet.
      </div>
    );
  }

  // Unwrap content from component-type wrapper keys
  const { data, detectedType } = unwrapContent(content, componentType);

  // If the AI wrapper key differs from componentType, use data-shape detection
  // to find the best renderer. This handles AI mismatches like cost_table content
  // wrapped under progress_bars key.
  const shapeType =
    detectedType && detectedType !== componentType
      ? detectRendererType(data) || detectedType
      : null;

  // Use detected shape type if available, otherwise use admin-configured componentType
  const effectiveType = shapeType || componentType;

  // Dispatch to appropriate renderer based on effective component type
  switch (effectiveType) {
    // ── Data Display ──
    case "stat_cards":
      return <StatCardsRenderer content={data} />;

    case "key_value":
      return <KeyValueRenderer content={data} />;

    case "progress_bars":
      return <ProgressBarsRenderer content={data} />;

    // ── Analysis & Frameworks ──
    case "swot_grid":
      return <SwotGridRenderer content={data} />;

    case "risk_matrix":
    case "risk_assessment":
      return <ComparisonTableRenderer content={data} />;

    case "comparison_table":
    case "cost_table":
    case "financial_projections":
      return <ComparisonTableRenderer content={data} />;

    case "viability_score":
      return <ViabilityScoreRenderer content={data} />;

    case "pestel":
      return <SwotGridRenderer content={data} />;

    case "canvas_grid":
      return <SwotGridRenderer content={data} />;

    // ── Visual & Timeline ──
    case "timeline":
    case "journey_timeline":
    case "development_roadmap":
    case "launch_plan":
    case "milestones":
    case "mvp_roadmap":
      return <JourneyTimelineRenderer content={data} />;

    case "funnel_chart":
      return <ProgressBarsRenderer content={data} />;

    case "line_chart":
      return <StatCardsRenderer content={data} />;

    // ── People & Pricing ──
    case "persona_card":
    case "persona_cards":
      return <PersonaCardRenderer content={data} />;

    case "pricing_cards":
      return <PricingCardsRenderer content={data} />;

    case "funding_strategy":
    case "growth_channels":
    case "partnerships":
    case "differentiators":
      return <StatCardsRenderer content={data} />;

    // ── Technical & Product ──
    case "tech_architecture":
      return <KeyValueRenderer content={data} />;

    case "mvp_definition":
      return <TextContentRenderer content={data} />;

    // ── Strategic & Market ──
    case "competitive_analysis":
    case "market_analysis":
    case "go_to_market":
    case "strategic_frameworks":
    case "dashboard":
      return <TextContentRenderer content={data} />;

    // ── Default ──
    case "text_content":
    default:
      return <TextContentRenderer content={data} />;
  }
}
