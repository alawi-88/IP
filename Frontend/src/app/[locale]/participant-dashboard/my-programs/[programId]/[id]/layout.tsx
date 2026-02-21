"use client";
import axiosInstance from "@/axios";
import { usePathname, useRouter } from "@/i18n/routing";
import { ProgramApplication } from "@/lib/interfaces";
import { useThemeStore } from "@/store/theme";
import { useQuery } from "@tanstack/react-query";
import { Breadcrumb, Segmented, Spin, ConfigProvider } from "antd";
import { BreadcrumbItemType } from "antd/es/breadcrumb/Breadcrumb";
import { useLocale, useTranslations } from "next-intl";
import { useParams } from "next/navigation";
import { useEffect } from "react";

interface Tab {
  tab: string;
  label_en?: string;
  label_ar?: string;
  is_visible: boolean;
}

const availableTabs = [
  "journey",
  "events",
  "mentors",
  "my-team",
  "teams",
  "projects",
  "tasks",
  "guidelines",
  "winners",
  "leaderboard"
];

export default function ProgramApplicationLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const t = useTranslations();
  const router = useRouter();
  const pathname = usePathname();
  const currentSegment = pathname.split("/").pop();
  const locale = useLocale();
  const theme = useThemeStore((state) => state.theme)!;
  const setTheme = useThemeStore((state) => state.setTheme);

  const { programId, id, eventId, teamId, projectId } = useParams<{
    programId: string;
    id: string;
    eventId?: string;
    teamId?: string;
    projectId?: string;
  }>();

  // get program tabs
  const { data: activeTabs, isLoading } = useQuery<Tab[]>({
    queryKey: ["participants/program-tabs"],
    queryFn: async () => {
      const response = await axiosInstance.get(
        `/participants/program-tabs`,
        {
          params: {
            program_id: programId,
          },
        }
      );

      const tabs = response.data.data.filter(
        (tab: Tab) => tab.is_visible && availableTabs.includes(tab.tab)
      );
      // Sort tabs based on availableTabs order
      const sortedTabs = [...tabs].sort(
        (a, b) => availableTabs.indexOf(a.tab) - availableTabs.indexOf(b.tab)
      );

      return sortedTabs;
    },
  });

  // get my application
  const { data: myApplication, isLoading: isMyApplicationLoading } =
    useQuery<ProgramApplication>({
      queryKey: ["my-application", id],
      queryFn: async () => {
        const response = await axiosInstance.get(
          `/participants/program-applications/${id}`
        );
        return response.data.data;
      },
    });

  //updateFavicon
  function updateFavicon(favicon: string) {
    let link: HTMLLinkElement | null =
      document.querySelector("link[rel~='icon']");
    if (!link) {
      link = document.createElement("link");
      link.rel = "icon";
      link.type = "image/png";
      document.head.appendChild(link);
    }
    link.href = favicon;
  }

  useEffect(() => {
    const brandingTheme = myApplication?.program?.branding;
    if (brandingTheme && brandingTheme.is_published) {
      // Load font if exists
      if (brandingTheme.font) {
        const fontName = brandingTheme.font.replace(/ /g, "+");
        const linkId = "dynamic-google-font";
        // Avoid loading multiple times
        if (!document.getElementById(linkId)) {
          const link = document.createElement("link");
          link.id = linkId;
          link.rel = "stylesheet";
          link.href = `https://fonts.googleapis.com/css2?family=${fontName}:wght@100;300;400;500;700;900&display=swap`;
          document.head.appendChild(link);
        }
      }

      // set the favicon if exists
      if (brandingTheme.favicon) {
        updateFavicon(brandingTheme.favicon);
      }

      // Set theme
      setTheme(brandingTheme);
    }
    return () => {
      if (brandingTheme) {
        setTheme(theme);
        updateFavicon(theme.favicon || "");
      }
    };
  }, [myApplication]);

  useEffect(() => {
    const pathLength = pathname.split("/").length;
    if (activeTabs != null && activeTabs.length > 0 && pathLength === 5) {
      const firstTab = activeTabs[0];
      router.push(`${pathname}/${firstTab.tab}`);
    }
  }, [activeTabs, currentSegment]);

  const breadcrumbItems: BreadcrumbItemType[] = [
    {
      title: t("my-programs"),
      onClick: () => router.push("/participant-dashboard/my-programs"),
      className: "cursor-pointer hover:text-primary",
    },
    {
      title: myApplication?.program?.title
        ? myApplication.program.title
        : "-",
    },
  ];

  // if (myProgram?.program?.title) {
  //   breadcrumbItems.push({
  //     title: myProgram.program.title,
  //     className: "cursor-pointer hover:text-primary",
  //     onClick: () =>
  //       router.push(
  //         `/participant-dashboard/my-programs/${programId}/${id}`
  //       ),
  //   });
  // }

  // if (event?.title) {
  //   breadcrumbItems.push({ title: event.title });
  // }

  // if (team?.name) {
  //   breadcrumbItems.push({ title: team.name });
  // }

  if (isMyApplicationLoading || isLoading) {
    return <Spin className="w-full flex justify-center" />;
  }

  return (
    <section className="flex flex-col gap-y-6">
      <Breadcrumb items={breadcrumbItems} />

      <div className="flex justify-between items-center gap-4 flex-wrap">
        {activeTabs != null && activeTabs.length > 0 ? (
          <div className="overflow-x-auto max-w-full scrollbar-thin">
            <ConfigProvider direction={locale === "ar" ? "rtl" : "ltr"}>
              <Segmented
                value={currentSegment}
                onChange={(value) => {
                  router.push(
                    `/participant-dashboard/my-programs/${programId}/${id}/${value}`
                  );
                }}
                className="!bg-card !rounded-xl !p-2 !w-max"
                options={activeTabs.map((tab) => ({
                  value: tab.tab,
                  label: (locale === "ar" && tab.label_ar) ? tab.label_ar : (tab.label_en || t(tab.tab)),
                }))}
              />
            </ConfigProvider>
          </div>
        ) : (
          <div className="text-gray-500">{t("no-tabs-available")}</div>
        )}
        <div id="filter-section"></div>
      </div>

      {children}
    </section>
  );
}
