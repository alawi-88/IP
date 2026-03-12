// Venture helper utilities — all section/tab metadata is driven by API (admin-configured)

import type { VentureSection, VentureTab } from '@/lib/interfaces/venture';

/**
 * Resolve bilingual content based on locale
 */
export function resolveBilingual(
  content: Record<string, any> | null,
  contentAr: Record<string, any> | null,
  locale: string
): Record<string, any> | null {
  if (locale === 'ar' && contentAr && Object.keys(contentAr).length > 0) {
    return contentAr;
  }
  return content;
}

/**
 * Resolve bilingual label from any object with label_en/label_ar
 */
export function resolveLabel(item: { label_en: string; label_ar: string }, locale: string): string {
  return locale === 'ar' ? item.label_ar : item.label_en;
}

/**
 * Get status color
 */
export function getStatusColor(status: string): string {
  const map: Record<string, string> = {
    completed: '#22c55e',
    generating: '#f59e0b',
    failed: '#ef4444',
    pending: '#9ca3af',
    draft: '#6b7280',
    archived: '#64748b',
  };
  return map[status] ?? '#9ca3af';
}

/**
 * Get status badge config
 */
export function getStatusBadge(status: string): { bg: string; text: string; label: string } {
  const map: Record<string, { bg: string; text: string; label: string }> = {
    completed: { bg: '#dcfce7', text: '#166534', label: 'venture.completed' },
    generating: { bg: '#fef3c7', text: '#92400e', label: 'venture.generating-status' },
    failed: { bg: '#fee2e2', text: '#991b1b', label: 'venture.failed' },
    draft: { bg: '#f3f4f6', text: '#374151', label: 'venture.draft' },
    archived: { bg: '#e2e8f0', text: '#475569', label: 'venture.archived' },
  };
  return map[status] ?? { bg: '#fef3c7', text: '#92400e', label: 'venture.partially-completed' };
}

/**
 * Get section icon from admin-configured display_config (falls back to section slug)
 */
export function getSectionIcon(section: VentureSection): string {
  return section.display_config?.icon ?? '📄';
}

/**
 * Get section color from admin-configured display_config
 */
export function getSectionColor(section: VentureSection): string {
  return section.display_config?.color ?? '#6b7280';
}

/**
 * Get section component type from admin-configured display_config
 */
export function getSectionComponentType(section: VentureSection): string {
  return section.display_config?.component_type ?? section.component_type ?? 'text_content';
}

/**
 * Flatten section content to plain text for copy
 */
export function flattenContentToText(content: Record<string, any> | null): string {
  if (!content) return '';
  const lines: string[] = [];

  function walk(obj: any) {
    if (typeof obj === 'string') {
      lines.push(obj);
    } else if (Array.isArray(obj)) {
      obj.forEach((item) => {
        if (typeof item === 'string') lines.push(`• ${item}`);
        else if (typeof item === 'object' && item !== null) walk(item);
      });
    } else if (typeof obj === 'object' && obj !== null) {
      for (const [key, value] of Object.entries(obj)) {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
        if (typeof value === 'string' || typeof value === 'number') {
          lines.push(`${label}: ${value}`);
        } else {
          lines.push(`\n${label}:`);
          walk(value);
        }
      }
    }
  }
  walk(content);
  return lines.join('\n');
}

/**
 * Get tab completion dot color (for tab nav pills)
 */
export function getTabDotColor(tab: VentureTab): string {
  const sections = tab.sections ?? [];
  if (sections.length === 0) return '#9ca3af';
  const allDone = sections.every((s) => s.status === 'completed');
  const anyFailed = sections.some((s) => s.status === 'failed');
  const anyGenerating = sections.some((s) => s.status === 'generating');
  if (allDone) return '#22c55e';
  if (anyFailed) return '#ef4444';
  if (anyGenerating) return '#f59e0b';
  return '#9ca3af';
}

/**
 * Industry / Market / Model options for create form
 * These are generic dropdown values; admin can extend via prompts.
 */
export const INDUSTRY_OPTIONS = [
  'Technology', 'Healthcare', 'Education', 'Finance', 'E-Commerce',
  'Food & Beverage', 'Real Estate', 'Transportation', 'Entertainment',
  'Agriculture', 'Energy', 'Manufacturing', 'Tourism', 'Sports',
  'Social Impact', 'Other',
];

export const TARGET_MARKET_OPTIONS = [
  'B2C - Consumers', 'B2B - Businesses', 'B2B2C - Platform',
  'B2G - Government', 'C2C - Peer-to-Peer', 'Local Market',
  'Regional (GCC)', 'Global Market',
];

export const BUSINESS_MODEL_OPTIONS = [
  'SaaS', 'Marketplace', 'Subscription', 'Freemium',
  'E-Commerce', 'Ad-Supported', 'Licensing', 'Hardware + Software',
  'Service-Based', 'Commission-Based', 'Other',
];
