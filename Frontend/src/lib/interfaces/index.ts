// program
export interface Program {
  data: Competition[];
  programs_types: any[];
}

// stage
export interface Stage {
  id: number;
  slug: string;
  title: string;
  description: string;
  starts_at: string | null;
  ends_at: string | null;
  forms: {
    id: number;
    name: string;
  }[];
  projects: {
    id: number;
    isSubmitted: boolean;
  }[];
  evaluation?: {
    project_id: number[];
    isSubmitted: boolean;
    isDisclaimerAccepted: boolean | null;
  };
  form_id: number[];
  project: {
    project_id: number[];
    isSubmitted: boolean;
  };
}

// Competition
export interface Competition {
  id: number;
  title: string;
  type?: {
    title: string;
    slug: string;
  };
  about: string;
  terms_and_conditions: string;
  registration_closed_date: string | null;
  banner: string;
  is_closed: boolean | null;
  is_published: boolean;
  current_stage: Stage | undefined;
  stages: Stage[];
  hub?: {
    id: number;
    competition_id: number;
    tab: string;
    is_visible: boolean;
  }[];
  tracks: {
    id: number;
    name: string;
    order: number;
    is_selected: boolean;
    sub_tracks: {
      id: number;
      name: string;
      order: number;
      is_selected?: boolean;
    }[];
  }[];
  branding?: Theme & {
    is_published?: boolean;
  };
  selectedTrackId: string;
  selectedSubTrackId: string;
  current_stage_id?: number | null;
  current_stage_slug: string | null;
}

// DynamicForm
export interface DynamicForm {
  application_id: string;
  id: number;
  type: string;
  ai_enhancement: any;
  submit_type: string;
  name: string;
  description: string;
  status?: string | null;
  is_published: boolean;
  created_at: string;
  updated_at: string;
  competition?: Competition;
  fields: Field[];
  steps: any[];
  team?: {
    register_as?: string;
    team_name: string;
    team_logo: string;
    team_serial: string;
  };
  metadata?: any;
  evaluation_config?: EvaluationConfig;
}

// MyProgram
export interface MyProgram {
  data: MyCompetition[];
  programs_types: any[];
}

// MyCompetition
export interface MyCompetition {
  id: number;
  status: string;
  submit_type: string;
  created_at: string;
  is_evaluated?: boolean;
  participant_id?: number;
  participant_name?: string;
  competition: Competition;
  form_id: number;
  form: DynamicForm;
  team_metadata?: {
    register_as?: string;
    team_name: string;
    team_logo: string;
    team_serial: string;
  };
  metadata?: any;
  team?: Team;
  has_comment?: boolean;

  // old
  has_team?: boolean;
  has_idea?: boolean;
  idea_description?: string;
  path?: unknown;
  challenge?: unknown;
  participation_interest?: string;
  current_stage_slug?: string;
  current_stage?: string;
}

export interface Team {
  id: number;
  name: string;
  team_logo: string;
  logo: string;
  members: {
    id: number;
    application_id: number;
    participant_id: number;
    is_leader: number;
    created_at: string;
    updated_at: string;
    participant: {
      id: number;
      serial_number: string;
      name: string;
      email: string;
      email_verified_at: string;
      phone: string;
      gender: string;
      date_of_birth: string;
      nationality: string;
      country: string;
      residence_city: string;
      educational_background: string;
      current_role: string;
      place_of_work_study: string;
      years_of_experience: string;
      experience_or_skills: string;
      key_achievements: string;
      password_reset_code: null;
      last_login_at: null;
      created_at: string;
      updated_at: string;
    };
  }[];
  track: {
    id: number;
    name: string;
    order: number;
  };
  sub_track: {
    id: number;
    name: string;
    order: number;
  };
  is_participant_leader?: boolean;
  idea_description: string;
  previous_participation: boolean;
  skills?: [];
  contact_email: string;
  is_completed: boolean;
  is_published: boolean;
}

