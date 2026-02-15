import { getLocale, getTranslations } from "next-intl/server";

import { Metadata } from "next";
import { Breadcrumb, Button, Divider, Tabs } from "antd";
import { PiLink } from "react-icons/pi";
import { TbExternalLink } from "react-icons/tb";
import Image from "next/image";
import { getSingleService } from "@/lib/actions";
import Empty from "@/components/Empty";
import ServicePage from "@/components/site/ServicePage";

// type PageProps = {
//   params: Promise<{ locale: string; id: string }>;
// };

export async function generateMetadata(): Promise<Metadata> {
  const locale = await getLocale();
  const t = await getTranslations({ locale, namespace: "meta" });
  return {
    title: t("services"),
  };
}

async function Page({ params }: { params: any }) {
  const { locale, id } = await params;
  const service = await getSingleService(locale, id);

  return <ServicePage service={service} />;
}

export default Page;
