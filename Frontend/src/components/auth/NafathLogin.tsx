"use client";
import axiosInstance, { APIError } from "@/axios";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Button, message, Spin } from "antd";
import { useLocale, useTranslations } from "next-intl";
import React, { useEffect, useLayoutEffect, useState } from "react";
import { useUserStore } from "@/store/user";
import { saveAccessToken } from "@/hooks/useTokenSaver";
import Image from "next/image";
import { useRouter } from "@/i18n/routing";

type NafathLoginProps = {
  role?: string;
  btnText?: string;
  status?: any;
};

function NafathLogin({ role, btnText, status }: NafathLoginProps) {
  const t = useTranslations();
  const locale = useLocale();
  const [messageApi, contextHolder] = message.useMessage();
  const router = useRouter();
  const [isStartRedirect, setIsStartRedirect] = useState(false);
  const setParticipant = useUserStore((state) => state.loginParticipant);
  const setJudge = useUserStore((state) => state.loginJudge);

  // Check if Nafath login is available
  const { data: loginStatus, isLoading: isLoginStatusLoading , refetch } = useQuery({
    queryKey: ["login-status"],
    queryFn: async () => {
      const response = await axiosInstance.get("/nafath/status");
      return response.data;
    },
    enabled: !status,
  });
  

  // Redirect to Nafath
  const { mutate: nafathRedirect, isPending } = useMutation({
    mutationFn: async (_) => {
      const response = await axiosInstance.post("/nafath/login", {
        lang: locale,
        redirect_url: `${window.location.origin}/${locale}/login`,
      });
      return response.data;
    },

    onSuccess: (data) => {
      if (data?.authorization_url) {
        sessionStorage.setItem("nafath_initiated", "true");
        window.location.href = data.authorization_url;
        setIsStartRedirect(true);
      }
    },
    onError: (error: APIError) => {
      if (error.response.data.message) {
        messageApi.error(
          error.response.data.message || t("nafath.login-error")
        );
      }
    },
  });

  // Fetch Nafath profile after redirect
  const {
    mutate: nafathLogin,
    isPending: isNafathProfileLoading,
    isError: isNafathProfileError,
  } = useMutation({
    mutationFn: async (token: string) => {
      const response = await axiosInstance.get("/participants/profile", {
        headers: { Authorization: `Bearer ${token}` },
      });
      return response.data;
    },
    onSuccess: (data, token) => {
      saveAccessToken(token);
      setParticipant(data);
      router.replace("/participant-dashboard");
    },
    onError: (error: APIError) => {
      messageApi.error(t("nafath.login-error"));
    },
  });

  useLayoutEffect(() => {
    const navigation = performance.getEntriesByType("navigation")[0] as
      | PerformanceNavigationTiming
      | undefined;
    if (navigation?.type === "reload") {
      // sessionStorage.clear();
      sessionStorage.removeItem("nafath_initiated");
    }

    const url = new URL(window.location.href);
    const token = url.searchParams.get("access_token");
    const nafathError = url.searchParams.get("nafath_fail");
    const initiated = sessionStorage.getItem("nafath_initiated");
    if (initiated === "true") {
      if (token) {
        nafathLogin(decodeURIComponent(token));
      }
      if (nafathError) {
        messageApi.error(t("nafath.login-error"));
      }
    }
    sessionStorage.removeItem("nafath_initiated");
    if (url.search) {
      const cleanUrl = window.location.origin + url.pathname;
      window.history.replaceState({}, "", cleanUrl);
    }
  }, []);

  return (
    <>
      {contextHolder}
      <Button
        type="default"
        htmlType="button"
        className="w-full !h-auto min-h-12 whitespace-normal"
        iconPosition="start"
        loading={isPending || isStartRedirect}
        disabled={
          (status && !status?.data?.enabled) ||
          // !loginStatus?.enabled ||
          isPending ||
          isNafathProfileLoading
        }
        onClick={() => nafathRedirect()}
      >
        <Image
          src={"/nafath-logo.png"}
          alt="Nafath"
          width={40}
          height={40}
          loading="eager"
          className="flex-shrink-0 h-4 object-contain"
          priority
        />
        <div>{btnText ? btnText : t("nafath.key")}</div>
      </Button>
      {isNafathProfileLoading && <Spin fullscreen size="large" />}
    </>
  );
}

export default NafathLogin;
