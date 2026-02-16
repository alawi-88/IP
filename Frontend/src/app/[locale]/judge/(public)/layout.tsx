"use client";
import Footer from "@/components/layout/Footer";
import Header from "@/components/layout/Header";

export default function PublicLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <>
      <main className="min-h-screen flex flex-col auth-layout">
        <Header type="judge"/>
        {children}
      </main>
      <Footer />
    </>
  );
}
