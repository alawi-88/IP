import axiosInstance from "@/axios";
import { useUserStore } from "@/store/user";
import { useQuery } from "@tanstack/react-query";
import { getAccessToken } from "./useTokenSaver";
import { useEffect } from "react";

export const useAutoParticipantLogin = () => {
  const loginParticipant = useUserStore((state) => state.loginParticipant);
  const accessToken = getAccessToken();

  const { data } = useQuery({
    queryKey: ["profile"],
    queryFn: async () => {
      const response = await axiosInstance.get("/participants/profile");

      return response.data;
    },
    enabled: accessToken != null,
    retry: false,
  });

  useEffect(() => {
    if (data) {
      loginParticipant({ ...data });
    }
  }, [data]);
};
