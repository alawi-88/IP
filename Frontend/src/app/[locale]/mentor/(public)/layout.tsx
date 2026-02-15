"use client";
import Footer from "@/components/layout/Footer";
import Header from "@/components/layout/Header";
import { useLocale } from "next-intl";
import { GoogleReCaptchaProvider } from "react-google-recaptcha-v3";

export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const locale = useLocale();
  return (
    <GoogleReCaptchaProvider
      reCaptchaKey={process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY!}
      language={locale}
      scriptProps={{
        async: true,
        defer: true,
        appendTo: "head",
      }}
    >
      <main className="min-h-screen flex flex-col auth-layout">
        <Header type="mentor"/>
        {children}
      </main>
      <Footer />
    </GoogleReCaptchaProvider>
  );
}
