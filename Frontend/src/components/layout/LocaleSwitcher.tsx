"use client";

import { usePathname, useRouter } from "@/i18n/routing";
import { Button } from "antd";
import { useLocale } from "next-intl";
import { useParams } from "next/navigation";
import { TbWorld } from "react-icons/tb";

export default function LocaleSwitcher() {
  const locale = useLocale();
  const router = useRouter();
  const pathname = usePathname();
  const params = useParams();

  const changeLocale = () => {
    router.replace(
      // @ts-expect-error -- TypeScript will validate that only known `params`
      // are used in combination with a given `pathname`. Since the two will
      // always match for the current route, we can skip runtime checks.
      { pathname, params },
      { locale: locale === "en" ? "ar" : "en" }
    );
  };

  return (
    <Button type="text" onClick={changeLocale} className="min-w-auto">
      <TbWorld className="text-primary text-2xl" />
    </Button>
  );
}
