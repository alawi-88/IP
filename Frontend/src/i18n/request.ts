import { getRequestConfig } from "next-intl/server";
import { routing } from "./routing";

export default getRequestConfig(async ({ requestLocale }) => {
  // This typically corresponds to the `[locale]` segment
  let locale = await requestLocale;

  // Ensure that a valid locale is used
  if (!locale || !routing.locales.includes(locale as Locale)) {
    locale = routing.defaultLocale;
  }

  // Load main messages
  const mainMessages = (await import(`../../messages/${locale}.json`)).default;

  // Load venture-specific messages and merge them in
  let ventureMessages = {};
  try {
    ventureMessages = (await import(`./venture-${locale}.json`)).default;
  } catch (e) {
    // Venture translations not available for this locale — skip
  }

  return {
    locale,
    messages: {
      ...mainMessages,
      ...ventureMessages,
    },
  };
});
