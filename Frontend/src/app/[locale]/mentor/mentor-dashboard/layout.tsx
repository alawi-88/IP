"use client";
import DashboardHeader from "@/components/layout/dashboard/DashboardHeader";
import DashboardSidebar from "@/components/layout/dashboard/DashboardSidebar";
import { useAutoLogin } from "@/hooks/useAutoLogin";

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { isLoading, isError } = useAutoLogin("mentor");
  return (
    <>
      <div className="h-screen w-screen overflow-hidden flex">
        <div className="hidden lg:block z-50">
          <DashboardSidebar type="mentor" />
        </div>

        <div className="flex flex-col overflow-hidden w-full">
          <DashboardHeader type="mentor" dataLoading={isLoading} />
          <main className="flex-1 h-full overflow-x-hidden overflow-y-auto p-4 pt-6 lg:p-10">
            {children}
          </main>
        </div>
      </div>
    </>
  );
}
