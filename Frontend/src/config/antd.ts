import { ThemeConfig, theme as antdTheme } from "antd";

/**
 * DGA Design System - Ant Design Theme Configuration
 * Colors are derived from admin-controlled branding settings.
 * Dark mode uses DGA neutral palette CSS variables for consistency.
 */

// Dark mode neutral palette (mirrors --dga-neutral-* CSS variables)
const darkNeutral = {
  surface: "#1f1f1f",       // --dga-neutral-50
  surfaceAlt: "#272727",    // --dga-neutral-100
  surfaceElevated: "#2d2d2d", // --dga-neutral-200
  control: "#3a3a3a",       // --dga-neutral-300
  controlAlt: "#565656",    // --dga-neutral-400
  textMuted: "#8c8c8c",     // --dga-neutral-500
  textSecondary: "#a3a3a3", // --dga-neutral-600
  textPrimary: "#f0f0f0",   // --dga-neutral-900
  border: "rgba(255,255,255,0.12)",
  hoverBg: "rgba(255,255,255,0.06)",
};

export const createThemeConfig = (
  theme: any,
  isDarkMode: boolean = false
): ThemeConfig => ({
  algorithm: isDarkMode ? antdTheme.darkAlgorithm : antdTheme.defaultAlgorithm,
  token: {
    colorPrimary: theme?.primary_color,
    colorPrimaryBg: isDarkMode ? darkNeutral.surfaceAlt : theme?.primary_color,
    colorText: isDarkMode ? darkNeutral.textPrimary : "#141414",
    colorPrimaryText: isDarkMode ? darkNeutral.textPrimary : "#141414",

    fontSize: 16,
    fontFamily: "var(--font-regular)",
    controlHeight: 48,
    controlHeightLG: 56,
    controlHeightSM: 32,
    paddingSM: 8,
    paddingMD: 16,
    paddingLG: 24,
    paddingXL: 32,
    borderRadius: 8,
  },
  components: {
    Button: {
      borderRadius: 8,
      colorBgContainer: isDarkMode ? darkNeutral.controlAlt : "#fff",
    },
    Input: {
      borderRadius: 8,
      paddingInline: 16,
      paddingBlock: 10,
      ...(isDarkMode && {
        colorBgContainer: darkNeutral.controlAlt,
        colorTextPlaceholder: darkNeutral.textMuted,
      }),
    },
    InputNumber: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: darkNeutral.controlAlt,
        colorTextPlaceholder: darkNeutral.textMuted,
      }),
    },
    Select: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: darkNeutral.controlAlt,
        colorTextPlaceholder: darkNeutral.textMuted,
      }),
    },
    Radio: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: darkNeutral.controlAlt,
        colorTextPlaceholder: darkNeutral.textMuted,
      }),
    },
    Checkbox: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: darkNeutral.controlAlt,
        colorTextPlaceholder: darkNeutral.textMuted,
      }),
    },
    DatePicker: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: darkNeutral.controlAlt,
        colorTextPlaceholder: darkNeutral.textMuted,
      }),
    },
    Table: {
      headerBg: isDarkMode ? darkNeutral.surfaceElevated : "#0000000f",
    },
    Menu: {
      itemSelectedBg: isDarkMode ? theme?.primary_color : "#0000000f",
      itemActiveBg: isDarkMode ? darkNeutral.hoverBg : "#0000000f",
      itemSelectedColor: isDarkMode ? darkNeutral.textPrimary : theme?.primary_color,
      colorBgContainer: isDarkMode ? darkNeutral.surfaceAlt : "#ffffff",
    },
    Drawer: {
      colorBgContainer: isDarkMode ? darkNeutral.surfaceAlt : "#ffffff",
      colorBgElevated: isDarkMode ? darkNeutral.surfaceAlt : "#ffffff",
    },
    Segmented: {
      itemSelectedBg: isDarkMode ? theme?.primary_color : "#f2f4f7",
    },
    Card: {
      colorBgContainer: isDarkMode ? darkNeutral.surfaceAlt : "#ffffff",
    },
    Modal: {
      contentBg: isDarkMode ? darkNeutral.surfaceAlt : "#ffffff",
      headerBg: isDarkMode ? darkNeutral.surfaceAlt : "#ffffff",
    },
  },
});

export const form = (lang: string) => ({
  validateMessages: {
    required: lang === "ar" ? "هذا الحقل مطلوب" : "This field is required",
    types: {
      email:
        lang === "ar"
          ? "صيغة البريد الإلكتروني غير صحيحة"
          : "The Email format is incorrect",
      number: lang === "ar" ? "إدخال رقم صحيح" : "Please enter a valid number",
      url: lang === "ar" ? "الرابط غير صالح" : "Invalid URL",
    },
    string: {
      min:
        lang === "ar"
          ? "الحد الأدنى للحروف هو ${min}"
          : "Minimum characters is ${min}",
      max:
        lang === "ar"
          ? "الحد الأقصى للحروف هو ${max}"
          : "Maximum characters is ${max}",
    },
  },
});
