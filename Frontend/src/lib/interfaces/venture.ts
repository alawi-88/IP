// Venture TypeScript interfaces

export interface VentureSection {
  id: number;
  venture_id: number;
  venture_tab_id: number;
  slug: string;
  label_en: string;
  label_ar: string;
  content: Record<string, any> | null;
  content_ar: Record<string, any> | null;
  status: 'pending' | 'generating' | 'completed' | 'failed';
  error_message: string | null;
  sort_order: number;
  is_visible: boolean;
  component_type: string;
  tokens_used: number | null;
  estimated_cost: string | null;
  generated_at: string | null;
  display_config?: VentureSectionConfig;
}

export interface VentureTab {
  id: number;
  venture_id: number;
  slug: string;
  label_en: string;
  label_ar: string;
  icon: string | null;
  sort_order: number;
  is_visible: boolean;
  sections: VentureSection[];
  completion_status?: {
    total: number;
    completed: number;
    failed: number;
    generating: number;
    pending: number;
    is_complete: boolean;
  };
}

export interface Venture {
  id: number;
  title: string;
  idea_prompt: string;
  status: 'draft' | 'generating' | 'completed' | 'failed' | 'archived';
  viability_score: number | null;
  viability_breakdown: Record<string, any> | null;
  industry: string | null;
  target_market: string | null;
  business_model: string | null;
  sections_total: number;
  sections_completed: number;
  sections_failed: number;
  created_at: string;
  updated_at: string;
  generation_started_at: string | null;
  generation_completed_at: string | null;
  is_archived: boolean;
  progress_percentage: number;
  tabs?: VentureTab[];
}

export interface VentureSectionConfig {
  section_slug: string;
  tab_slug: string;
  label_en: string;
  label_ar: string;
  icon: string;
  color: string;
  component_type: string;
  sort_order: number;
  is_visible: boolean;
  metadata: Record<string, any> | null;
}

export interface VentureProgress {
  venture_id: number;
  status: string;
  sections_total: number;
  sections_completed: number;
  sections_failed: number;
  progress_percentage: number;
  tabs: Array<{
    slug: string;
    label_en: string;
    label_ar: string;
    total: number;
    completed: number;
    failed: number;
    generating: number;
    pending: number;
    is_complete: boolean;
  }>;
}

export interface CreateVenturePayload {
  title: string;
  idea_prompt: string;
  industry?: string;
  target_market?: string;
  business_model?: string;
}
