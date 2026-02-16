import { getLandingPage, getServices } from "@/lib/actions";
import { getTranslations, getLocale } from "next-intl/server";
import { Metadata } from "next";
import SiteHomePage from "@/components/site/HomePage";

// type PageProps = {
//   params: Promise<{ locale: string }>;
// };

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getLocale();
  const t = await getTranslations({ locale, namespace: "meta" });
  return {
    title: t("home-title"),
  };
}

export default async function Page({ params }: { params: any }) {
  const { locale } = await params;
  const landingPage = await getLandingPage(locale);
  const services = await getServices(locale);
  return <SiteHomePage landingPage={landingPage} services={services} />;
}

