"use client";

import axiosInstance from "@/axios";
import { Link } from "@/i18n/routing";
import { useMutation } from "@tanstack/react-query";
import { Button, Spin } from "antd";
import { useTranslations } from "next-intl";
import { useSearchParams } from "next/navigation";
import { useEffect } from "react";

export default function VerifyPage() {
  const t = useTranslations();
  const searchParams = useSearchParams();

  const activation_code = searchParams.get("activation_code");

  const { isPending, mutate, error } = useMutation({
    mutationFn: async () => {
      const response = await axiosInstance.post(
        `/participants/activate-account`,
        {
          activation_code,
        }
      );
      return response.data;
    },
    retry: false,
  });

  useEffect(() => {
    mutate();
  }, []);

  let content = <></>;

  if (isPending) {
    content = <Spin />;
  } else if (error == null) {
    content = (
      <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
        {t("your-account-has-been-successfully-activated")}
      </h1>
    );
  } else {
    content = (
      <h1 className="text-4xl text-[#5B656A] font-bold text-center sm:text-start">
        {t("activation-link-is-invalid-or-expired")}
      </h1>
    );
  }

  return (
    <div className="card p-0 flex justify-center items-center">
      <div className="py-10 px-5 md:px-10 flex flex-col gap-5 items-center">
        {content}
        <Link href="/login">
          <Button
            type="primary"
            htmlType="button"
            size="large"
          >
            {t("login")}
          </Button>
        </Link>
      </div>
    </div>
  );
}
