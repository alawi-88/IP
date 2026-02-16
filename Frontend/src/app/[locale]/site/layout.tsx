import Footer from "@/components/layout/Footer";
import Header from "@/components/layout/Header";
import HomeAlert from "@/components/site/HomeAlert";

export default async function SiteLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <>
      <main className="site_content">
        <HomeAlert />
        <Header className="site_header" type="site" />
        {children}
        <Footer className={"site_footer"} />
      </main>
    </>
  );
}
