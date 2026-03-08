import { ThemeConfig, theme as antdTheme } from "antd";

// Function to create theme config with actual values instead of CSS variables
export const createThemeConfig = (
  theme: any,
  isDarkMode: boolean = false
): ThemeConfig => ({
  algorithm: isDarkMode ? antdTheme.darkAlgorithm : antdTheme.defaultAlgorithm,
  token: {
    colorPrimary: theme?.primary_color,
    colorPrimaryBg: isDarkMode ? "#272727" : theme?.primary_color,
    colorText: isDarkMode ? "#ffffff" : "#141414",
    colorPrimaryText: isDarkMode ? "#ffffff" : "#141414",

    fontSize: 16,
    fontFamily: "var(--font-regular)",
    controlHeight: 48,
    controlHeightLG: 56,
    controlHeightSM: 32,
    paddingSM: 8,
    paddingMD: 16,
    paddingLG: 24,
    paddingXL: 32,
    borderRadius: 16,
  },
  components: {
    Button: {
      borderRadius: 8,
      colorBgContainer: isDarkMode ? "#565656" : "#fff",
    },
    Input: {
      borderRadius: 8,
      paddingInline: 16,
      paddingBlock: 10,
      ...(isDarkMode && {
        colorBgContainer: "#565656",
        colorTextPlaceholder: "#B1B1B1",
      }),
    },
    InputNumber: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: "#565656",
        colorTextPlaceholder: "#B1B1B1",
      }),
    },
    Select: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: "#565656",
        colorTextPlaceholder: "#B1B1B1",
      }),
    },
    Radio: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: "#565656",
        colorTextPlaceholder: "#B1B1B1",
      }),
    },
    Checkbox: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: "#565656",
        colorTextPlaceholder: "#B1B1B1",
      }),
    },
    DatePicker: {
      borderRadius: 8,
      ...(isDarkMode && {
        colorBgContainer: "#565656",
        colorTextPlaceholder: "#B1B1B1",
      }),
    },
    Table: {
      headerBg: isDarkMode ? "#2d2d2d" : "#0000000f",
    },
    Menu: {
      itemSelectedBg: isDarkMode ? theme?.primary_color : "#0000000f",
      itemActiveBg: isDarkMode ? "#ffffff1f" : "#0000000f",
      itemSelectedColor: isDarkMode ? "#fff" : theme?.primary_color,
      colorBgContainer: isDarkMode ? "#272727" : "#ffffff",
    },
    Drawer: {
      colorBgContainer: isDarkMode ? "#272727" : "#ffffff",
      colorBgElevated: isDarkMode ? "#272727" : "#ffffff",
    },
    Segmented: {
      itemSelectedBg: isDarkMode ? theme?.primary_color : "#f2f4f7",
    },
    Card: {
      colorBgContainer: isDarkMode ? "#272727" : "#ffffff",
    },
    Modal: {
      contentBg: isDarkMode ? "#272727" : "#ffffff",
      headerBg: isDarkMode ? "#272727" : "#ffffff",
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
