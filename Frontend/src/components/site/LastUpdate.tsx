
import React from "react";

import { useTranslations } from "next-intl";
function LastUpdate() {
  const t = useTranslations();
  return (
    <section className="section-style py-4 bg-card">
      <div className="container">
        <p className="text-sm">{t("site.last-update", { last_update: "" })}</p>
      </div>
    </section>
  );
}

export default LastUpdate;
