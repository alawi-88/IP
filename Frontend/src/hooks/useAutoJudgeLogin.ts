import axiosInstance from "@/axios";
import { useUserStore } from "@/store/user";
import { useQuery } from "@tanstack/react-query";
import { getAccessToken } from "./useTokenSaver";
import { useEffect } from "react";

export const useAutoJudgeLogin = () => {
  const loginJudge = useUserStore((state) => state.loginJudge);
  const accessToken = getAccessToken();

  const { data } = useQuery({
    queryKey: ["judges", "profile"],
    queryFn: async () => {
      const response = await axiosInstance.get("/judges/profile");

      return response.data;
    },
    enabled: accessToken != null,
    retry: false,
  });

  useEffect(() => {
    if (data) {
      loginJudge({ ...data });
    }
  }, [data]);
};
