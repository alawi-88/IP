import axiosInstance from "@/axios";
import { useUserStore } from "@/store/user";
import { useQuery } from "@tanstack/react-query";
import { getUser } from "./useTokenSaver";
import { useEffect } from "react";

export const useAutoLogin = (role:string) => {
  const { loginParticipant, loginJudge, loginMentor } = useUserStore();
  const user = getUser();
  const userType = user?.user_type;
  

  const { data, isError, isLoading } = useQuery({
    queryKey: ["profile", userType],
    queryFn: async () => {
      const response = await axiosInstance.get(`/${userType}s/profile`);
      return response.data;
    },
    enabled: Boolean(user),
    retry: false,
  });

  useEffect(() => {
    if (!data) return;
    switch (userType) {
      case "participant":
        loginParticipant(data);
        break;
      case "judge":
        loginJudge(data);
        break;
      case "mentor":
        loginMentor(data);
        break;
    }
  }, [data, userType, loginParticipant, loginJudge, loginMentor]);

  return { data, isError, isLoading };
};