interface EvaluationConfig {
  total_score: number;
  rounding_rule: string;
  evaluation_criteria: {
    label: string;
    weight: string;
    slug: string;
    subcriteria: {
      label: string;
      slug: string;
      weight: string;
      max_score: string | null;
      min_score: string | null;
      scoring_method: string;
      enable_comments: boolean;
      comment_max_chars: string | null;
    }[];
    max_score: string;
    min_score: string;
    scoring_method: string;
    comment_max_chars: string;
    aggregation_method: string;
    enable_comments_criteria: boolean;
  }[];
  evaluation_agreement_text: string;
  require_agreement_acceptance: boolean;
  enable_overall_comments: boolean;
}

// FieldType
export type FieldType =
  | "text"
  | "email"
  | "url"
  | "phone"
  | "number"
  | "textarea"
  | "date"
  | "time"
  | "dropdown"
  | "multi_select"
  | "team"
  | "radio"
  | "checkbox"
  | "file"
  | "mimes"
  | "rating"
  | "section_header"
  | "paragraph";
export interface Field {
  id: string;
  slug: string;
  label: string;
  type: FieldType;
  required: boolean;
  placeholder?: string;
  hint?: string;
  value?: any;
  validation_rules?: {
    rule: string;
    value?: any;
    start_date?: string;
    end_date?: string;
    max_file_size?: string;
    value_date?: string;
    value_time?: string;
    start_time?: string;
    end_time?: string;
    allowed_mimes?: string;
    allowed_mimes_string?: string;
  }[];
  mandatory_options?: {
    indices: any[];
  };
  options?: {
    id: number;
    label: string;
  }[];
  conditional_logic?: boolean;
  conditional_logic_rules?: {
    values: {
      value: string;
    }[];
    field_id: string;
  }[];
  sort?: number;
  created_at?: string;
  fieldClass?: string;
  ai_enhancement_enabled?: boolean;
  ai_enhancement_instructions?: string;
  ai_enhancement_context_field?: string;
  suggestedValue?: string | null;
}

// Country
export interface Country {
  id: number;
  name: string;
}

// theme
export interface Theme {
  logo: string;
  white_logo: string;
  primary_color: string;
  secondary_color: string;
  font?: string;
  favicon?: string;
  metaData?: any;
  theme_status?: string;
  mode?: "light" | "dark";
}

// AccessToken
export interface AccessTokenPayload {
  user_type: string;
  exp?: number;
}

// profile
export interface Participant {
  id: number;
  name: string;
  email: string;
  email_verified_at: null;
  phone: string;
  gender: string;
  date_of_birth: string;
  nationality: string;
  country: string;
  residence_city: string;
  educational_background: string;
  current_role: string;
  place_of_work_study: string;
  years_of_experience: string;
  experience_or_skills: string;
  key_achievements: string;
  password_reset_code: null;
  last_login_at: null;
  created_at: string;
  updated_at: string;
  serial_number: string;
  recovery_email: string;
  login_by: string;
  nafath_data: any;
}

export interface Judge {
  id: number;
  competition: null;
  name: string;
  email: string;
  phone_number: string;
  experience_field: string;
  created_at: string;
}

export interface Mentor {
  id: number;
  competition: Competition;
  track: string | null;
  name: string;
  experience: string;
  brief: string;
  profession: string;
  email: string;
  phone: string;
  image: string;
  linkedin: string;
  facebook: string;
  instagram: string;
  last_login_at: string;
  is_visible: number;
}

// mentor session
export interface Session {
  id: number;
  title: string;
  description: string;
  scheduled_at: string;
  scheduled_at_formatted: string;
  duration_minutes: number;
  duration_formatted: string;
  end_time: string;
  end_time_formatted: string;
  status: string;
  status_display_name: string;
  video_tool: string;
  video_tool_display_name: string;
  meeting_id: string;
  join_url: string;
  password: string;
  calendar_event_id: string;
  declined_reason: string;
  cancellation_reason: string;
  proposed_time: string;
  proposed_time_formatted: string;
  has_proposed_time: boolean;
  is_pending_request: boolean;
  feedback: string;
  feedback_comments: string;
  feedback_strengths: string;
  feedback_improvements: string;
  rating: number;
  started_at: string;
  ended_at: string;
  is_upcoming: boolean;
  is_in_progress: boolean;
  is_completed: boolean;
  is_cancelled: boolean;
  mentor: Mentor;
  participant: Participant;
  competition: Competition;
  created_at: string;
  updated_at: string;
}
